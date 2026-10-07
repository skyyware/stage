<?php
declare(strict_types=1);

namespace Stage\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Stage\Http\Application;
use Stage\Http\FileResponse;
use Stage\Http\HttpError;
use Stage\Http\Request;
use Stage\Http\Response;
use Stage\Http\Route;
use Stage\Http\UploadedFile;

final class FileTest extends TestCase
{
    public function testPhpUploadErrorsHaveExplicitHttpStatuses(): void
    {
        foreach ([UPLOAD_ERR_INI_SIZE => 413, UPLOAD_ERR_FORM_SIZE => 413, UPLOAD_ERR_PARTIAL => 400,
            UPLOAD_ERR_NO_FILE => 400, UPLOAD_ERR_NO_TMP_DIR => 500, UPLOAD_ERR_CANT_WRITE => 500,
            UPLOAD_ERR_EXTENSION => 500, 99 => 400] as $error => $status) {
            try {
                UploadedFile::fromPhp(['error' => $error], 100);
                self::fail('Failed upload accepted.');
            } catch (HttpError $failure) {
                self::assertSame($status, $failure->status);
            }
        }
    }

    public function testMalformedMetadataAndLocalFilesAreNotUploads(): void
    {
        $local = ['error' => UPLOAD_ERR_OK, 'name' => 'FileTest.php', 'tmp_name' => __FILE__, 'size' => filesize(__FILE__)];
        foreach ([[], ['error' => '0'], ['error' => [0]], $local, array_replace($local, ['name' => ['file']]),
            array_replace($local, ['name' => "bad\r\nname"]), array_replace($local, ['size' => -1])] as $metadata) {
            try {
                UploadedFile::fromPhp($metadata, PHP_INT_MAX);
                self::fail('Invalid upload accepted.');
            } catch (HttpError $failure) {
                self::assertSame(400, $failure->status);
            }
        }
    }

    public function testUploadLimitIsApplicationConfiguration(): void
    {
        $this->expectException(InvalidArgumentException::class);
        UploadedFile::fromPhp([], -1);
    }

    public function testFileResponsePreservesTheResponseContractAndApplicationHeaders(): void
    {
        $app = new Application(Route::get('/file', fn () => new FileResponse(__FILE__, 'report "ä".php', [
            'Cache-Control' => 'no-store', 'X-Robots-Tag' => 'noindex', 'Content-Security-Policy' => "default-src 'none'",
        ])));
        $response = $app->handle(new Request('HEAD', '/file'));
        self::assertInstanceOf(Response::class, $response);
        self::assertInstanceOf(FileResponse::class, $response);
        self::assertSame(filesize(__FILE__), $response->size);
        self::assertSame('', $response->body);
        self::assertSame('no-store', $response->headers['cache-control']);
        self::assertSame('noindex', $response->headers['x-robots-tag']);
        self::assertSame("default-src 'none'", $response->headers['content-security-policy']);
        self::assertSame('application/octet-stream', $response->headers['content-type']);
        self::assertSame('nosniff', $response->headers['x-content-type-options']);
        self::assertStringStartsWith('attachment;', $response->headers['content-disposition']);
        self::assertStringContainsString("filename*=UTF-8''report%20%22%C3%A4%22.php", $response->headers['content-disposition']);
    }

    public function testMissingFileFailsDuringDispatch(): void
    {
        $app = new Application(Route::get('/file', fn () => new FileResponse(__FILE__ . '.missing', 'file.bin')));
        $response = $app->handle(new Request('GET', '/file'));
        self::assertSame(404, $response->status);
        self::assertSame('{"error":404}', $response->body);
    }

    public function testDownloadNamesCannotInjectHeadersOrPaths(): void
    {
        foreach (['', '.', '..', '../file', 'dir\\file', "name\r\nX-Secret: value", "invalid\xff"] as $name) {
            try {
                new FileResponse(__FILE__, $name);
                self::fail('Invalid download name accepted.');
            } catch (InvalidArgumentException $error) {
                self::assertSame('The download name must be a UTF-8 filename.', $error->getMessage());
            }
        }
    }

    public function testCallerCannotTurnADownloadIntoInlineContent(): void
    {
        foreach (['Content-Type' => 'text/html', 'Content-Disposition' => 'inline', 'X-Content-Type-Options' => ''] as $name => $value) {
            $this->expectHeaderRefusal([$name => $value]);
        }
        $this->expectHeaderRefusal(['Content-Length' => '999999']);
        $this->expectHeaderRefusal(['X-Header' => "value\r\nInjected: value"]);
    }

