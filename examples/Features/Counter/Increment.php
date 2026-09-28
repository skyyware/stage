<?php
declare(strict_types=1);

namespace Example\Counter;

use InvalidArgumentException;

final readonly class Increment
{
    public function __construct(public int $amount)
    {
        if ($amount < 1 || $amount > 100) {
            throw new InvalidArgumentException('Increment must be between 1 and 100.');
        }
    }
}
