# Changelog

## 0.1.1 — 2026-09-28

- Index parameter routes by path depth and their fixed prefix. Dispatch skips
  unrelated paths while preserving literal priority, registration order,
  method handling, and parameter validation.
- Add a repeatable routing benchmark and tests for overlapping prefixes,
  encoded paths, empty segments, and trailing slashes.

No public API or dependency changes.

## 0.1.0 — 2026-09-28

First public MIT release.

- Immutable HTTP request and response values with a bounded input adapter.
- Literal routes and named path parameters such as `/pages/{slug}`.
- HEAD fallback, OPTIONS responses, and distinct 404 and 405 handling.
- Validated response headers and generic unexpected-error output.
- Explicit caller permissions and typed feature-composition examples.
- PHPStan, PHPUnit, and dependency-boundary checks on PHP 8.4 and 8.5.
- Contribution instructions, private security reporting, and agent guidance.

This release adds named routes to the private `0.1.0-alpha.1` foundation.
Literal routes keep their existing behavior. There are no production package
dependencies. The API remains experimental before 1.0.
