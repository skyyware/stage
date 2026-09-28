# Contribute to Stage

Install PHP 8.4 and Composer. Run `composer install`, then `composer check`.
The check validates package metadata, types, behavior, and architecture rules.

Start a change with a concrete caller and a failing behavior. Prefer deleting
unnecessary work to adding another abstraction. Keep state and authorization
with their owning feature. Explain public behavior in the documentation and
keep the examples executable.

The API is experimental. Record breaking changes in the same change as the
implementation. Add no compatibility alias without a real caller needing it.
Do not include secrets, personal data, or private source material in changes.

Repository access is currently limited. Public contribution channels will be
established when the source is released.
