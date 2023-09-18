<?php

namespace App\Http\Controllers;

use App\RequiresAuthentication;
use App\Rules\FileOrString;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ParseAndImportLogController extends JsonController
{
    protected RequiresAuthentication $requiresAuthentication;
    protected ParseLogController $parseLogController;
    protected ImportParsedJsonController $importParsedJsonController;

    public function __construct(RequiresAuthentication $requiresAuthentication, ParseLogController $parseLogController, ImportParsedJsonController $importParsedJsonController)
    {
        $this->requiresAuthentication = $requiresAuthentication;
        $this->parseLogController = $parseLogController;
        $this->importParsedJsonController = $importParsedJsonController;

        parent::__construct();
    }

    /**
     * Stub -- never called.
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
        ($this->requiresAuthentication)($request, 'uploader', true);

        $body = array_merge($request->getParsedBody(), $request->getUploadedFiles());

        $validator = Validator::make($body, [
            'logFormat'       => ['required', 'string', Rule::in(array_keys($this->parseLogController::VALID_LOG_FORMATS))],
            'eutraLog'        => ['required_without_all:eutranrLog,nrLog', new FileOrString()],
            'eutranrLog'      => ['required_without_all:eutraLog,nrLog', new FileOrString()],
            'nrLog'           => ['required_without_all:eutraLog,eutranrLog', new FileOrString()],
            'deviceId'        => 'required|exists:devices,id',
            'capabilitySetId' => 'required|exists:capability_sets,id',
        ]);

        if ($validator->fails()) {
            $this->response = $this->response->withStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY);

            return $this->writeResponse([
                'errors' => $validator->errors()->jsonSerialize(),
            ]);
        }

        $parseResponse = $this->parseLogController->requestHandler($request);

        if ($parseResponse->getStatusCode() !== 200) {
            return $parseResponse;
        }

        $parseResponse->getBody()->seek(0);

        $importBody = [
            'deviceId'        => $body['deviceId'],
            'capabilitySetId' => $body['capabilitySetId'],
            'jsonData'        => $parseResponse->getBody()->getContents(),
        ];

        $importResponse = $this->importParsedJsonController->requestHandler($request->withParsedBody($importBody));

        return $importResponse;
    }
}
