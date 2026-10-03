<?php
declare(strict_types=1);

namespace Stage\Http;

use InvalidArgumentException;

readonly class Response
{
    /** @var array<string, string> */
    public array $headers;

    /** @param array<string, string> $headers */
    public function __construct(public string $body = '', public int $status = 200, array $headers = [])
    {
        if ($status < 200 || $status > 599 || (in_array($status, [204, 205, 304], true) && $body !== '')) {
            throw new InvalidArgumentException('Invalid final response status or body.');
        }
        $normalized = [];
        foreach ($headers as $name => $value) {
            $name = strtolower($name);
            if (!preg_match('/^[!#$%&\'*+.^_`|~0-9a-z-]+$/D', $name) || preg_match('/[\x00-\x08\x0a-\x1f\x7f]/', $value)) {
                throw new InvalidArgumentException('Invalid HTTP header.');
            }
            if (array_key_exists($name, $normalized) || in_array($name, ['content-length', 'transfer-encoding', 'connection'], true)) {
                throw new InvalidArgumentException('Duplicate or server-owned HTTP header.');
            }
            $normalized[$name] = $value;
        }
        $this->headers = $normalized;
    }

    public static function text(string $body, int $status = 200): self
    {
        return new self($body, $status, ['content-type' => 'text/plain; charset=utf-8']);
    }

    public static function html(string $body, int $status = 200): self
    {
        return new self($body, $status, ['content-type' => 'text/html; charset=utf-8']);
    }

    public static function json(mixed $data, int $status = 200): self
    {
        return new self(json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES), $status, ['content-type' => 'application/json; charset=utf-8']);
    }

    public function send(bool $head = false): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value, true);
        }
        if (!in_array($this->status, [204, 304], true)) {
            header('Content-Length: ' . $this->bodyLength());
        }
        if (!$head) {
            $this->sendBody();
        }
    }

    protected function bodyLength(): int
    {
        return strlen($this->body);
    }

    protected function sendBody(): void
    {
        echo $this->body;
    }
}
