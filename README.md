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

**1. Install dependencies**

```bash
composer install
npm install
```

**2. Create the database and its user**

Pick a password and use the same one in both this step and step 3.

```bash
sudo mysql <<'SQL'
CREATE DATABASE IF NOT EXISTS peopleportal
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'peopleportal'@'localhost' IDENTIFIED BY 'CHANGE_ME';
CREATE USER IF NOT EXISTS 'peopleportal'@'127.0.0.1' IDENTIFIED BY 'CHANGE_ME';
GRANT ALL PRIVILEGES ON peopleportal.* TO 'peopleportal'@'localhost';
GRANT ALL PRIVILEGES ON peopleportal.* TO 'peopleportal'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL
```

Both host entries are needed. `DB_HOST=127.0.0.1` connects over TCP, but MySQL
resolves the loopback address back to the hostname `localhost` when matching
grants, so an account created for only one of the two still gets access denied.

**3. Configure the app**

```bash
cp .env.example .env
php artisan key:generate
```

Set `DB_PASSWORD` in `.env` to the password from step 2. `.env.example` already
has the other `DB_*` values filled in; change `DB_DATABASE` / `DB_USERNAME` only
if you used different names. Quote the password if it contains `#`, spaces or
quotes — an unquoted `#` starts a comment.

Verify the credentials before migrating:

```bash
mysql -h 127.0.0.1 -u peopleportal -p'CHANGE_ME' peopleportal -e "SELECT 1;"
```

**4. Build the schema**

```bash
php artisan migrate:fresh --seed
```

`migrate:fresh` drops every table first, so use plain `migrate` on a database
whose data you want to keep.

### Troubleshooting

| Error | Cause |
| --- | --- |
| `General error: 1 near "MODIFY": syntax error` | Running against SQLite. Check `DB_CONNECTION=mysql` in `.env`, then `php artisan config:clear`. |
| `Access denied ... (using password: NO)` | `DB_PASSWORD` is empty in `.env`. |
| `Access denied ... (using password: YES)` | Wrong password, or the user does not exist — re-run step 2. |
| `Unknown database 'peopleportal'` | Step 2 was skipped, or `DB_DATABASE` does not match the database you created. |

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
