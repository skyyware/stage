<?php
declare(strict_types=1);

use Stage\Http\Application;
use Stage\Http\Request;
use Stage\Http\Response;
use Stage\Http\Route;

require dirname(__DIR__) . '/vendor/autoload.php';

(new Application(
    Route::get('/', fn () => Response::text('Stage')),
    Route::get('/failure', fn () => throw new RuntimeException('PRIVATE_EXCEPTION_CANARY')),
    new Route('POST', '/echo', fn (Request $request) => Response::json($request->json())),
))->run(32);
