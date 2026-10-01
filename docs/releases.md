# Release a Stage package

This is the manual maintainer workflow for `skyyware/stage`, `skyyware/stage-cms`,
`skyyware/stage-chat`, and `skyyware/stage-chat-codex`. Each repository owns its
version and checks. Application repositories are released separately.

## Prepare the version

1. Inspect the working tree, upstream, and latest remote tags. Preserve unrelated work.
2. Check the README, examples, API references, contribution guide, and agent instructions against the proposed code.
3. Check licenses and tracked files for credentials, private data, runtime, and source material without redistribution rights.
4. Choose a semantic version and add its behavior, compatibility, and upgrade notes to the changelog. Before 1.0, use a minor version for incompatible API changes and a patch for compatible fixes.
5. Run `composer install` and `composer check` in the package checkout. Run the package-specific checks listed in its contribution or release guide.
6. Review the complete diff, stage explicit paths, commit the coherent change, and push to the configured upstream.

Keep GitHub Actions, CI/CD, and automatic dependency jobs disabled. Local checks
are required even when no pipeline runs. Do not add or change Packagist hooks as
a side effect of a release; inspect existing hooks separately.

## Publish the tag and release

1. Check that the version does not already exist locally, on GitHub, or on Packagist. Never move or replace a released tag.
2. Create an annotated `vX.Y.Z` tag at the reviewed commit and push that exact tag.
3. Create a GitHub Release for the existing tag. Include the installation command, observable changes, upgrade requirements, checks performed, and material limits. Use `gh release create` with `--verify-tag` and `--notes-file` when using the CLI.
4. Verify the remote tag's commit and the release's tag, repository, and published state. A pushed tag alone is not a completed release.
5. Register a new package or use **Update** on its existing Packagist page with the approved maintainer account. Use the exact public GitHub repository URL. Do not request new permissions, create credentials, or add automation to refresh a package.
6. Check `https://repo.packagist.org/p2/skyyware/PACKAGE.json`. Confirm that the new version resolves to the released commit.

Changing a repository from private to public needs maintainer authority and a
review of every branch, tag, release, and reachable history that becomes public.
A concrete disclosure concern holds that repository. Removing a file from the
latest commit does not remove it from history; do not rewrite history to pass
the review.

## Verify a new consumer

Use a disposable directory and an empty `COMPOSER_HOME` and cache. Supply no
Composer authentication, private VCS repository, local path repository, or
application checkout. Install the exact version from Packagist with plugins
disabled. Libraries use `composer require`; the standalone CMS also uses
`composer create-project`.

Inspect the consumer's lockfile. Verify the package version, source commit,
production dependencies, and license. Execute its documented example or a
complete synthetic workflow through the installed package. A connector test
can use the local fake CLI; do not make a paid provider call merely to check
distribution. Remove disposable credentials and runtime after the check.

A release is complete only when documentation, native checks, GitHub Release,
Packagist metadata, and the fresh consumer agree on the same version and commit.
Report publication separately from an application's deployment or live acceptance.
