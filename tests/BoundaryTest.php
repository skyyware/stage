<?php
declare(strict_types=1);

namespace Stage\Tests;

use PHPUnit\Framework\TestCase;

final class BoundaryTest extends TestCase
{
    public function testArchitectureCheckRejectsImplementationDependency(): void
    {
        $root = dirname(__DIR__);
        $process = proc_open(
            [PHP_BINARY, $root . '/vendor/bin/deptrac', 'analyse', '--config-file=deptrac-forbidden.yaml', '--no-cache', '--no-progress', '--formatter=json'],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            $root,
        );
        self::assertIsResource($process);
        $output = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        self::assertSame(1, proc_close($process), $output . $errors);
        self::assertStringContainsString('ForbiddenReport', $output);
        self::assertStringContainsString('Counter', $output);
    }
}
