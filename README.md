# NEXUS ARENA

NEXUS ARENA is a web application for organizing esports tournaments and player communities. Users can create accounts, join tournaments and teams, view brackets and match schedules, and track standings. Administrators can manage tournaments and users.

The primary, runnable web application is built with **PHP 8+**, **PDO SQLite**, and server-rendered PHP views, with JavaScript for interactive actions and a responsive CSS theme. A Python/Tornado implementation is also included under `assets/`; it is a separate implementation, not required to run the PHP application.

## Tech stack

| Area | Technology |
| --- | --- |
| Server-side application | PHP 8+ |
| Database access | PDO |
| Primary database | SQLite |
| User interface | PHP templates, HTML, CSS, and vanilla JavaScript |
| Web server | Apache/XAMPP, PHP's built-in development server, or Docker with Apache |
| Optional alternate implementation | Python and Tornado (`assets/app.py`) |

The PHP application is the primary setup described in this guide. The optional Python app is separate; it is not required to run the PHP site.

## Features

- Account registration, login, logout, and session-based authentication
- Player dashboard, profiles, teams, and notifications
- Tournament browsing, filtering, details, and player registration
- Tournament brackets, match schedules, and match updates
- Leaderboards and rankings
- Administrator pages and management endpoints
- JSON endpoints for tournament, team, search, and notification actions
- SQLite database initialized automatically on first run

## How the system works

1. The web server sends application requests to the project-root `index.php`. For local PHP development, `router.php` also serves static files from `assets/static/` and forwards application requests to `index.php`.
2. `index.php` starts a secure PHP session, loads the helpers, database model, and front controller, then dispatches the request.
3. `app/Controllers/FrontController.php` matches the URL and HTTP method to a page or API action. It checks the current session for protected pages and administrator-only actions.
4. `app/Models/Database.php` opens `assets/nexus_arena.db` through PDO SQLite. It creates the tables and seeds an administrator and sample tournaments when the database is empty.
5. `app/Views/` contains the page templates and shared layout. The layout loads the stylesheet and JavaScript from `assets/static/`.
6. The browser submits regular forms to the PHP routes. Interactive actions use JavaScript to call the JSON endpoints.

The SQLite database file is intentionally ignored by Git. A fresh clone creates it automatically when the PHP application is first opened. Keep backups of the database if it contains data you need.

### Main project layout

```text
app/
  Controllers/FrontController.php  Page and API routing, authentication, and actions
  Core/helpers.php                 Escaping, URL, redirect, JSON, and view helpers
  Models/Database.php              SQLite connection, schema initialization, and seed data
  Views/                           PHP page templates and shared layout
assets/
  static/css/style.css             Site styles
  static/js/app.js                 Browser interactions and JSON requests
  app.py                           Separate Python/Tornado implementation
  Dockerfile                       PHP/Apache Docker image configuration
  nexus_arena.db                   Local SQLite database (created on first run)
database/
  schema_mysql.sql                 Optional MySQL/MariaDB schema for reference/import
index.php                          PHP application entry point
router.php                         PHP development server router and static-file handler
.htaccess                          Apache rewrite and access rules
```

## Clone the repository

Install [Git](https://git-scm.com/downloads) for your operating system, open a terminal, and run the commands below.

### Windows (PowerShell)

```powershell
git clone https://github.com/sanjotenso/Nexus-arena.git
Set-Location Nexus-arena
```

### macOS or Linux (Terminal)

```sh
git clone https://github.com/sanjotenso/Nexus-arena.git
cd Nexus-arena
```

The repository uses the same Git commands on all operating systems. Windows paths use backslashes in PowerShell; macOS/Linux paths use forward slashes.

After cloning, install or enable PHP and the `pdo_sqlite` extension (see [Requirements](#requirements)), then follow [Run locally](#run-locally).

## Version control and getting updates

Git tracks source-code changes. The local SQLite database is ignored and is not cloned or uploaded as part of normal source changes.

Check the current branch and working-tree changes:

```sh
git status
git branch --show-current
```

Download and apply the latest committed changes for the branch you're on:

```sh
git pull
```

Before pulling, commit or back up local source changes you want to keep. To record your own changes, stage only the files you intend to include and commit them:

```sh
git add README.md
git commit -m "docs: update project setup and system guide"
```

Replace `README.md` with the paths you actually changed. Do not add the local database file to Git.

## Requirements

- PHP 8.0 or newer
- The `PDO` and `pdo_sqlite` PHP extensions
- Apache with `mod_rewrite` and `.htaccess` overrides enabled if using Apache/XAMPP
- Git to clone the repository

## Run locally

### PHP development server

From the cloned project root, start PHP's built-in development server:

```sh
php -S 127.0.0.1:8000 router.php
```

Open <http://127.0.0.1:8000>. The built-in PHP server is for local development, not production.

### XAMPP

1. Copy or clone the project into XAMPP's `htdocs` directory, for example `C:\xampp\htdocs\Nexus`.
2. Enable `pdo_sqlite` in XAMPP's `php.ini`.
3. Enable Apache `mod_rewrite` and allow `.htaccess` overrides for the project directory.
4. Start Apache from the XAMPP Control Panel.
5. Open <http://localhost/Nexus/>.

The first request creates `assets/nexus_arena.db` and initializes the tables and sample data. If the database does not contain a user, the development seed administrator is `admin` / `admin123`. Change this password before using the application outside local development.

## Run with Docker

From the project root:

```sh
docker build -f assets/Dockerfile -t nexus-arena .
docker run --rm -p 8888:80 nexus-arena
```

Open <http://localhost:8888>.

## Database

The PHP application uses SQLite at `assets/nexus_arena.db`. The database tables cover users, teams and team membership, tournaments and participants, matches, leaderboard entries, and notifications. SQLite schema initialization is handled by the PHP database model.

`database/schema_mysql.sql` is an optional MySQL/MariaDB schema for manual import through phpMyAdmin. The PHP application uses SQLite by default; importing the MySQL schema does not switch the app to MySQL.

## Included Python implementation

`assets/app.py` is a separate Tornado application that uses the same SQLite database filename. It is not needed for the PHP/XAMPP or Docker setup above. It requires Python and the Tornado package. The PHP application is the documented primary setup.

## Production notes

- Replace the seeded development administrator password and review seeded accounts before deployment.
- Set HTTPS and use secure session cookies in production.
- Configure a strong secret and production-safe settings if deploying the Python/Tornado implementation.
- Back up `assets/nexus_arena.db` regularly. The database file is excluded from Git and is not included in repository clones.
- Use a production web server configuration; do not expose PHP's built-in development server.
