<?php

namespace App\Http\Controllers;

use App\RequiresAuthentication;
use App\Rules\FileOrString;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;


class ParseAndImportLogController extends JsonController
{
    protected RequiresAuthentication $requiresAuthentication;
    protected ParseLogController $parseLogController;
    protected ImportParsedCsvController $importParsedCsvController;

    public function __construct(RequiresAuthentication $requiresAuthentication, ParseLogController $parseLogController, ImportParsedCsvController $importParsedCsvController)
    {
        $this->requiresAuthentication = $requiresAuthentication;
        $this->parseLogController = $parseLogController;
        $this->importParsedCsvController = $importParsedCsvController;

        parent::__construct();
    }

    /**
     * Stub -- never called
     */
    public function handle(ServerRequestInterface $request): array|string|int|bool|null
    {
        return null;
    }

    protected function writeResponse($json): ResponseInterface
    {
        $encodedBody = json_encode($json);

        $this->response = $this->response->withHeader('Content-Type', 'application/json');
        $this->response->getBody()->write($encodedBody);

        return $this->response;
    }

    public function requestHandler(ServerRequestInterface $request): ResponseInterface
    {
        ($this->requiresAuthentication)($request, 'uploader');

        $body = array_merge($request->getParsedBody(), $request->getUploadedFiles());

        $validator = Validator::make($body, [
            'logFormat' => ['required', 'string', Rule::in(array_keys($this->parseLogController::VALID_LOG_FORMATS))],
            'eutraLog' => ['required_without_all:eutranrLog,nrLog', new FileOrString()],
            'eutranrLog' => ['required_without_all:eutraLog,nrLog', new FileOrString()],
            'nrLog' => ['required_without_all:eutraLog,eutranrLog', new FileOrString()],
            'deviceId' => 'required|exists:devices,id',
            'capabilitySetId' => 'required|exists:capability_sets,id',
        ]);

        if ($validator->fails()) {
            $this->response = $this->response->withStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY);

            return $this->writeResponse([
                'errors' => $validator->errors()->jsonSerialize()
            ]);
        }

        $parseResponse = $this->parseLogController->requestHandler($request);

        if ($parseResponse->getStatusCode() !== 200) {
            return $parseResponse;
        }

        $parseResponse->getBody()->seek(0);

        $parsedBody = json_decode($parseResponse->getBody()->getContents(), true);

        if (Arr::has($body, 'eutraLog')) {
            $eutraCsv = array_shift($parsedBody);
        }

        /**
         * Ideally, I would implement a better output format in the parser
         * itself, and I might still do that in the future.
         * 
         * It's hard to ascertain which CSV is which in the STDOUT output
         * when using the Qualcomm hexdump log type, since you only input
         * two files, and this can produce either 1, 2, or 3 depending on
         * the device's capability. These 1 or 2 could be in any combination,
         * and it's not realistic to be able to tell which is which purely
         * from the output without some form of heuristics.
         * 
         * This is the best way I can think of doing it for now.
         */
        if ($body['logFormat'] === 'qualcomm') {
            $nrCsv = array_shift($parsedBody);
            $eutranrCsv = array_shift($parsedBody);
        } else {
            if (Arr::has($body, 'nrLog')) {
                $nrCsv = array_shift($parsedBody);
            }

            if (Arr::has($body, 'eutranrLog')) {
                $eutranrCsv = array_shift($parsedBody);
            }
        }

        $importBody = [
            'deviceId' => $body['deviceId'],
            'capabilitySetId' => $body['capabilitySetId'],
        ];

        if (isset($eutraCsv)) {
            $importBody['eutraCsv'] = $eutraCsv;
        }

        if (isset($nrCsv)) {
            $importBody['nrCsv'] = $nrCsv;
        }

        if (isset($eutranrCsv)) {
            $importBody['eutranrCsv'] = $eutranrCsv;
        }

        $importResponse = $this->importParsedCsvController->requestHandler($request->withParsedBody($importBody));

        return $importResponse;
    }
}
