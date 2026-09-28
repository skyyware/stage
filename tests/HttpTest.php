<?php
declare(strict_types=1);

namespace Stage\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Stage\Http\Application;
use Stage\Http\HttpError;
use Stage\Http\Request;
use Stage\Http\Response;
use Stage\Http\Route;

final class HttpTest extends TestCase
{
    public function testDispatchPreservesTypedRequestAndJson(): void
    {
        $app = new Application(new Route('POST', '/echo', fn (Request $r) => Response::json($r->json(), 201)));
        $response = $app->handle(new Request('POST', '/echo', '{"name":"Stage"}'));
        self::assertSame(201, $response->status);
        self::assertSame('{"name":"Stage"}', $response->body);
        self::assertSame('application/json; charset=utf-8', $response->headers['content-type']);
    }

    public function testUnknownPathAndWrongMethodAreDistinct(): void
    {
        $app = new Application(Route::get('/', fn () => Response::text('hello')));
        self::assertSame(404, $app->handle(new Request('GET', '/missing'))->status);
        $response = $app->handle(new Request('POST', '/'));
        self::assertSame(405, $response->status);
        self::assertSame('GET, HEAD, OPTIONS', $response->headers['allow']);
        $options = $app->handle(new Request('OPTIONS', '/'));
        self::assertSame(204, $options->status);
        self::assertSame('', $options->body);
        self::assertSame(404, $app->handle(new Request('OPTIONS', '/missing'))->status);
    }

    public function testHeadSelectsGetUnlessExplicit(): void
    {
        $app = new Application(Route::get('/', fn () => Response::text('hello')));
        self::assertSame('hello', $app->handle(new Request('HEAD', '/'))->body);
        $app = new Application(Route::get('/', fn () => Response::text('hello')), new Route('HEAD', '/', fn () => new Response('', 202)));
        self::assertSame(202, $app->handle(new Request('HEAD', '/'))->status);
    }

    public function testMalformedJsonCannotInvokeOperation(): void
    {
        $calls = 0;
        $app = new Application(new Route('POST', '/', function (Request $request) use (&$calls) {
            $input = $request->json();
            $calls++;
            return Response::json($input);
        }));
        self::assertSame(400, $app->handle(new Request('POST', '/', '{'))->status);
        self::assertSame(0, $calls);
    }

    public function testDuplicateRoutesFailDuringConstruction(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Application(Route::get('/', fn () => Response::text('one')), Route::get('/', fn () => Response::text('two')));
    }

    public function testHeaderInjectionIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Response('body', headers: ['x-test' => "ok\r\nSet-Cookie: secret"]);
    }

    public function testHeaderNamesAreCaseInsensitiveAndCannotCollide(): void
    {
        self::assertSame(['x-test' => 'ok'], (new Response(headers: ['X-Test' => 'ok']))->headers);
        $this->expectException(InvalidArgumentException::class);
        new Response(headers: ['X-Test' => 'one', 'x-test' => 'two']);
    }

    public function testCallerCannotInjectFramingHeader(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Response('a', headers: ['Content-Length' => '500']);
    }

    public function testBodylessStatusCannotContainBody(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Response('invalid', 204);
    }

    public function testInvalidPathFailsBeforeRouting(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Request('GET', "/bad\r\npath");
    }

    public function testExpectedErrorsDoNotExposeExceptionDetails(): void
    {
        $app = new Application(Route::get('/', fn () => throw new HttpError(403)));
        $response = $app->handle(new Request('GET', '/'));
        self::assertSame(403, $response->status);
        self::assertSame('{"error":403}', $response->body);
    }

    public function testReusableApplicationDoesNotRetainPreviousInput(): void
    {
        $app = new Application(new Route('POST', '/', fn (Request $r) => Response::text($r->body)));
        self::assertSame('first', $app->handle(new Request('POST', '/', 'first'))->body);
        self::assertSame('second', $app->handle(new Request('POST', '/', 'second'))->body);
        self::assertSame('', $app->handle(new Request('POST', '/'))->body);
    }
}
