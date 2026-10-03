# Stage

Stage is a PHP framework for people and coding agents.
It provides HTTP routing, request and response values, and caller permission
checks. Keep your application rules in typed PHP objects, then compose more
features as the application grows.

Requires PHP 8.4 or later within PHP 8. Stage is open source under MIT.
Version 0.1 is an early release. The API can change between minor
versions before 1.0. Read [the changelog](CHANGELOG.md) before updating.

## Create an application

Install [PHP](https://www.php.net/downloads.php) 8.4 or later within PHP 8 and
[Composer](https://getcomposer.org/download/). In a new application directory:

```sh
mkdir stage-app
cd stage-app
composer require skyyware/stage:^0.1
mkdir public
```

For an existing Composer project, install the same package:

```sh
composer require skyyware/stage:^0.1
```

Composer installs the tagged package from [Packagist](https://packagist.org/packages/skyyware/stage).
No GitHub account or custom repository setting is required. Commit your application's `composer.lock`.

Create `public/index.php` in your application:

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

Start PHP's local development server from the application directory:

```sh
php -S 127.0.0.1:8080 -t public public/index.php
```

Open [http://127.0.0.1:8080](http://127.0.0.1:8080). The response is
`{"hello":"world"}`. Press Ctrl+C to stop the server.

Keep `vendor/` outside `public/`. Use only `public/` as the web root when you
configure a production server. PHP's built-in server is for local development.

Continue with [routes and JSON input](docs/getting-started.md) to add an endpoint
and check successful, malformed, and rejected requests.

## Build with clear boundaries

- Pass dependencies through constructors. No global container is required.
- Parse external input into types before calling an operation.
- Check permissions inside the operation, including calls outside HTTP.
- Expose a feature contract when another feature needs it.
- Test successful, denied, and malformed calls in your application.

Stage requires no other production Composer packages. Choose your application's
testing and analysis tools. Installing Stage does not install its development
tools or add a `composer check` command to your application.

## Documentation

Start with the guide that matches your next task:

| Task | Guide |
| --- | --- |
| Add routes, accept JSON, and check responses | [Build your first HTTP application](docs/getting-started.md) |
| Accept file uploads and stream downloads | [Transfer files](docs/files.md) |
| Package reusable routes as an idea | [Share an idea](docs/ideas.md) |
| Keep rules and permissions in reusable PHP objects | [Compose features](docs/features.md) |
| Look up HTTP methods, errors, and limits | [HTTP reference](docs/http.md) |
| Give a coding agent a bounded application task | [Work with agents](docs/agents.md) |
| Understand the core's responsibilities and tradeoffs | [Design](docs/design.md) and [principles](docs/principles.md) |
| Change the framework or publish a package version | [Contributing](CONTRIBUTING.md) and [releases](docs/releases.md) |

## Choose a package

Install only the packages your application needs:

- [Stage CMS](https://github.com/skyyware/stage-cms) adds editing, revisions, publication, and scoped agent access.
- [Stage Chat](https://github.com/skyyware/stage-chat) defines a stateless chat contract without a provider dependency.
- [Stage Chat Codex](https://github.com/skyyware/stage-chat-codex) implements that contract with an isolated Codex CLI process.

Each package has its own installation guide, version, and MIT license.

## Current scope

The initial core provides literal and named-parameter HTTP routes, bounded
request bodies, JSON responses, validated PHP uploads, streamed file downloads,
and an optional caller permission value.
Authentication, persistence, queues, templates, and agent integrations belong
to application code until an independently tested package earns a place here.

Ordinary message bodies are buffered. File downloads use bounded chunks;
PHP handles multipart uploads before dispatch. Worker lifecycle and performance
under load have not been validated. See [the design](docs/design.md) for the
current boundaries.

## License

[MIT](LICENSE). To work on the framework itself, follow
[contributing](CONTRIBUTING.md). Maintainers follow
[the package release guide](docs/releases.md).
