---
title: Support a new GLPI version
when: GLPI publishes a new major or minor version, or a version leaves support.
---

# Support a new GLPI version

1. Check release notes and the developer changelog for plugin API changes (hooks,
   `public/` serving, CSRF, rights, Twig).
2. Update `PLUGIN_TICKETBOOST_MAX_GLPI_VERSION` in `setup.php` and the
   `<compatibility>` entries in `plugin.xml`.
3. Add the runtime service to `compose.yaml` (new port), a `toolsNN` service with the
   official CI image tag, Make variables and a Playwright project.
4. Update the CI matrix in `.github/workflows/ci.yml` using the official PHP/DB matrix
   (`generate-ci-matrix.yml` in glpi-project/plugin-ci-workflows) as reference.
5. Run `make check` and the full e2e suite on every supported version.
6. Dropping a version is a breaking change (`feat!:`) and needs an ADR.
