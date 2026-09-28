# Stage

Stage is a PHP framework for people and coding agents.
Start with a request and a response. Keep your application rules in typed PHP
objects, then compose more features as the application grows.

Requires PHP 8.4 or later within PHP 8. Stage is open source under MIT.
Version 0.1 is the first public release. The API can change between minor
versions before 1.0. Read [the changelog](CHANGELOG.md) before updating.

## Install with Composer

In an existing Composer project:

```sh
composer config repositories.stage vcs https://github.com/skyyware/stage.git
composer require skyyware/stage:^0.1
```

These commands install the tagged public package directly from GitHub.
No GitHub account is required. Commit your application's `composer.lock`.

## Run a small application

To run the included example and development checks:

```sh
git clone https://github.com/skyyware/stage.git
cd stage
composer install
php -S 127.0.0.1:8080 examples/hello.php
```

Open `http://127.0.0.1:8080`. The response is `{"hello":"world"}`.
The application is:

```php
<?php
declare(strict_types=1);

use Stage\Http\Application;
use Stage\Http\Response;
use Stage\Http\Route;

require dirname(__DIR__) . '/vendor/autoload.php';

(new Application(
	Route::get('/', fn () => Response::json(['hello' => 'world'])),
))->run();
```

## Build with clear boundaries

- Pass dependencies through constructors. No global container is required.
- Parse external input into types before calling an operation.
- Check permissions inside the operation, including calls outside HTTP.
- Expose a feature contract when another feature needs it.
- Run `composer check` to validate types, behavior, and dependency rules.

There are no production Composer dependencies. Development uses PHPUnit,
PHPStan at its strictest level, and [Deptrac](https://deptrac.github.io/deptrac/)
to check declared dependency rules. The checks include an intentionally forbidden
import to prove the architecture rule can fail.

Read [the HTTP reference](docs/http.md), [feature composition](docs/features.md),
[working with agents](docs/agents.md), and [the principles](docs/principles.md).
The [website](https://stage.dev) is the first
application and lives in a separate repository.

## Current scope

The initial core provides literal and named-parameter HTTP routes, immutable messages, bounded
request bodies, JSON responses, and an optional caller permission value.
Authentication, persistence, queues, templates, and agent integrations belong
to application code until an independently tested package earns a place here.

The router buffers bodies. Worker lifecycle and performance under load have
not been validated. See [the design](docs/design.md) for the current boundaries.

## License

[MIT](LICENSE). See [contributing](CONTRIBUTING.md) before changing the API.
