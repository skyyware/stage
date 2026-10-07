# Explicit application boundaries

A program receives input, applies a rule, and produces a result. A transport
such as HTTP converts external data into that input. Keeping those jobs separate
lets the same operation serve a web page, command, or agent tool.

Stage starts with PHP 8.4, immutable HTTP messages, a path router, and an
optional caller permission value. There are no production package dependencies.
PHPUnit, PHPStan, and Deptrac run during development only.

## Own a rule in one place

An application composes ordinary PHP objects through constructors. An operation
accepts typed input and checks the caller before protected reads or writes.
Authentication adapters establish that caller from trusted credentials.
Never accept permissions directly from request data or model output.

Features keep their implementation together. A feature exposes an interface
under `Contract` when another feature needs it. The architecture check permits
that dependency and rejects access to the implementation.

`examples/Features/Counter` owns its count and input validation. `Reports`
reads through `Counter/Contract/ReadCount`. The example uses memory only and
does not demonstrate persistence, concurrent updates, or tenant isolation.

## Choose explicit composition

An automatic container could discover services and routes through attributes.
That shortens registration while making construction order and dependencies
less visible. A mandatory operation bus could centralize dispatch, but adds
indirection to every call. This version uses explicit construction and normal
method calls. Add an abstraction only when a real application exposes a need.

HTTP owns protocol handling. Application code owns business errors, transactions,
storage, and identity. Stage does not introduce an ORM, queue, template language,
agent provider, or global application registry.

## Grow from evidence

Start with the smallest working application. Measure a real bottleneck before
optimizing it. Define the failure case before adding retries or concurrency.
Automate repeatable checks after the behavior is understood.

Ordinary HTTP messages have buffered bodies with a default raw request limit
of 1 MiB. PHP handles multipart uploads before routing; `UploadedFile` validates
one native upload and its byte limit. `FileResponse` opens a regular file before
dispatch completes and streams it in bounded chunks. The application owns file
access, storage, retention, MIME policy, and caching. Tests transfer files up to 80 MiB through
native PHP HTTP with a 16 MiB PHP memory limit; they do not establish production
throughput or total process memory.

Literal paths and named path segments are supported. There is no catch-all
wildcard or arbitrary response stream. Inline files support a single byte range;
attachment downloads always send the full file. Each response
header has one string value. Stage does not implement PSR-7 or PSR-15. Cookie
handling, queues, worker lifecycle, and distributed transactions need separate
designs and tests before they are advertised.

The long-term goal is a common foundation for small services and large systems.
The initial website proves package consumption and HTTP delivery. It does not
establish capacity, availability, or suitability for safety-critical systems.
