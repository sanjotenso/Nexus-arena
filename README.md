# NEXUS ARENA

A modern gaming tournament platform built with PHP and SQLite.

## Features
- User registration and login
- Player dashboard, profiles, teams, and notifications
- Tournament creation, browsing, and registration
- Tournament brackets and match schedules
- Leaderboards and rankings
- Admin dashboard
- Search and JSON API endpoints

## Run locally with XAMPP

1. Link or copy this project into XAMPP's `htdocs` as `Nexus`. For example, the project should be available at `C:\xampp\htdocs\Nexus\index.php`.
2. In XAMPP's `php.ini`, make sure `pdo_sqlite` is enabled.
3. Start Apache in the XAMPP Control Panel.
4. Open `http://localhost/Nexus/`.

The included `.htaccess` routes application pages through PHP. XAMPP Apache must have `mod_rewrite` enabled and permit `.htaccess` overrides for this directory.

The application creates or uses the SQLite database at `nexus-arena/nexus_arena.db` and seeds sample tournaments on first run. The development admin account is `admin` / `admin123`; change or remove it before deployment.

## Run with Docker

From the repository root:

```sh
docker build -f nexus-arena/Dockerfile -t nexus-arena .
docker run --rm -p 8888:80 nexus-arena
```

Then open `http://localhost:8888`.

## Production Notes

- Use HTTPS and configure secure session cookies.
- Change the seeded admin password before exposing the application.
- Back up the SQLite database; consider PostgreSQL if you need multi-instance deployment.