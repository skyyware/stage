# Working on Stage

Read README.md and docs/design.md. Keep the core independent of applications.
Pass dependencies through constructors, validate input into typed values, and
use interfaces between features. Public docs describe behavior with runnable
examples. Keep private research out of them.

Run composer check after meaningful changes. Test denied and malformed calls,
not only successful calls. Add no explanatory source comments or TODOs.
PHPDoc consumed by static analysis and required legal notices are allowed.

Never commit credentials, runtime data, dependencies, or private records.

This repository is a public MIT package. Follow docs/releases.md for every
version: local checks, an immutable tag, a GitHub Release, Packagist availability,
and a fresh Composer consumer. Keep Actions and dependency automation disabled.

For the public Stage packages listed in docs/releases.md, an authorized new-
version delivery includes those publication steps without another routine
approval. Changes to visibility, unrelated repositories, new access or
credentials, and automation need their own maintainer instruction. If a required
check or existing access is missing, report that limit and finish independent work.

Stage maintainer work defaults to GPT-6.1 Sol. For future selections, use the
best current workhorse below the most capable frontier tier. Verify the current
catalogue before changing that choice. This is a maintainer preference, not a
framework dependency; applications choose their own providers and models.
