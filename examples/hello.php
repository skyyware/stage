<?php
declare(strict_types=1);

use Stage\Http\Application;
use Stage\Http\Response;
use Stage\Http\Route;

require dirname(__DIR__) . '/vendor/autoload.php';

(new Application(
    Route::get('/', fn () => Response::json(['hello' => 'world'])),
))->run();
