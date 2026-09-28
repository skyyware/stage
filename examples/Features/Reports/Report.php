<?php
declare(strict_types=1);

namespace Example\Reports;

use Example\Counter\Contract\ReadCount;
use Stage\Security\Caller;

final readonly class Report
{
    public function __construct(private ReadCount $counter) {}

    public function total(Caller $caller): int
    {
        return $this->counter->read($caller);
    }
}
