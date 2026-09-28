<?php
declare(strict_types=1);

namespace Stage\Security;

use InvalidArgumentException;

final readonly class Caller
{
    /** @param list<non-empty-string> $permissions */
    public function __construct(public ?string $id = null, private array $permissions = [])
    {
        if ($id === '' || ($id === null && $permissions !== [])) {
            throw new InvalidArgumentException('Permissions require an identified caller.');
        }
        foreach ($permissions as $permission) {
            if (trim($permission) === '') {
                throw new InvalidArgumentException('Permissions cannot be empty.');
            }
        }
    }

    public function require(string $permission): void
    {
        if (!in_array($permission, $this->permissions, true)) {
            throw new Forbidden();
        }
    }
}
