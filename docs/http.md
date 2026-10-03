# HTTP reference

Use [Build your first HTTP application](getting-started.md) for a runnable
Composer application. This reference describes the `Stage\Http` classes.

## Routes

`Application` accepts `Route` objects. Each route has an uppercase method, a
literal encoded path or a named path pattern, and a callable accepting `Request`
and returning `Response`.

| Entry point | Behavior |
| --- | --- |
| `new Route(string $method, string $path, callable $handler)` | Registers an uppercase method and encoded path or pattern |
| `Route::get(string $path, callable $handler)` | Constructs a GET route |
| `new Application(Route ...$routes)` | Builds the routing index and rejects duplicate method and path shapes |

Duplicate methods for the same path shape fail at startup, including `/{id}`
and `/{slug}`. OPTIONS is provided by the application; registering an OPTIONS
route throws `InvalidArgumentException`.

Named parameters occupy a complete segment, as in `/pages/{slug}`. A parameter
name starts with a letter and contains letters, digits, or underscores. Names
must be unique within the route. A matched request exposes values through
`$request->parameters['slug']`. Values are decoded once. Empty values, decoded
slashes, backslashes, and control characters do not match.

Literal paths take priority regardless of registration order. Other overlapping
patterns use registration order. The matched path determines allowed methods;
a method mismatch does not fall through to a less specific route. Parameter
values remain untrusted input and need application validation.

## Dispatch

| Entry point | Behavior |
| --- | --- |
| `Application::handle(Request $request): Response` | Dispatches in memory without sending headers or output |
| `Application::run(int $maxBodyBytes = 1048576): void` | Reads PHP's request, dispatches, and sends the response |

An unknown path returns 404. An unsupported method returns 405 with `Allow`.
OPTIONS returns 204 with `Allow` for a known path. HEAD uses a registered HEAD
route or falls back to GET. A response from `handle` retains its body in memory;
`run` suppresses body output for HEAD.

`handle` translates a handler's `HttpError` into JSON such as `{"error":422}`
with the same HTTP status. `run` also translates errors from reading the request.
An unexpected exception propagates from `handle`. `run` catches the exception,
returns a generic 500, and logs its class without its message or request data.

`Stage\Security\Forbidden` is not an `HttpError`. It needs translation to 403
in the application's HTTP handler. [Compose features](features.md) describes
operation permission checks.

## Requests

`Request` holds `method`, `path`, `body`, `headers`, the raw `query` string, and
matched `parameters`. The constructor requires an uppercase method and an
encoded absolute path without spaces, control characters, a query, or a fragment.
Paths are not normalized or decoded. Invalid constructor input throws
`InvalidArgumentException`.

| Entry point | Behavior |
| --- | --- |
| `new Request(string $method, string $path, string $body = '', array $headers = [], string $query = '', array $parameters = [])` | Constructs a request for an adapter or an in-memory call |
| `Request::fromGlobals(int $maxBodyBytes = 1048576): Request` | Reads the request from PHP globals and limits the body in bytes |
| `Request::withParameters(array $parameters): Request` | Returns a new request with a replaced parameter map |
| `Request::json(): mixed` | Decodes JSON into PHP values, using associative arrays for objects |

Dispatch replaces any pre-existing parameter map with the matched route's values.
`fromGlobals` reads at most the body limit plus one byte. Oversized input throws
`HttpError(413)`; a malformed request or unreadable body throws `HttpError(400)`.
Headers read from PHP have lowercase names. The raw query is kept separately
from the path. Proxy headers never set identity or trust.

`json()` throws `HttpError(400)` for malformed JSON. A valid JSON value can be
an array, scalar, or null; decoding does not validate the shape or domain rules.

## Responses

| Entry point | Behavior |
| --- | --- |
| `new Response(string $body = '', int $status = 200, array $headers = [])` | Constructs a final response with string-valued headers |
| `Response::text(string $body, int $status = 200): Response` | Sets `text/plain; charset=utf-8` |
| `Response::html(string $body, int $status = 200): Response` | Sets `text/html; charset=utf-8` |
| `Response::json(mixed $data, int $status = 200): Response` | Encodes JSON and sets `application/json; charset=utf-8` |
| `Response::send(bool $head = false): void` | Writes status and headers, calculates content length, and sends the body unless `$head` is true |

The response constructor accepts statuses 200 through 599. It rejects header
injection, duplicate names regardless of case, server-owned framing headers,
and a body for status 204, 205, or 304. Header names are stored in lowercase.
JSON encoding failures throw. HTML is not escaped automatically; untrusted text
needs escaping at the rendering boundary with `htmlspecialchars`.

Bodies are buffered. Repeated `Set-Cookie` headers and streaming are not supported.

## HTTP errors

`new HttpError(int $status)` accepts a status from 400 through 599. An invalid
status throws `InvalidArgumentException`. A translated error response contains
only the status, such as `{"error":400}`.

## Routing performance

Literal routes use a direct lookup. Parameter routes are indexed at construction
by their segment count and fixed prefix. Dispatch checks only those candidates,
in registration order. Patterns sharing the same prefix and depth still require
a linear scan. Reusing the application avoids rebuilding this index in a
persistent runtime. Mutable handler dependencies still need their own lifecycle.

Run `php bin/benchmark` in the source checkout to measure dispatch with 10,
100, and 1,000 parameter routes. It reports microseconds per request after
warmup, using the median of five samples. The fixture uses distinct prefixes;
it measures routing in memory, not server throughput or application capacity.

## Server configuration

Only the application's public directory belongs under the web root. Keep
credentials, dependencies, source, and runtime data outside that directory.
TLS, timeouts, request limits, and private-path denial are server responsibilities.
PHP's built-in server is for local development.
