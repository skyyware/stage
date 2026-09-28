<?php
declare(strict_types=1);

namespace Example\Reports;

use Example\Counter\Counter;

final readonly class ForbiddenReport
{
    public function __construct(public Counter $counter) {}
}
