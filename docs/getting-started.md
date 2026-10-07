# Build your first HTTP application

Build an application with a named route and a JSON endpoint. Start with
[the Composer application in the README](../README.md#create-an-application).
Run all commands from your application directory.

## Add routes and validate input

Replace `public/index.php` with:

```php
<?php
declare(strict_types=1);

use Stage\Http\Application;
use Stage\Http\HttpError;
use Stage\Http\Request;
use Stage\Http\Response;
use Stage\Http\Route;

require dirname(__DIR__) . '/vendor/autoload.php';

(new Application(
	Route::get('/', fn () => Response::json(['hello' => 'world'])),
	Route::get('/hello/{name}', fn (Request $request) => Response::json([
		'hello' => $request->parameters['name'],
	])),
	new Route('POST', '/hello', function (Request $request): Response {
		$data = $request->json();
		if (!is_array($data) || !is_string($data['name'] ?? null) || trim($data['name']) === '') {
			throw new HttpError(422);
		}

		return Response::json(['hello' => trim($data['name'])]);
	}),
))->run();
```

`Route::get` creates a GET route. Use `new Route` for other methods.
The named route exposes the decoded `name` segment in `Request::parameters`.
The POST handler reads JSON and accepts a nonempty string in `name`.

Start the development server:

```sh
php -S 127.0.0.1:8080 -t public public/index.php
```

## Check successful requests

In a second terminal, call the named route:

```sh
curl -i http://127.0.0.1:8080/hello/World
```

Expect status 200, a JSON content type, and `{"hello":"World"}`.
Try `/hello/Ada%20Lovelace`; the response contains `"Ada Lovelace"`.

Send a JSON request:

```sh
curl -i http://127.0.0.1:8080/hello \
	-H 'Content-Type: application/json' \
	-d '{"name":"Ada"}'
```

Expect status 200 and `{"hello":"Ada"}`.

## Check rejected input

Send malformed JSON:

```sh
curl -i http://127.0.0.1:8080/hello \
	-H 'Content-Type: application/json' \
	-d '{'
```

Expect status 400 and `{"error":400}`. `Request::json()` rejects malformed JSON.

Send valid JSON with an invalid name:

```sh
curl -i http://127.0.0.1:8080/hello \
	-H 'Content-Type: application/json' \
	-d '{"name":42}'
```

Expect status 422 and `{"error":422}`. The handler requires a string for `name`.
An empty or whitespace-only name also returns 422.

Call the POST endpoint with GET:

```sh
curl -i http://127.0.0.1:8080/hello
```

Expect status 405 with `Allow: OPTIONS, POST`. An unknown path returns 404.
Press Ctrl+C in the server terminal when you finish.

## Check a response without a server

Create `check.php` in your application directory:

```php
<?php
declare(strict_types=1);

use Stage\Http\Application;
use Stage\Http\Request;
use Stage\Http\Response;
use Stage\Http\Route;

require __DIR__ . '/vendor/autoload.php';

$app = new Application(
	Route::get('/', fn () => Response::json(['hello' => 'world'])),
);
$response = $app->handle(new Request('GET', '/'));

if ($response->status !== 200 || $response->body !== '{"hello":"world"}') {
	throw new RuntimeException('Unexpected response.');
}

echo "Response checked", PHP_EOL;
```

Run it:

```sh
php check.php
```

Expect `Response checked`. `handle` returns a response without writing headers
or output. Use the same method in your application's tests.

## Move rules into a feature

For an operation shared by HTTP, a command, or an agent adapter, keep its rules
and permission checks in a PHP object. Continue with
[Compose features](features.md) to build and call one in this application.
Use [the HTTP reference](http.md) to look up routing, messages, and body limits.
