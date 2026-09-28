<?php
declare(strict_types=1);

namespace Stage\Http;

use InvalidArgumentException;
use Throwable;

final readonly class Application
{
    /** @var array<string, array<string, Route>> */
    private array $routes;

    public function __construct(Route ...$routes)
    {
        $index = [];
        foreach ($routes as $route) {
            if (isset($index[$route->path][$route->method])) {
                throw new InvalidArgumentException('Duplicate route: ' . $route->method . ' ' . $route->path);
            }
            $index[$route->path][$route->method] = $route;
        }
        $this->routes = $index;
    }

    public function handle(Request $request): Response
    {
        $routes = $this->routes[$request->path] ?? [];
        if ($routes === []) {
            return Response::text('Not found', 404);
        }
        $route = $routes[$request->method] ?? ($request->method === 'HEAD' ? ($routes['GET'] ?? null) : null);
        if ($route !== null) {
            try {
                return ($route->handler)($request);
            } catch (HttpError $error) {
                return Response::json(['error' => $error->status], $error->status);
            }
        }
        $allowed = array_keys($routes);
        if (isset($routes['GET']) && !isset($routes['HEAD'])) {
            $allowed[] = 'HEAD';
        }
        $allowed[] = 'OPTIONS';
        sort($allowed);
        return new Response('', $request->method === 'OPTIONS' ? 204 : 405, ['allow' => implode(', ', $allowed)]);
    }

    public function run(int $maxBodyBytes = 1048576): void
    {
        try {
            $request = Request::fromGlobals($maxBodyBytes);
            $response = $this->handle($request);
        } catch (HttpError $error) {
            $response = Response::json(['error' => $error->status], $error->status);
        } catch (Throwable $error) {
            error_log('Stage request failed: ' . $error::class);
            $response = Response::text('Internal server error', 500);
        }
        $response->send(($_SERVER['REQUEST_METHOD'] ?? '') === 'HEAD');
    }
}
