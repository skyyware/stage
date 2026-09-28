<?php
declare(strict_types=1);

namespace Stage\Tests;

use Example\Counter\Counter;
use Example\Counter\Increment;
use Example\Reports\Report;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Stage\Security\Caller;
use Stage\Security\Forbidden;

final class OperationTest extends TestCase
{
    public function testTypedInputRejectsInvalidAmountBeforeMutation(): void
    {
        $counter = new Counter();
        $caller = new Caller('writer', ['counter.read', 'counter.write']);
        try {
            $counter->increment(new Increment(0), $caller);
            self::fail('Invalid input was accepted.');
        } catch (InvalidArgumentException) {
            self::assertSame(0, $counter->read($caller));
        }
    }

    public function testAuthorizationAppliesToDirectCallsAndDoesNotLeak(): void
    {
        $counter = new Counter();
        $writer = new Caller('writer', ['counter.read', 'counter.write']);
        self::assertSame(2, $counter->increment(new Increment(2), $writer));
        try {
            $counter->increment(new Increment(3), new Caller());
            self::fail('Anonymous mutation was accepted.');
        } catch (Forbidden) {
            self::assertSame(2, $counter->read($writer));
        }
        $this->expectException(Forbidden::class);
        $counter->read(new Caller());
    }

    public function testSecondFeatureUsesPublicContract(): void
    {
        $counter = new Counter();
        $writer = new Caller('writer', ['counter.read', 'counter.write']);
        $counter->increment(new Increment(7), $writer);
        self::assertSame(7, (new Report($counter))->total(new Caller('reader', ['counter.read'])));
        self::assertSame(0, (new Counter())->read($writer));
    }

    public function testAnonymousCallerCannotHavePermissions(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Caller(null, ['counter.read']);
    }
}
