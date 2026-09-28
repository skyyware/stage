<?php
declare(strict_types=1);

namespace Stage\Http;

use InvalidArgumentException;
use JsonException;

final readonly class Request
{
    /** @param array<string, string> $headers */
    public function __construct(
        public string $method,
        public string $path,
        public string $body = '',
        public array $headers = [],
        public string $query = '',
    ) {
        if (!preg_match('/^[A-Z]+$/D', $method)) {
            throw new InvalidArgumentException('The HTTP method must contain uppercase letters.');
        }
        if (!str_starts_with($path, '/') || preg_match('/[\x00-\x20\x7f?#]|%(?![0-9a-f]{2})/i', $path)) {
            throw new InvalidArgumentException('The request path must be an encoded absolute path.');
        }
    }

    public static function fromGlobals(int $maxBodyBytes = 1048576): self
    {
        if ($maxBodyBytes < 0 || $maxBodyBytes >= PHP_INT_MAX) {
            throw new InvalidArgumentException('Invalid request body limit.');
        }
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $target = $_SERVER['REQUEST_URI'] ?? '/';
        if (!is_string($method) || !is_string($target)) {
            throw new HttpError(400);
        }
        $body = file_get_contents('php://input', false, null, 0, $maxBodyBytes + 1);
        if ($body === false) {
            throw new HttpError(400);
        }
        if (strlen($body) > $maxBodyBytes) {
            throw new HttpError(413);
        }
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (is_string($value) && str_starts_with($key, 'HTTP_')) {
                $headers[strtolower(str_replace('_', '-', substr($key, 5)))] = $value;
            }
        }
        if (is_string($_SERVER['CONTENT_TYPE'] ?? null)) {
            $headers['content-type'] = $_SERVER['CONTENT_TYPE'];
        }
        [$path, $query] = array_pad(explode('?', $target, 2), 2, '');
        try {
            return new self($method, $path, $body, $headers, $query);
        } catch (InvalidArgumentException) {
            throw new HttpError(400);
        }
    }

    public function json(): mixed
    {
        try {
            return json_decode($this->body, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new HttpError(400);
        }
    }
}
