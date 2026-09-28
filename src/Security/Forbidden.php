<?php
declare(strict_types=1);

namespace Stage\Security;

use RuntimeException;

final class Forbidden extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Permission denied.');
    }
}
