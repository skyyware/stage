# HTTP reference

`Application` accepts `Route` objects. Each route has an uppercase method, an
literal encoded path or a named path pattern, and a callable accepting `Request`
and returning `Response`. `Route::get` constructs a GET route. Duplicate methods
for the same path shape fail at startup, including `/{id}` and `/{slug}`.

Named parameters occupy a complete segment, as in `/pages/{slug}`. A parameter
name starts with a letter and contains letters, digits, or underscores. Names
must be unique within the route. A matched request exposes values through
`$request->parameters['slug']`. Values are decoded once. Empty values, decoded
slashes, backslashes, and control characters do not match.

Literal paths take priority regardless of registration order. Other overlapping
patterns use registration order. The matched path determines allowed methods;
a method mismatch does not fall through to a less specific route. Parameter
values remain untrusted input. Validate their domain meaning in the application.

`handle(Request): Response` runs in memory and does not send headers or output.
It returns 404 for an unknown path and 405 with `Allow` for an unsupported
method. OPTIONS returns 204 for a known path. HEAD uses a registered HEAD route
or the GET route. A HEAD response retains its representation in memory.

`run(int $maxBodyBytes = 1048576): void` reads PHP's request, dispatches, and
sends the response. It suppresses body output for HEAD. It translates
`HttpError` into a JSON error with the HTTP status. An unexpected exception
produces a generic 500 and logs its class, without its message or request data.
Unexpected exceptions propagate from `handle` so an embedding adapter can
provide its own reporting policy.

`Request` holds `method`, `path`, `body`, `headers`, the raw `query` string, and
matched `parameters`. `withParameters` returns a new request. Dispatch replaces
any pre-existing parameter map with the values from the matched route.
`fromGlobals` reads at most the body limit plus one byte and rejects oversized
input with 413. Headers read from PHP are lowercase. Proxy headers never set
identity or trust. Paths remain encoded and are not normalized or decoded.
`json()` returns a decoded JSON value or throws `HttpError(400)`. The application
must still validate the value's shape and domain rules.

`Response::text`, `Response::html`, and `Response::json` set the content type.
JSON encoding failures throw. HTML is not escaped automatically. Escape
untrusted text at the rendering boundary with `htmlspecialchars`.

The response constructor accepts a body, status, and string-valued headers.
It rejects header injection, duplicate names regardless of case, server-owned
framing headers, and a body for status 204, 205, or 304. `send(bool $head = false)`
writes the response and calculates its content length. Repeated `Set-Cookie`
headers and streaming are not supported in this version.

Only the application's public directory belongs under the web root. Keep
credentials, dependencies, source, and runtime data outside that directory.
Configure TLS, timeouts, request limits, and private-path denial in the server.
