<?php

use App\Http\Controllers\ImportParsedCsvController;
use App\Http\Controllers\ParseAndImportLogController;
use App\Http\Controllers\ParseLogController;
use App\Http\Controllers\SubmitCombosController;
use Illuminate\Support\Facades\Route;
use Psr\Http\Message\ServerRequestInterface;
use App\JsonApi\V1\JsonApiServer;
use Illuminate\Support\Facades\DB;

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

Route::group(['middleware' => ['cache.headers']], function () {
    Route::get('/heartbeat', function (ServerRequestInterface $request) {
        return response()->json(['status' => 'ok']);
    });

    // v1 API
    Route::group(['prefix' => 'v1'], function () {
        Route::group(['prefix' => 'actions'], function () {
            Route::post('/parse-log', [ParseLogController::class, 'requestHandler']);
            Route::post('/import-csv', [ImportParsedCsvController::class, 'requestHandler']);
            Route::post('/parse-import-log', [ParseAndImportLogController::class, 'requestHandler']);
            Route::post('/submit-combos', [SubmitCombosController::class, 'requestHandler']);
        });

        Route::group(['prefix' => 'api', 'middleware' => 'etag'], function () {
            // JSON:API instance
            Route::any('{any}', function (ServerRequestInterface $request) {
                $server = new JsonApiServer('/v1/api');

                return DB::transaction(fn () => $server->requestHandler($request));
            })->where('any', '.*');
        });
    });
});
