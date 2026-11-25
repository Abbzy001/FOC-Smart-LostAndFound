# Faculty of Computing Smart Lost and Found

A PHP and MySQL web application for reporting and matching lost and found items within the Faculty of Computing.

## Local development with XAMPP

XAMPP's PHP 8.2 and MariaDB are sufficient for local development. The schema uses InnoDB, foreign keys, prepared statements and UTF-8 features supported by the bundled MariaDB. Docker Compose remains available when you need MySQL 8.0 specifically.

1. Start **MySQL** in the XAMPP Control Panel. The app uses XAMPP's PHP with PHP's local development server; Apache can remain running for other local sites.
2. Create the local environment file and database:

   ```powershell
   Copy-Item .env.example .env
   & 'C:\xampp\mysql\bin\mysql.exe' --user=root --execute="CREATE DATABASE IF NOT EXISTS smart_lost_found CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   ```

   If your XAMPP root account has a password, update `DB_PASSWORD` in `.env`.
3. Run the schema migrations:

   ```powershell
   & 'C:\xampp\php\php.exe' scripts\migrate.php
   ```

4. Start PHP's local development server from the project directory:

   ```powershell
   & 'C:\xampp\php\php.exe' -S 127.0.0.1:8080 -t public public\index.php
   ```

5. Open [http://localhost:8080](http://localhost:8080). The database health endpoint is [http://localhost:8080/health](http://localhost:8080/health).

Keep the PHP development server bound to `127.0.0.1`; it is not intended for public or production use.

## Local development with Docker

Start Docker Desktop, copy `.env.example` to `.env`, change the example Docker database passwords, then run:

```powershell
docker compose up --build -d
docker compose exec web php scripts/migrate.php
```

The Docker app is available at [http://localhost:8081](http://localhost:8081) by default, keeping it separate from the XAMPP development server on port 8080. Set `APP_PORT` in `.env` to change the Docker web port. Use `docker compose logs -f web db` to inspect service logs and `docker compose down` to stop the stack. The named database volume is preserved when the stack stops.

The `.env` file is ignored by Git. Never use development database credentials outside a local environment.

## Deploying to Railway

Railway builds the root `Dockerfile`; `compose.yaml` is for local development and does not create Railway's database service. Add a Railway MySQL service, then add reference variables to the PHP service using your database service's actual Railway service name:

```text
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}
```

Replace `MySQL` with the exact Railway service name if it differs. The application also accepts the `MYSQLHOST`, `MYSQLPORT`, `MYSQLDATABASE`, `MYSQLUSER`, and `MYSQLPASSWORD` environment variables directly.

After the MySQL service is ready and its variables are available to the web service, run the schema migrations once from the deployed application environment:

```sh
php scripts/migrate.php
```

The service must listen on the port Railway routes to it. This image runs Apache on port `80` (`EXPOSE 80`); configure Railway's service target port as `80` if automatic port detection does not select it. Deploy the Dockerfile change to resolve Apache's multiple-MPM startup error, then check the deployment logs and `/health` endpoint.

## Tests

Run the matching-engine unit checks with XAMPP's PHP:

```powershell
& 'C:\xampp\php\php.exe' tests\run.php
```

The migration runner can safely be run again; it applies only migrations that have not yet been recorded.

## Authentication and handover authorization

Account passwords authenticate users when they sign in. The separate four-digit PIN is used only to authorize the in-person item handover workflow. They must have separate fields, validation paths, and hashes; the handover PIN must never be accepted as an account password.

The first administrator account can be promoted after registration by running this statement through phpMyAdmin or the local MariaDB client, replacing the registration number:

```sql
UPDATE users SET role = 'admin' WHERE matric_number = 'CST/24/COM/12345';
```

Sign out and back in after changing the role so the new permission is loaded.

Only approved claim participants can see each other's account email addresses; public item listings never display contact details.

## Application structure

- `public/` — Apache document root and web entry point
- `src/` — application code
- `database/migrations/` — ordered database schema migrations
- `tests/` — automated tests
- `docker/` — Apache container configuration
- `scripts/migrate.php` — applies pending SQL migrations
