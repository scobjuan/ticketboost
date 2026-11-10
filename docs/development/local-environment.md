# Local environment

Everything that needs PHP or GLPI runs in containers, so the host only needs:

- Node 24 and pnpm 12
- Podman with `podman-compose`, or Docker with Compose
  (run `make <target> COMPOSE="docker compose"` with Docker)
- `make` and `msgfmt` (gettext)

```bash
make install          # Node dependencies and git hooks
make up               # MariaDB, GLPI 11 on http://localhost:8011, GLPI 12 on http://localhost:8012
make install-plugin   # build, then install and activate TicketBoost in both
```

Default accounts: `glpi/glpi` (super-admin), `tech/tech` (technician), `normal/normal`,
`post-only/postonly` (self-service).

The repository is mounted at `/var/www/glpi/plugins/ticketboost` in every container:
rebuild with `make build` (or `pnpm dev` for watch mode) and reload the page.

| Target                            | Purpose                                                     |
| --------------------------------- | ----------------------------------------------------------- |
| `make down`                       | Stop the containers, keep data                              |
| `make reset`                      | Stop and delete all data (fresh GLPI on the next `make up`) |
| `make logs`                       | Follow GLPI logs                                            |
| `make lint-php` / `make test-php` | PHP tooling inside the official GLPI CI image               |
| `make help`                       | Every target                                                |
