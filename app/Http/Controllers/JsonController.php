<?php

namespace App\Http\Controllers;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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

        $encodedBody = json_encode($response);

        $this->response->getBody()->write($encodedBody);

        return $this->response;
    }

    abstract public function handle(ServerRequestInterface $request): array|string|int|bool|null;
}
