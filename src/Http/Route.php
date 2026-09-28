<?php
declare(strict_types=1);

namespace Stage\Http;

use Closure;
use InvalidArgumentException;

final readonly class Route
{
    /** @var Closure(Request): Response */
    public Closure $handler;

    public string $key;

    /** @var list<string> */
    private array $segments;

    /** @var array<int, string> */
    private array $names;

    /** @param callable(Request): Response $handler */
    public function __construct(public string $method, public string $path, callable $handler)
    {
        new Request($method, $path);
        if ($method === 'OPTIONS') {
            throw new InvalidArgumentException('OPTIONS is provided by the application.');
        }
        $this->handler = Closure::fromCallable($handler);
        $segments = explode('/', $path);
        $key = $segments;
        $names = [];
        foreach ($segments as $position => $segment) {
            if (preg_match('/^\{([a-zA-Z][a-zA-Z0-9_]*)\}$/D', $segment, $match)) {
                if (in_array($match[1], $names, true)) {
                    throw new InvalidArgumentException('Route parameter names must be unique.');
                }
                $names[$position] = $match[1];
                $key[$position] = '{}';
            } elseif (str_contains($segment, '{') || str_contains($segment, '}')) {
                throw new InvalidArgumentException('A route parameter must occupy one complete path segment.');
            }
        }
        $this->segments = $segments;
        $this->names = $names;
        $this->key = implode('/', $key);
    }

    /** @return array<string, string>|null */
    public function parameters(string $path): ?array
    {
        $segments = explode('/', $path);
        if (count($segments) !== count($this->segments)) {
            return null;
        }
        $parameters = [];
        foreach ($this->segments as $position => $expected) {
            $actual = $segments[$position];
            if (isset($this->names[$position])) {
                $value = rawurldecode($actual);
                if ($value === '' || preg_match('/[\x00-\x1f\x7f\/\\\\]/', $value)) {
                    return null;
                }
                $parameters[$this->names[$position]] = $value;
            } elseif ($actual !== $expected) {
                return null;
            }
        }
        return $parameters;
    }

    /** @param callable(Request): Response $handler */
    public static function get(string $path, callable $handler): self
    {
        return new self('GET', $path, $handler);
    }
}
