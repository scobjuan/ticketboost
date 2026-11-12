# Architecture decision records

One decision per file. Never edit an accepted ADR to change its decision: write a new one with `status: superseded-by NNNN` on the old one. Template: `.agents/templates/adr.md`.

| ADR                                               | Title                                                             | Status   |
| ------------------------------------------------- | ----------------------------------------------------------------- | -------- |
| [0001](0001-glpi-version-support.md)              | Support GLPI 11.x and 12.x only                                   | accepted |
| [0002](0002-plugin-endpoints-native-classes.md)   | Run actions through plugin endpoints that use native GLPI classes | accepted |
| [0003](0003-leader-key-tap-and-release.md)        | Leader key: tap-and-release Ctrl opening a which-key panel        | accepted |
| [0004](0004-circular-navigation-with-fallback.md) | Circular next/previous over the GLPI session list with a fallback | accepted |
| [0005](0005-configuration-layers.md)              | Configuration: admin defaults, profile right, user overrides      | accepted |
| [0006](0006-admin-configurable-close.md)          | Close action behavior is chosen by the admin                      | accepted |
| [0007](0007-typescript-without-framework.md)      | Frontend in TypeScript without a framework, bundled with Vite     | accepted |
| [0008](0008-testing-and-coverage-gates.md)        | Testing stack and blocking coverage gates                         | accepted |
| [0009](0009-ci-on-official-glpi-image.md)         | CI on the official GLPI CI image with project-owned workflows     | accepted |
| [0010](0010-containerized-local-environment.md)   | Local environment fully in containers                             | accepted |
| [0011](0011-trunk-based-release-please.md)        | Trunk-based flow and release-please with the owner's token        | accepted |
| [0012](0012-agent-agnostic-workflow.md)           | Agent-agnostic AI workflow                                        | accepted |
| [0013](0013-mkdocs-documentation.md)              | Documentation with MkDocs Material on GitHub Pages                | accepted |
| [0014](0014-name-and-license.md)                  | Name TicketBoost and license GPLv3+                               | accepted |
| [0015](0015-gettext-translations.md)              | Translations with gettext and regional copies                     | accepted |
| [0016](0016-ctrl-enter-complements-glpi.md)       | Ctrl+Enter complements GLPI's native submit shortcut              | accepted |
| [0017](0017-binding-notation.md)                  | Binding notation with physical keys and typed characters          | accepted |
| [0018](0018-request-security-and-panel-scope.md)  | Request security, self-service rights and panel scope             | proposed |
