# Contribute to Stage

To build an application with Stage, start with
[the Composer installation guide](README.md#create-an-application).

To change the framework itself, install PHP 8.4 or later within PHP 8 and
Composer. Clone your fork, or use the upstream checkout for local inspection:

```sh
git clone https://github.com/skyyware/stage.git
cd stage
composer install
composer check
```

`composer check` validates package metadata, types, behavior, and architecture
rules in this checkout. It runs PHPStan, PHPUnit, and Deptrac. The architecture
check includes an intentionally forbidden import to prove the rule can fail.
These tools and scripts are not installed into applications that require Stage.

File-transfer tests require the `curl` command and permission to start a PHP
development server on a temporary loopback port. They transfer a 64 MiB fixture
with a 16 MiB PHP memory limit, check HEAD and denied uploads, and remove their
temporary files from `.runtime/`. Allow about 200 MiB of local scratch space.

To run the framework's HTTP example:

```sh
php -S 127.0.0.1:8080 -t examples examples/hello.php
```

Open [http://127.0.0.1:8080](http://127.0.0.1:8080) and expect
`{"hello":"world"}`. Press Ctrl+C to stop the server.

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

Run checks locally; repository automation is disabled. Maintainers use
[the release guide](docs/releases.md) when publishing a version.

Report security problems through [private vulnerability reporting](https://github.com/skyyware/stage/security/advisories/new).
Do not put credentials, private data, or unpatched exploit details in an issue.
