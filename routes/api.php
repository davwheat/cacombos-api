<?php

use App\Http\Controllers\ParseLogController;
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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::get('/heartbeat', function (ServerRequestInterface $request) {
    return response()->json(['status' => 'ok']);
});

// v1 API
Route::group(['prefix' => 'v1'], function () {
    Route::group(['prefix' => 'actions'], function () {
        Route::post('/parse-log', [ParseLogController::class, 'requestHandler']);
    });

    Route::group(['prefix' => 'api'], function () {
        // JSON:API instance
        Route::fallback(function (ServerRequestInterface $request) {
            $server = new JsonApiServer('/v1/api');

            return $server->requestHandler($request);
        });
    });
});
