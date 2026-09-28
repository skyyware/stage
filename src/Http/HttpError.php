<?php
declare(strict_types=1);

namespace Stage\Http;

use InvalidArgumentException;
use RuntimeException;

final class HttpError extends RuntimeException
{
    public function __construct(public readonly int $status)
    {
        if ($status < 400 || $status > 599) {
            throw new InvalidArgumentException('An HTTP error needs a 4xx or 5xx status.');
        }
        parent::__construct('HTTP request failed.', $status);
    }
}
