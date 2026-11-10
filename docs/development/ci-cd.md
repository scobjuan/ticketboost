# CI/CD and releases

## Workflows

| Workflow      | Trigger                         | Jobs                                                                                                                 |
| ------------- | ------------------------------- | -------------------------------------------------------------------------------------------------------------------- |
| `ci.yml`      | Pull request, push to `main`    | Conventions (commitlint, rule guards), frontend, PHP matrix, e2e, docs build, workflow lint, release archive dry run |
| `release.yml` | Push to `main`                  | release-please, release archive upload, docs deployment                                                              |
| `docs.yml`    | Called by `release.yml`, manual | Build and deploy the documentation to GitHub Pages                                                                   |

PHP matrix: GLPI 11.0.x on PHP 8.2 and 8.5, GLPI 12.0.x on PHP 8.3 and 8.5, on MySQL
and MariaDB.

## Versioning

[Semantic Versioning](https://semver.org) computed by release-please from
[Conventional Commits](https://www.conventionalcommits.org): `fix` gives a patch, `feat`
a minor, `!` or `BREAKING CHANGE` a major (minor while the version is below 1.0.0).
release-please keeps a release pull request open with the CHANGELOG; merging it tags the
version, creates the GitHub release and attaches `ticketboost-X.Y.Z.tar.bz2`.

## One-time repository setup (owner)

1. Create a fine-grained personal access token for this repository with _Contents_ and
   _Pull requests_ read and write, and save it as the Actions secret
   `RELEASE_PLEASE_TOKEN`. Releases are then authored by the owner.
2. Settings > Pages > Source: **GitHub Actions**.
3. Settings > General > Pull requests: allow **squash merging** only, default commit
   message **pull request title**.
4. Branch protection (or a ruleset) on `main`: require a pull request, require status
   checks (`Conventions and project rules`, `Frontend (lint, types, unit tests, bundle)`,
   the four `PHP (...)` jobs, `E2E (Playwright on GLPI 11 and 12)`,
   `Documentation build (strict)`, `Workflow lint`, `Release archive dry run`), require
   linear history, block force pushes.
5. The first release is forced to 0.1.0 with a commit footer `Release-As: 0.1.0`.
