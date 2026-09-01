# People Portal

Laravel 12 API with a Vue 3 SPA front end (Vue Router + Tailwind CSS, bundled
with Vite). Covers attendance, leave and leave credits, overtime, shifts and
shift-change requests, standups, teams, projects, clients, HR announcements and
employee regularization, with role-based access control.

## Requirements

- PHP 8.2+ (tested on 8.5)
- Composer
- Node.js 20+
- MySQL or MariaDB (XAMPP and Laragon both bundle it)

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

On XAMPP and Laragon `root` already exists with a blank password, so all you
have to do is create the database.

**Option A — with phpMyAdmin** (easiest; bundled with both XAMPP and Laragon)

1. Start MySQL from the XAMPP or Laragon control panel.
2. Open <http://localhost/phpmyadmin> and connect as `root`.
3. Create a new database named `peopleportal`.
4. Set its collation to `utf8mb4_unicode_ci`.

TablePlus, MySQL Workbench, DBeaver or your IDE's database panel all work the
same way if you prefer one of those.

**Option B — with the command line**

Open a MySQL shell as `root`:

| Setup | Command |
| --- | --- |
| Laragon | `mysql -u root` (from the Laragon menu's terminal) |
| XAMPP | `C:\xampp\mysql\bin\mysql -u root` |

Then create the database:

```sql
CREATE DATABASE IF NOT EXISTS peopleportal
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

That is all most setups need — `root` with a blank password is fine for local
development. If MySQL came from the official MySQL Installer rather than XAMPP
or Laragon, `root` has the password you chose during setup; use that in step 3.

To create a dedicated user instead — worth doing if the database is shared or
reachable beyond your machine — also run:

```sql
CREATE OR REPLACE USER 'peopleportal'@'localhost' IDENTIFIED BY 'CHANGE_ME';
CREATE OR REPLACE USER 'peopleportal'@'127.0.0.1' IDENTIFIED BY 'CHANGE_ME';
GRANT ALL PRIVILEGES ON peopleportal.* TO 'peopleportal'@'localhost';
GRANT ALL PRIVILEGES ON peopleportal.* TO 'peopleportal'@'127.0.0.1';
```

Replace `CHANGE_ME` with a password of your own. Both host entries are needed:
`DB_HOST=127.0.0.1` connects over TCP, but MySQL resolves the loopback address
back to the hostname `localhost` when matching grants, so an account created for
only one of the two still gets access denied. Use `CREATE OR REPLACE USER` and
not `CREATE USER IF NOT EXISTS` — the latter silently keeps the existing
password if the account is already there, which looks like a wrong-password
error later.

### 3. Configure the app

```bash
copy .env.example .env    # macOS/Linux: cp .env.example .env
php artisan key:generate
```

The template ships with the credentials a default XAMPP or Laragon install
uses — `root` with no password:

```
DB_DATABASE=peopleportal
DB_USERNAME=root
DB_PASSWORD=
```

If that is your setup, there is nothing to change here. Otherwise replace them
with whatever step 2 left you with — the dedicated `peopleportal` user and its
password, or an admin account that has a password.

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
| `Access denied ... (using password: NO)` | `DB_PASSWORD` is empty in `.env` but the account has a password. Common on MySQL Installer setups, where `root` always has one. |
| `Access denied ... (using password: YES)` | Wrong password, or the user does not exist. Re-run the `CREATE OR REPLACE USER`/`GRANT` block from step 2. |
| `'mysql' is not recognized` | Use the full path, e.g. `C:\xampp\mysql\bin\mysql -u root`, or open the shell from the Laragon menu. |
| `Connection refused` | MySQL is not running. Start it from the XAMPP or Laragon control panel. |
| `[1698] Access denied for user 'root'@'localhost'` | You are on WSL, macOS or Linux, where packaged MariaDB authenticates `root` by operating system user and cannot be reached over TCP at all. Create the dedicated `peopleportal` user in step 2 and point `.env` at it. |

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
