<?php

namespace App\Http\Controllers;

use App\Repositories\TokensRepository;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;


class ParseAndImportLogController extends JsonController
{
    protected TokensRepository $tokensRepository;
    protected ParseLogController $parseLogController;
    protected ImportParsedCsvController $importParsedCsvController;

    public function __construct(TokensRepository $tokenRepository, ParseLogController $parseLogController, ImportParsedCsvController $importParsedCsvController)
    {
        $this->tokensRepository = $tokenRepository;
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
        $token = $request->getHeader('X-Auth-Token')[0] ?? null;

        $this->tokensRepository->assertValidToken($token);

        $body = array_merge($request->getParsedBody(), $request->getUploadedFiles());

        $fileOrStringValidator = function ($attribute, $value, $fail) {
            if (!is_string($value) && !($value instanceof UploadedFileInterface)) {
                $fail('The ' . $attribute . ' must either be a string or file.');
            }
        };

        $validator = Validator::make($body, [
            'logFormat' => ['required', 'string', Rule::in(array_keys($this->parseLogController::VALID_LOG_FORMATS))],
            'eutraLog' => ['required_without_all:eutranrLog,nrLog', $fileOrStringValidator],
            'eutranrLog' => ['required_without_all:eutraLog,nrLog', $fileOrStringValidator],
            'nrLog' => ['required_without_all:eutraLog,eutranrLog', $fileOrStringValidator],
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

        if (Arr::has($body, 'nrLog')) {
            $nrCsv = array_shift($parsedBody);
        }

        if (Arr::has($body, 'eutranrLog')) {
            $eutranrCsv = array_shift($parsedBody);
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
