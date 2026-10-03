<?php
declare(strict_types=1);

namespace Stage\Http;

use InvalidArgumentException;
use Throwable;

final readonly class Application
{
    /** @var array<string, array<string, Route>> */
    private array $routes;

    /** @var array<int, array<string, array<int, array<string, Route>>>> */
    private array $patterns;

    public function __construct(Route|Idea ...$entries)
    {
        $index = [];
        foreach ($entries as $entry) {
            foreach ($entry instanceof Idea ? $entry->routes() : [$entry] as $route) {
                if (isset($index[$route->key][$route->method])) {
                    throw new InvalidArgumentException('Duplicate route: ' . $route->method . ' ' . $route->path);
                }
                $index[$route->key][$route->method] = $route;
            }
        }
        $literal = [];
        $patterns = [];
        $position = 0;
        foreach ($index as $path => $methods) {
            $prefix = strstr($path, '{}', true);
            if ($prefix === false) {
                $literal[$path] = $methods;
            } else {
                $patterns[substr_count($path, '/')][$prefix][$position] = $methods;
            }
            $position++;
        }
        $this->routes = $literal;
        $this->patterns = $patterns;
    }

    public function handle(Request $request): Response
    {
        $routes = $this->routes[$request->path] ?? [];
        if ($routes === []) {
            $patterns = $this->patterns[substr_count($request->path, '/')] ?? [];
            $candidatesByPosition = [];
            $prefix = '';
            foreach (explode('/', $request->path) as $segment) {
                $candidatesByPosition += $patterns[$prefix] ?? [];
                $prefix .= $segment . '/';
            }
            ksort($candidatesByPosition);
            foreach ($candidatesByPosition as $candidates) {
                $candidate = reset($candidates);
                if ($candidate->parameters($request->path) !== null) {
                    $routes = $candidates;
                    break;
                }
            }
        }
        if ($routes === []) {
            return Response::text('Not found', 404);
        }
        $route = $routes[$request->method] ?? ($request->method === 'HEAD' ? ($routes['GET'] ?? null) : null);
        if ($route !== null) {
            try {
                return ($route->handler)($request->withParameters($route->parameters($request->path) ?? []));
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