    public function testDirectoryIsNotADownload(): void
    {
        try {
            new FileResponse(__DIR__, 'directory');
            self::fail('Directory accepted.');
        } catch (HttpError $error) {
            self::assertSame(404, $error->status);
        }
    }

    public function testInlineDeliveryOwnsItsMediaAndRangeHeaders(): void
    {
        $response = FileResponse::inline(__FILE__, 'film "ä".mp4', 'video/mp4', new Request('GET', '/media', headers: ['Range' => 'bytes=2-5']), ['Cache-Control' => 'no-store']);
        self::assertSame(206, $response->status);
        self::assertSame('', $response->body);
        self::assertSame(filesize(__FILE__), $response->size);
        self::assertSame('video/mp4', $response->headers['content-type']);
        self::assertSame('nosniff', $response->headers['x-content-type-options']);
        self::assertSame('bytes', $response->headers['accept-ranges']);
        self::assertSame('bytes 2-5/' . filesize(__FILE__), $response->headers['content-range']);
        self::assertSame('no-store', $response->headers['cache-control']);
        self::assertStringStartsWith('inline;', $response->headers['content-disposition']);
        self::assertStringContainsString("filename*=UTF-8''film%20%22%C3%A4%22.mp4", $response->headers['content-disposition']);
        foreach (['', 'video', 'video/*', 'video/mp4; codecs=avc1', "video/mp4\r\nX-Header: injected"] as $type) {
            try {
                FileResponse::inline(__FILE__, 'film.mp4', $type, new Request('GET', '/media'));
                self::fail('Invalid media type accepted.');
            } catch (InvalidArgumentException) {
                self::addToAssertionCount(1);
            }
        }
        foreach (['Content-Type', 'Content-Disposition', 'X-Content-Type-Options', 'Content-Range', 'Accept-Ranges', 'Content-Length', 'Transfer-Encoding'] as $name) {
            try {
                FileResponse::inline(__FILE__, 'film.mp4', 'video/mp4', new Request('GET', '/media'), [$name => 'injected']);
                self::fail('Owned file header replaced.');
            } catch (InvalidArgumentException) {
                self::addToAssertionCount(1);
            }
        }
    }

    public function testRangeFallbackDoesNotGuessAtMalformedOrConditionalRequests(): void
    {
        foreach (['bytes=', 'bytes=-', 'bytes=9-2', 'items=1-2', 'bytes=0-1,4-5', 'bytes=+1-2', "bytes=0-1\r\n", 'bytes=' . str_repeat('9', 50) . '-' . str_repeat('8', 50)] as $range) {
            $response = FileResponse::inline(__FILE__, 'film.mp4', 'video/mp4', new Request('GET', '/media', headers: ['range' => $range]));
            self::assertSame(200, $response->status, $range);
            self::assertArrayNotHasKey('content-range', $response->headers);
        }
        foreach ([new Request('HEAD', '/media', headers: ['range' => 'bytes=0-1']),
            new Request('POST', '/media', headers: ['range' => 'bytes=0-1']),
            new Request('GET', '/media', headers: ['range' => 'bytes=0-1', 'If-Range' => '"version"']),
            new Request('GET', '/media', headers: ['range' => 'bytes=0-1', 'Range' => 'bytes=3-4'])] as $request) {
            self::assertSame(200, FileResponse::inline(__FILE__, 'film.mp4', 'video/mp4', $request)->status);
        }
        foreach (['bytes=-0', 'bytes=' . str_repeat('9', 50) . '-'] as $range) {
            $response = FileResponse::inline(__FILE__, 'film.mp4', 'video/mp4', new Request('GET', '/media', headers: ['range' => $range]));
            self::assertSame(416, $response->status);
            self::assertSame('bytes */' . filesize(__FILE__), $response->headers['content-range']);
        }
    }

    private function expectHeaderRefusal(array $headers): void
    {
        try {
            new FileResponse(__FILE__, 'file.bin', $headers);
            self::fail('Unsafe file response header accepted.');
        } catch (InvalidArgumentException) {
            self::addToAssertionCount(1);
        }
    }
}
