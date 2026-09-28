# Working on Stage

Read README.md and docs/design.md. Keep the core independent of applications.
Use explicit constructors, typed input, and feature contracts. Public docs
describe behavior and executable examples without private research context.

Run composer check after meaningful changes. Test denied and malformed calls,
not only successful calls. Add no explanatory source comments or TODOs.
PHPDoc consumed by static analysis and required legal notices are allowed.

Never commit credentials, runtime data, dependencies, or private records.
Visibility changes and public releases require explicit maintainer authority.
