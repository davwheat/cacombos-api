<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Psr\Http\Message\ServerRequestInterface;
use App\JsonApi\V1\JsonApiServer;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/heartbeat', function (ServerRequestInterface $request) {
    return response()->json(['status' => 'ok']);
});

Route::group(['prefix' => 'v1'], function () {
    Route::fallback(function (ServerRequestInterface $request) {
        $server = new JsonApiServer();

        return $server->requestHandler($request);
    });
});
