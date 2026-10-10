# clamp — a containerized LAMP study environment

Linux, Apache, MariaDB and PHP in four small services orchestrated by one
`compose.yml` file. Made for learning: put your PHP files in `./www`, open
`http://localhost:8080`, and see the result immediately.

```
your browser
   │ localhost:8080            localhost:8081
   ▼                           ▼
┌─────────────┐             ┌──────────┐
│ web         │  hostname   │ db       │
│ Apache+PHP  │ ── "db" ──► │ MariaDB  │
│ (./www)     │             │ (volume) │
└─────────────┘             └──────────┘
                             ▲
                             │ hostname "db"
                        ┌──────────┐
                        │ adminer  │
                        └──────────┘
```

## Quick start

```bash
cp .env.example .env   # first time only: set ports and database credentials
docker compose up -d --build
```

| URL | What |
| --- | --- |
| <http://localhost:8080> | Apache serving `./www` (your PHP experiments) |
| <http://localhost:8081> | Adminer — browse the database (server `db`, credentials in `.env`) |

Stop everything with `docker compose down` (add `-v` to also delete the
database contents).

## Files

| Path | Purpose |
| --- | --- |
| `compose.yml` | Defines the `web`, `db` and `adminer` services and how they connect |
| `.env.example` | Template for the real `.env` (ports, DB credentials); the `.env` you create is git-ignored |
| `php/Dockerfile` | PHP 8.5 + Apache image, plus the MySQL drivers for PDO |
| `php/conf.d/dev.ini` | Shows all PHP errors in the page (study setting) |
| `www/` | Your code. Bind-mounted into the container as `/var/www/html` |
| `www/index.php` | Smoke test: checks PHP, Apache and the database connection |

## How it works

- **Bind mount** — `./www` on your machine is `/var/www/html` inside the
  `web` container. Editing a file does not require any rebuild: just reload
  the page.
- **Environment variables** — `compose.yml` reads `${...}` values from
  `.env` and passes them into the containers. `index.php` reads the DB
  credentials with `getenv()`, so no password lives in the code.
- **Internal network** — containers talk to each other by service name.
  From PHP, the database host is simply `db`; MariaDB is not reachable
  from outside the compose network.
- **Volume** — `db-data` keeps the database contents across
  `docker compose down`. Only `down -v` erases them.
- **Healthcheck** — `depends_on` with `condition: service_healthy` makes
  `web` and `adminer` wait until MariaDB is really accepting connections.

## Everyday commands

```bash
docker compose up -d --build      # start (and rebuild images if needed)
docker compose ps                 # state of the containers
docker compose logs -f web        # follow Apache/PHP logs
docker compose logs db            # database logs
docker compose exec web bash      # shell inside the PHP container
docker compose exec db mariadb -u clamp -p clamp   # SQL prompt (password: from .env)
docker compose down               # stop and remove containers (data kept)
docker compose down -v            # ...and delete the database volume
```

## Talking to the database from PHP

The `web` container already receives `DB_HOST`, `DB_NAME`, `DB_USER` and
`DB_PASSWORD` — see `www/index.php` for a working PDO example. A minimal
connection looks like:

```php
$pdo = new PDO(
    'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME') . ';charset=utf8mb4',
    getenv('DB_USER'),
    getenv('DB_PASSWORD'),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
```

## Running this on Debian trixie

The images themselves are Debian-based (`php:8.5-apache`, `mariadb:11.8`),
so the stack behaves the same on macOS and on a Debian host. On Debian you
only need Docker Engine and the Compose v2 plugin (`docker compose`, not the
old `docker-compose` Python tool); the commands in this README stay
unchanged. If you want the site on the standard port, set `WEB_PORT=80` in
`.env`.

## Troubleshooting

- **Port already in use** — change `WEB_PORT` / `ADMINER_PORT` in `.env`,
  then run `docker compose up -d` again.
- **Database connection failed** on the first run — MariaDB initializes the
  data directory on first start and can take a few seconds; the `db`
  container must show `healthy` in `docker compose ps`. If it is not, read
  `docker compose logs db`.
- **Changed `compose.yml`, `.env` or `php/Dockerfile`?** — `docker compose up -d`
  applies configuration changes; add `--build` when the Dockerfile changed.
- **Start from a clean database** — `docker compose down -v` then
  `docker compose up -d --build`.
