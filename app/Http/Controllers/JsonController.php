<?php

namespace App\Http\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Nyholm\Psr7\Response;

abstract class JsonController extends Controller
{
    protected ResponseInterface $response;

    public function __construct()
    {
        $this->response = new Response();
    }

    public function requestHandler(ServerRequestInterface $request): ResponseInterface
    {
        $response = $this->handle($request);

        if (!$this->response->hasHeader('Content-Type')) {
            $this->response = $this->response->withHeader('Content-Type', 'application/json');
        }

        $encodedBody = $this->response->getHeader('Content-Type')[0] === 'application/json'
            ? json_encode($response)
            : $response;

        $this->response->getBody()->write($encodedBody);

        return $this->response;
    }

    abstract function handle(ServerRequestInterface $request): array | string | int | bool | null;
}
