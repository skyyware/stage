# Share an idea

An idea is a reusable piece of a Stage application. Distribute it as an ordinary
Composer library with an explicit PHP contract. Install it, construct it, and
pass it to the part of the application that needs it.

An HTTP idea implements `Stage\Http\Idea`, available since 0.1.4. It returns a
list of `Route` objects. `Application` accepts ideas alongside individual routes:

```php
<?php
declare(strict_types=1);

use Stage\Http\Application;
use Stage\Http\Idea;
use Stage\Http\Request;
use Stage\Http\Response;
use Stage\Http\Route;

require __DIR__ . '/vendor/autoload.php';

final readonly class Greeting implements Idea
{
	public function __construct(private string $greeting) {}

	/** @return list<Route> */
	public function routes(): array
	{
		return [Route::get('/hello/{name}', fn (Request $request) =>
			Response::json(['message' => $this->greeting . ' ' . $request->parameters['name']]))];
	}
}

(new Application(new Greeting('Hello')))->run();
```

Save this as `index.php` beside `vendor/` and run
`php -S 127.0.0.1:8080 index.php`. `GET /hello/Sam` returns
`{"message":"Hello Sam"}`. Dependencies are constructor arguments. The host
application decides which ideas to install and where state lives.

## Package it

Move the class into your own namespace and `src/` directory. A library's
`composer.json` can be this small:

```json
{
	"name": "your-vendor/greeting-idea",
	"description": "A greeting route for Stage applications.",
	"type": "library",
	"license": "MIT",
	"require": {"php": "^8.4", "skyyware/stage": "^0.1.4"},
	"autoload": {"psr-4": {"YourVendor\\Greeting\\": "src/"}}
}
```

Add the actual license, a runnable README, tests for successful and denied
calls, and a changelog. Publish a versioned repository and register it with
Packagist. Consumers install the library through Composer and commit their
lockfile. The example vendor name is a placeholder, not an existing package.

## Keep the contract small

Ideas are expanded once when `Application` is constructed. Literal priority,
named parameters, HEAD, OPTIONS, and 405 handling are unchanged. Duplicate
method/path shapes fail at startup across all ideas and ordinary routes;
an idea cannot silently replace another route.

This contract covers HTTP routes. Plain PHP services, CMS page types, themes,
and publication rules keep their existing contracts. Use constructor arguments
to compose them; do not hide schema changes or data writes in `routes()`.

There is no discovery scan, global hook registry, install-from-content endpoint,
or runtime marketplace. Installed Composer packages are trusted PHP code, not
a sandbox. Review their code, dependencies, maintenance, and permissions before
installation. Content and agent output cannot install or activate an idea.

For machine readers, include supported PHP/Stage versions, constructor inputs,
owned routes, required permissions, state location, validation commands,
upgrade steps, and current limits in the README. Keep examples executable.
