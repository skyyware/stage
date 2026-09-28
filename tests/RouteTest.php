<?php
declare(strict_types=1);

namespace Stage\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Stage\Http\Application;
use Stage\Http\Request;
use Stage\Http\Response;
use Stage\Http\Route;

final class RouteTest extends TestCase
{
    public function testPageAndRevisionParametersAreDecodedOnce(): void
    {
        $app = new Application(Route::get('/pages/{page}/revisions/{revision}', fn (Request $r) => Response::json($r->parameters)));
        self::assertSame('{"page":"hello world","revision":"%2F"}', $app->handle(new Request('GET', '/pages/hello%20world/revisions/%252F'))->body);
        self::assertSame(404, $app->handle(new Request('GET', '/pages/hello/revisions'))->status);
        self::assertSame(404, $app->handle(new Request('GET', '/pages//revisions/2'))->status);
    }

    public function testLiteralRouteWinsRegardlessOfRegistrationOrder(): void
    {
        $app = new Application(
            Route::get('/pages/{page}', fn () => Response::text('dynamic')),
            Route::get('/pages/new', fn (Request $r) => Response::json($r->parameters)),
        );
        self::assertSame('[]', $app->handle(new Request('GET', '/pages/new', parameters: ['forged' => 'value']))->body);
        self::assertSame(405, $app->handle(new Request('POST', '/pages/new'))->status);
    }

    public function testDynamicMethodsPreserveHeadOptionsAndParameterNames(): void
    {
        $app = new Application(
            Route::get('/pages/{slug}', fn (Request $r) => Response::text($r->parameters['slug'])),
            new Route('POST', '/pages/{id}', fn (Request $r) => Response::json($r->parameters)),
        );
        self::assertSame('one', $app->handle(new Request('HEAD', '/pages/one'))->body);
        self::assertSame('{"id":"two"}', $app->handle(new Request('POST', '/pages/two'))->body);
        self::assertSame(204, $app->handle(new Request('OPTIONS', '/pages/one'))->status);
        self::assertSame('GET, HEAD, OPTIONS, POST', $app->handle(new Request('DELETE', '/pages/one'))->headers['allow']);
    }

    public function testEncodedSeparatorsAndControlsDoNotReachTheHandler(): void
    {
        $app = new Application(Route::get('/{slug}', fn () => self::fail('Invalid path reached the handler.')));
        foreach (['/%2f', '/%5c', '/%00', '/%0a', '/%7f'] as $path) {
            self::assertSame(404, $app->handle(new Request('GET', $path))->status);
        }
    }

    public function testEquivalentRoutePatternsCannotHideEachOther(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Application(Route::get('/{slug}', fn () => Response::text('one')), Route::get('/{id}', fn () => Response::text('two')));
    }

    public function testDuplicateParameterNamesAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Route::get('/{id}/{id}', fn () => Response::text('one'));
    }

    public function testPartialSegmentTemplatesAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Route::get('/page-{id}', fn () => Response::text('one'));
    }
}
