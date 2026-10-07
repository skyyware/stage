<?php
declare(strict_types=1);

use Stage\Http\Application;
use Stage\Http\FileResponse;
use Stage\Http\Request;
use Stage\Http\Response;
use Stage\Http\Route;
use Stage\Http\UploadedFile;

require dirname(__DIR__) . '/vendor/autoload.php';

$path = getenv('STAGE_TEST_FILE');
if (!is_string($path)) {
    throw new RuntimeException('Missing fixture.');
}
$upload = static function (int $limit): Response {
    $file = UploadedFile::fromPhp($_FILES['file'] ?? [], $limit);
    return Response::json([
        'name' => $file->name, 'size' => $file->size, 'sha256' => hash_file('sha256', $file->path),
        'field' => $_POST['field'] ?? null, 'peak' => memory_get_peak_usage(true),
    ]);
};
(new Application(
    Route::get('/health', fn () => Response::text('Stage file fixture')),
    Route::get('/download', fn () => new FileResponse($path, 'report "ä".bin', [
        'cache-control' => 'no-store', 'x-robots-tag' => 'noindex', 'content-security-policy' => "default-src 'none'",
    ])),
    Route::get('/missing', fn () => new FileResponse($path . '.missing', 'missing.bin')),
    Route::get('/inline', fn (Request $request) => FileResponse::inline($path, 'film.mp4', 'video/mp4', $request, ['cache-control' => 'no-store'])),
    new Route('POST', '/upload', fn () => $upload(250_000_000)),
    new Route('POST', '/small-upload', fn () => $upload(8)),
))->run(32);
