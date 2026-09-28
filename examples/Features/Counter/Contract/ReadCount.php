<?php
declare(strict_types=1);

namespace Example\Counter\Contract;

use Stage\Security\Caller;

interface ReadCount
{
    public function read(Caller $caller): int;
}
