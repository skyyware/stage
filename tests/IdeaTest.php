<?php
declare(strict_types=1);

namespace Stage\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Stage\Http\Application;
use Stage\Http\Idea;
use Stage\Http\Request;
use Stage\Http\Response;
use Stage\Http\Route;

final class IdeaTest extends TestCase
{
    public function testIdeasUseTheSameRoutingAndMethodContract(): void
    {
        $idea = new class('Hello') implements Idea {
            public function __construct(private string $greeting) {}
            public function routes(): array
            {
                return [Route::get('/hello/{name}', fn (Request $request) => Response::text($this->greeting . ' ' . $request->parameters['name']))];
            }
        };
        $app = new Application($idea, Route::get('/hello/world', fn () => Response::text('Welcome')));
        self::assertSame('Hello Sam', $app->handle(new Request('GET', '/hello/Sam'))->body);
        self::assertSame('Welcome', $app->handle(new Request('GET', '/hello/world'))->body);
        self::assertSame('Hello Sam', $app->handle(new Request('HEAD', '/hello/Sam'))->body);
        self::assertSame(204, $app->handle(new Request('OPTIONS', '/hello/Sam'))->status);
        self::assertSame(405, $app->handle(new Request('POST', '/hello/Sam'))->status);
    }

    public function testAnIdeaCannotReplaceAnAlreadyRegisteredRoute(): void
    {
        $idea = new class implements Idea {
            public function routes(): array
            {
                return [Route::get('/hello/{name}', fn () => Response::text('hello'))];
            }
        };
        $this->expectException(InvalidArgumentException::class);
        new Application(Route::get('/hello/{id}', fn () => Response::text('existing')), $idea);
    }
}
