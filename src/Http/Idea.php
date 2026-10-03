<?php
declare(strict_types=1);

namespace Stage\Http;

interface Idea
{
    /** @return list<Route> */
    public function routes(): array;
}
