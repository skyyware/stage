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

## Propose a change

Use [issues](https://github.com/skyyware/stage/issues) for reproducible bugs and
bounded proposals. Include the PHP version, smallest example, expected result,
and actual result. Discuss a new abstraction before implementing it.

Fork the repository, create a branch, and make one coherent change. Update
the relevant example and documentation. Run `composer check` and open a pull
request with the problem, resulting behavior, and checks performed.

People and agents can contribute. The contributor remains responsible for
understanding the change, testing it, and respecting the license of its sources.
Keep discussion specific and considerate. Review the work on its merits.

Report security problems through [private vulnerability reporting](https://github.com/skyyware/stage/security/advisories/new).
Do not put credentials, private data, or unpatched exploit details in an issue.
