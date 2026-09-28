<?php
declare(strict_types=1);

namespace Example\Counter;

use Example\Counter\Contract\ReadCount;
use Stage\Security\Caller;

final class Counter implements ReadCount
{
    private int $value = 0;

    public function increment(Increment $input, Caller $caller): int
    {
        $caller->require('counter.write');
        return $this->value += $input->amount;
    }

    public function read(Caller $caller): int
    {
        $caller->require('counter.read');
        return $this->value;
    }
}
