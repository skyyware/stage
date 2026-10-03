# Compose features

Build a counter and a report in your application. The counter owns input and
permission checks. The report receives a contract exposing only the read operation.

Start with [the Composer application](../README.md#create-an-application).
Run all commands from your application directory.

## Register your application's classes

Add an `autoload` section alongside `require` in your `composer.json`.
Keep any other dependencies and settings your application already has:

```json
{
	"require": {
		"skyyware/stage": "^0.1"
	},
	"autoload": {
		"psr-4": {
			"App\\": "src/"
		}
	}
}
```

Create the feature directories and regenerate the autoloader:

```sh
mkdir -p src/Features/Counter/Contract src/Features/Reports bin
composer dump-autoload
```

The `App\` prefix maps your application's classes to files under `src/`.

## Define the input and contract

Create `src/Features/Counter/Increment.php`:

```php
<?php
declare(strict_types=1);

namespace App\Features\Counter;

use InvalidArgumentException;

final readonly class Increment
{
	public function __construct(public int $amount)
	{
		if ($amount < 1 || $amount > 100) {
			throw new InvalidArgumentException('Increment must be between 1 and 100.');
		}
	}
}
```

Create `src/Features/Counter/Contract/ReadCount.php`:

```php
<?php
declare(strict_types=1);

namespace App\Features\Counter\Contract;

use Stage\Security\Caller;

interface ReadCount
{
	public function read(Caller $caller): int;
}
```

## Keep permissions inside the operation

Create `src/Features/Counter/Counter.php`:

```php
<?php
declare(strict_types=1);

namespace App\Features\Counter;

use App\Features\Counter\Contract\ReadCount;
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
```

The operations check permissions before reading or changing the count.
A call from HTTP, a command, or an agent adapter follows the same rule.

## Compose another feature through the contract

Create `src/Features/Reports/Report.php`:

```php
<?php
declare(strict_types=1);

namespace App\Features\Reports;

use App\Features\Counter\Contract\ReadCount;
use Stage\Security\Caller;

final readonly class Report
{
	public function __construct(private ReadCount $counter) {}

	public function total(Caller $caller): int
	{
		return $this->counter->read($caller);
	}
}
```

## Run successful, denied, and malformed calls

Create `bin/counter.php`:

```php
<?php
declare(strict_types=1);

use App\Features\Counter\Counter;
use App\Features\Counter\Increment;
use App\Features\Reports\Report;
use Stage\Security\Caller;
use Stage\Security\Forbidden;

require dirname(__DIR__) . '/vendor/autoload.php';

$counter = new Counter();
$writer = new Caller('writer', ['counter.read', 'counter.write']);
$reader = new Caller('reader', ['counter.read']);
$counter->increment(new Increment(2), $writer);
$report = new Report($counter);
echo $report->total($reader), PHP_EOL;

try {
	$counter->increment(new Increment(3), $reader);
} catch (Forbidden) {
	echo "Write denied", PHP_EOL;
}

try {
	$counter->increment(new Increment(0), $writer);
} catch (InvalidArgumentException) {
	echo "Invalid amount", PHP_EOL;
}

echo $report->total($reader), PHP_EOL;
```

Run the command:

```sh
php bin/counter.php
```

Expect:

```text
2
Write denied
Invalid amount
2
```

Both rejected calls leave the count unchanged.

## Apply the pattern to your application

Supply callers from your trusted authentication adapter. The fixed callers
above are local examples. A `Caller` value does not authenticate a person or
agent. Never accept a permission list from an untrusted request. Permissions
are exact strings; they do not support wildcards. Check resource and tenant
access inside the owning operation as well.

In an HTTP handler, catch `Forbidden` and return a 403 response. `Application`
does not automatically translate this exception; an uncaught `Forbidden`
reaches the generic 500 response from `run`. Keep HTTP translation in the
adapter so the operation remains usable from other entry points.

To check feature dependencies in your application, adapt the framework's
[Deptrac configuration](../deptrac.yaml) to your namespaces. Install the tool
as an application development dependency and test a forbidden import.
The framework's [boundary test](../tests/BoundaryTest.php) shows that check.
Static analysis does not prevent shared-database access or reflection.

The example stores its count in memory for one command invocation. It does
not provide persistence, transactions, concurrency, or tenant storage.
Create request-specific mutable objects per request. An immutable router
does not make a mutable object captured by a handler safe to share in a worker.

Read [the design](design.md) for the core's responsibilities and limits.
