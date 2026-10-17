<?php
declare(strict_types=1);

namespace Stage\Http\Internal;

use InvalidArgumentException;

/** @internal */
final class Headers
{
    /**
     * @param array<string, string> $headers
     * @return array<string, string>
     */
    public static function normalize(array $headers): array
    {
        $normalized = [];
        foreach ($headers as $name => $value) {
            $name = strtolower($name);
            if (!preg_match('/^[!#$%&\'*+.^_`|~0-9a-z-]+$/D', $name) || preg_match('/[\x00-\x08\x0a-\x1f\x7f]/', $value)) {
                throw new InvalidArgumentException('Invalid HTTP header.');
            }
            if (array_key_exists($name, $normalized)) {
                throw new InvalidArgumentException('Duplicate HTTP header.');
            }
            $normalized[$name] = $value;
        }
        return $normalized;
    }
}
