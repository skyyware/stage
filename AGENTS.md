# Working on Stage

Read README.md and docs/design.md. Keep the core independent of applications.
Use explicit constructors, typed input, and feature contracts. Public docs
describe behavior and executable examples without private research context.

Run composer check after meaningful changes. Test denied and malformed calls,
not only successful calls. Add no explanatory source comments or TODOs.
PHPDoc consumed by static analysis and required legal notices are allowed.

Never commit credentials, runtime data, dependencies, or private records.
Visibility changes and public releases require explicit maintainer authority.

This repository is a public MIT package. Follow docs/releases.md for every
version: local checks, an immutable tag, a GitHub Release, Packagist availability,
and a fresh Composer consumer. Keep Actions and dependency automation disabled.

Stage maintainer work defaults to GPT-6.1 Sol. For future selections, use the
best current workhorse below the most capable frontier tier. Verify the current
catalogue before changing that choice. This is a maintainer preference, not a
framework dependency; applications choose their own providers and models.
