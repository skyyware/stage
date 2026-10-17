<?php
declare(strict_types=1);

namespace Stage\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Stage\Http\Application;
use Stage\Http\HttpError;
use Stage\Http\Request;
use Stage\Http\Response;
use Stage\Http\Route;

final class RequestTest extends TestCase
{
    public function testDirectAndNativeRequestsReachTheSameAuthenticatedOperation(): void
    {
        $app = new Application(Route::get('/pages/{id}', static function (Request $request): Response {
            if (($request->headers['authorization'] ?? '') !== 'Bearer local-test-token') {
                throw new HttpError(401);
            }
            return Response::text($request->parameters['id']);
        }));
        $server = $_SERVER;
        try {
            $_SERVER = ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/pages/one', 'HTTP_AUTHORIZATION' => 'Bearer local-test-token'];
            $native = $app->handle(Request::fromGlobals());
            self::assertSame(200, $native->status);
            foreach (['Authorization', 'AUTHORIZATION', 'authorization'] as $name) {
                $direct = $app->handle(new Request('GET', '/pages/one', headers: [$name => 'Bearer local-test-token']));
                self::assertSame($native->status, $direct->status, $name);
                self::assertSame($native->body, $direct->body, $name);
            }
            self::assertSame(401, $app->handle(new Request('GET', '/pages/one'))->status);
            self::assertSame(401, $app->handle(new Request('GET', '/pages/one', headers: ['Authorization' => 'Bearer wrong']))->status);
        } finally {
            $_SERVER = $server;
        }
    }

    public function testRoutingPreservesHeaderValuesBodyAndQuery(): void
    {
        $headers = ['Authorization' => 'Bearer CaseSensitive', 'Content-Length' => '4', 'X-Text' => " \tGrüße, 世界 "];
        $request = new Request('POST', '/pages/one', 'body', $headers, 'a=1', ['id' => 'old']);
        $routed = $request->withParameters(['id' => 'one']);
        self::assertSame(['authorization' => 'Bearer CaseSensitive', 'content-length' => '4', 'x-text' => " \tGrüße, 世界 "], $routed->headers);
        self::assertSame('body', $routed->body);
        self::assertSame('a=1', $routed->query);
        self::assertSame(['id' => 'one'], $routed->parameters);
        self::assertSame(['id' => 'old'], $request->parameters);
    }

    /** @param array<string, string> $headers */
    #[DataProvider('invalidHeaders')]
    public function testInvalidHeadersCannotReachAnOperation(array $headers): void
    {
        $calls = 0;
        $app = new Application(Route::get('/', static function () use (&$calls): Response {
            $calls++;
            return Response::text('changed');
        }));
        try {
            $app->handle(new Request('GET', '/', headers: $headers));
            self::fail('Invalid headers reached the operation.');
        } catch (InvalidArgumentException) {
            self::assertSame(0, $calls);
        }
    }

    /** @return array<string, array{array<string, string>}> */
    public static function invalidHeaders(): array
    {
        return [
            'empty name' => [['' => 'value']],
            'space in name' => [['Bad Name' => 'value']],
            'separator in name' => [['X:Header' => 'value']],
            'non-ASCII name' => [['X-Ä' => 'value']],
            'line feed' => [['X-Test' => "ok\nInjected: value"]],
            'carriage return' => [['X-Test' => "ok\rvalue"]],
            'null byte' => [['X-Test' => "ok\0value"]],
            'control byte' => [['X-Test' => "ok\x01value"]],
            'delete byte' => [['X-Test' => "ok\x7fvalue"]],
            'ambiguous authorization' => [['Authorization' => 'Bearer one', 'authorization' => 'Bearer two']],
            'repeated same value' => [['X-Test' => 'same', 'x-test' => 'same']],
            'ambiguous range' => [['Range' => 'bytes=0-1', 'range' => 'bytes=3-4']],
            'range line ending' => [['Range' => "bytes=0-1\r\n"]],
        ];
    }

    public function testMalformedNativeHeaderBecomesBadRequest(): void
    {
        $server = $_SERVER;
        try {
            $_SERVER = ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/', 'HTTP_X_TEST' => "ok\r\nInjected: value"];
            try {
                Request::fromGlobals();
                self::fail('Malformed native header accepted.');
            } catch (HttpError $error) {
                self::assertSame(400, $error->status);
            }
        } finally {
            $_SERVER = $server;
        }
    }
}
