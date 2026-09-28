<?php
declare(strict_types=1);

namespace Stage\Http;

use Closure;
use InvalidArgumentException;

final readonly class Route
{
    /** @var Closure(Request): Response */
    public Closure $handler;

    /** @param callable(Request): Response $handler */
    public function __construct(public string $method, public string $path, callable $handler)
    {
        new Request($method, $path);
        if ($method === 'OPTIONS') {
            throw new InvalidArgumentException('OPTIONS is provided by the application.');
        }
        $this->handler = Closure::fromCallable($handler);
    }

    /** @param callable(Request): Response $handler */
    public static function get(string $path, callable $handler): self
    {
        return new self('GET', $path, $handler);
    }
}
