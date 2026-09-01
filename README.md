# People Portal

Laravel 12 API with a Vue 3 SPA front end (Vue Router + Tailwind CSS, bundled
with Vite). Covers attendance, leave and leave credits, overtime, shifts and
shift-change requests, standups, teams, projects, clients, HR announcements and
employee regularization, with role-based access control.

## Requirements

- PHP 8.2+ (tested on 8.5)
- Composer
- Node.js 20+
- MySQL

## Setup

MySQL or MariaDB is required — SQLite will not work. Two migrations alter the
`leaves` and `overtimes` status columns with raw MySQL DDL
(`ALTER TABLE ... MODIFY COLUMN ... ENUM`), which SQLite cannot parse.

### 1. Install dependencies

```bash
composer install
npm install
```

### 2. Create the database

`.env` only tells Laravel *how to connect* — it cannot create the database, and
`php artisan migrate` will not create one either. So the database has to exist
before you migrate. You need two things by the end of this step:

- a database named `peopleportal`
- a username and password that can access it

Use whichever route below matches your setup. They all achieve the same thing.

**Option A — with a GUI** (easiest if you would rather not use a terminal)

Open the database tool you already have — phpMyAdmin (bundled with XAMPP and
Laragon at <http://localhost/phpmyadmin>), TablePlus, MySQL Workbench, DBeaver
or your IDE's database panel — then:

1. Connect as the admin account (usually `root`).
2. Create a new database named `peopleportal`.
3. Set its collation to `utf8mb4_unicode_ci`.

That is all you need. In step 3, use the same admin username and password you
just connected with.

**Option B — with the command line**

First open a MySQL shell as an admin user. The command differs by platform:

| Setup | Command |
| --- | --- |
| XAMPP (Windows) | `C:\xampp\mysql\bin\mysql -u root` |
| Laragon (Windows) | `mysql -u root` |
| macOS (Homebrew) | `mysql -u root` |
| Linux (Fedora, Ubuntu, Debian) | `sudo mysql` |

Linux is the odd one out: its packaged MariaDB authenticates `root` by operating
system user rather than by password, so the shell has to be opened with `sudo`.
On XAMPP and Laragon `root` has a blank password by default and `sudo` does not
apply. If `mysql` is not a recognised command on Windows, use the full path from
the XAMPP row, or open the shell from the Laragon menu.

Then create the database:

```sql
CREATE DATABASE IF NOT EXISTS peopleportal
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

You can stop here and use your admin account in step 3 — fine for local
development. To create a dedicated user instead, which is worth doing if the
database is shared or reachable beyond your machine, also run:

```sql
CREATE USER IF NOT EXISTS 'peopleportal'@'localhost' IDENTIFIED BY 'CHANGE_ME';
CREATE USER IF NOT EXISTS 'peopleportal'@'127.0.0.1' IDENTIFIED BY 'CHANGE_ME';
GRANT ALL PRIVILEGES ON peopleportal.* TO 'peopleportal'@'localhost';
GRANT ALL PRIVILEGES ON peopleportal.* TO 'peopleportal'@'127.0.0.1';
FLUSH PRIVILEGES;
```

Replace `CHANGE_ME` with a password of your own. Both host entries are needed:
`DB_HOST=127.0.0.1` connects over TCP, but MySQL resolves the loopback address
back to the hostname `localhost` when matching grants, so an account created for
only one of the two still gets access denied.

### 3. Configure the app

```bash
cp .env.example .env
php artisan key:generate
```

Then set the credentials in `.env` to whatever step 2 left you with:

```
DB_DATABASE=peopleportal
DB_USERNAME=peopleportal   # or root, if you used the admin account
DB_PASSWORD=               # blank is normal for XAMPP/Laragon root
```

Quote the password if it contains `#`, spaces or quotes — an unquoted `#` starts
a comment. Verify the credentials before migrating:

```bash
php artisan db:show
```

### 4. Build the schema

```bash
php artisan migrate:fresh --seed
```

`migrate:fresh` drops every table first, so use plain `migrate` on a database
whose data you want to keep.

### Troubleshooting

| Error | Cause |
| --- | --- |
| `General error: 1 near "MODIFY": syntax error` | Running against SQLite. Check `DB_CONNECTION=mysql` in `.env`, then `php artisan config:clear`. |
| `Unknown database 'peopleportal'` | Step 2 was skipped, or `DB_DATABASE` does not match the database you created. |
| `Access denied ... (using password: NO)` | `DB_PASSWORD` is empty in `.env` but the account has a password. |
| `Access denied ... (using password: YES)` | Wrong password, or the user does not exist. |
| `Access denied for user 'root'@'localhost'` when opening the MySQL shell on Linux | Use `sudo mysql`, not `mysql -u root`. |
| `'mysql' is not recognized` on Windows | Use the full path, e.g. `C:\xampp\mysql\bin\mysql -u root`. |
| `Connection refused` | The MySQL service is not running. Start it from the XAMPP or Laragon control panel, or `sudo systemctl start mariadb` on Linux. |

Config is cached separately from `.env`, so run `php artisan config:clear` after
any change to `.env` that does not seem to take effect.

## Running locally

```bash
php artisan serve   # API + SPA on http://127.0.0.1:8000
npm run dev         # Vite dev server (separate terminal)
npm run build       # production assets
```

## Signing in

Sign in with an email address and password. `POST /api/login` returns a Sanctum
token, throttled to 5 attempts a minute per IP.

`TestUserSeeder` creates one account per role, all sharing the password
`password` (override with `SEED_PASSWORD` in `.env`):

| Email | Role |
| --- | --- |
| `superadmin@peopleportal.test` | Super Admin |
| `admin@peopleportal.test` | Admin |
| `hr@peopleportal.test` | HR |
| `manager@peopleportal.test` | Manager |
| `teamlead@peopleportal.test` | Team Lead |
| `employee1@peopleportal.test`, `employee2@peopleportal.test` | Employee |

Passwords for real accounts are set by an admin from **User Management**. A user
with no password cannot sign in.

### Magic-link login

The old passwordless email-link flow is disabled but still present:
`AuthController::sendLoginLink`/`verifyLoginLink`, `LoginLinkMail`,
`LoginLinkVerify.vue` and the `login_tokens` table are all intact. To re-enable,
uncomment the two routes in `routes/api.php` and the two guest routes in
`resources/js/router/index.js`.

## Seed data

Seeding creates roles and permissions, teams, shifts, leave types and the test
accounts above — nothing else. Clients, projects and project types start empty
and are created through the app.

One caveat: `project_types` has no management screen, and creating a project
requires an existing project type (`StoreProjectRequest`), so a row has to be
inserted into `project_types` directly before the Projects page can be used.

## Configuration

| Variable | Purpose |
| --- | --- |
| `APP_NAME` | Application name shown in the UI |
| `APP_URL` | Base URL of the app |
| `SEED_PASSWORD` | Password given to seeded test accounts (default `password`) |
| `BRAND_LOGO_URL` | Logo used in outbound RingCentral notifications (optional) |

## Notifications

RingCentral notifications are opt-in per user and per project via the `glip_url`
column. It seeds as `null`, which disables them — callers skip when no webhook is
set. Never commit real webhook URLs: they are live credentials.

## Deployment

`.github/workflows/deploy.yml` deploys to the app server over SSH on every push
to `dev`. The dependency install and asset build were manual stages in the old
GitLab pipeline and are now `workflow_dispatch` checkboxes.

Required repository secrets:

| Secret | Purpose |
| --- | --- |
| `DEPLOY_SSH_KEY` | Private key that can SSH into the app server |
| `DEPLOY_HOST` | App server hostname or IP |
| `DEPLOY_USER` | SSH user on the app server (also owns the app files) |
| `DEPLOY_PATH` | Absolute path to the app checkout on the server |
| `DEPLOY_GIT_SSH_KEY` | Path *on the server* to the key that can `git pull` this repo |

Optional repository variables:

| Variable | Default | Purpose |
| --- | --- | --- |
| `PHP_BINARY` | `/usr/bin/php82` | PHP selected via `alternatives` |
| `NODE_VERSION` | `20.19.1` | nvm version used by the build jobs |

## Repository

```
git remote add origin git@github.com:kimcwgp/people-portal.git
```
