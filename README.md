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

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate

# configure DB_* in .env, then:
php artisan migrate:fresh --seed
```

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
