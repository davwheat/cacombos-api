<?php

namespace App\Http\Controllers;

use App\Mail\SubmitCombos;
use App\RequiresAuthentication;
use App\Rules\File;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Psr\Http\Message\ServerRequestInterface;
use Illuminate\Support\Facades\Mail;
use Psr\Http\Message\UploadedFileInterface;

class SubmitCombosController extends JsonController
{
    protected RequiresAuthentication $requiresAuthentication;

    public function __construct(RequiresAuthentication $requiresAuthentication)
    {
        $this->requiresAuthentication = $requiresAuthentication;

        parent::__construct();
    }

    public function handle(ServerRequestInterface $request): array|string|int|bool|null
    {
        $body = array_merge($request->getParsedBody(), $request->getUploadedFiles());


        $validator = Validator::make($body, [
            'fromUser' => 'nullable|email',
            'deviceName' => 'required|string|max:255',
            'deviceModel' => 'required|string|max:255',
            'deviceFirmware' => 'required|string|max:255',
            'comment' => 'required|string|max:2500',
            'log' => [new File(25 * 1024 * 1024)],
        ]);


        if ($validator->fails()) {
            $this->response = $this->response->withStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY);

            return [
                'errors' => $validator->errors()->jsonSerialize()
            ];
        }

        $submitCombos = new SubmitCombos(
            fromUser: Arr::get($body, 'fromUser'),
            deviceName: Arr::get($body, 'deviceName'),
            deviceModel: Arr::get($body, 'deviceModel'),
            deviceFirmware: Arr::get($body, 'deviceFirmware'),
            comment: Arr::get($body, 'comment'),
            log: Arr::get($body, 'log'),
        );

        $result = Mail::to('david@davwheat.dev')->send($submitCombos);

        if ($result) {
            $this->response = $this->response->withStatus(HttpResponse::HTTP_OK);

            return [
                'message' => 'Combos submitted successfully',
            ];
        }

        $this->response = $this->response->withStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY);

        return [
            'errors' => [
                'title' => 'Failed to notify admin',
                'status' => HttpResponse::HTTP_INTERNAL_SERVER_ERROR
            ]
        ];
    }
}
