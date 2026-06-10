# Member Discount API (Laravel + React)

A small, deliberately-scoped full-stack app. Members get verified for a sector
(healthcare, education, charity or carer) and can redeem brand discount offers
targeted at that sector. Every redemption runs through a gated service that
decides, in a specific order, whether the member is allowed the code.

> **Why this repo exists.** I built this for the Network Digital interview to
> show I can write Laravel from scratch, cleanly. My day-to-day background is
> C#/.NET Core, React/TypeScript and Python/FastAPI. I have read and modified
> PHP/Laravel inside legacy systems (Ochiba, Servelec) but had not built a
> standalone Laravel app from scratch, so I built this one to show I can write it
> cleanly, and I mapped each piece to my .NET mental model (see the
> [mapping table](#laravel-and-net-mapping)). The backend is the exhibit; the
> React frontend is a clean frame around it.

## Start here: the centerpiece

The core business logic lives in
[`api/app/Services/RedemptionService.php`](api/app/Services/RedemptionService.php).
It is the piece I would most want to walk through. It runs six ordered
eligibility gates and, on success, issues the code inside a database
transaction. It is covered gate by gate in
[`api/tests/Unit/RedemptionServiceTest.php`](api/tests/Unit/RedemptionServiceTest.php).

## Live demo

- **Live URL:** https://member-discount-api.onrender.com
- **Log in with:** `nurse@demo.test` / `password` (a verified healthcare member, start here).
- This demo runs on a free tier that sleeps after a period of inactivity, so the
  first request can take up to 50 seconds or more to wake the server, after which
  it is responsive. The app shows a "waking up" screen during that time rather than
  a blank page, so the wait is expected, not a bug.

Seeded accounts (all use the password `password`):

| Email | Role | Use it to see |
| --- | --- | --- |
| `nurse@demo.test` | Verified, healthcare | The happy path plus every redemption gate |
| `pending@demo.test` | Unverified, healthcare | The "unverified members can't redeem" path (403) |
| `admin@demo.test` | Admin | The verification review queue |

Demo data is re-seeded on every deploy, so these always work. Accounts you
register yourself are ephemeral (the SQLite database resets on restart), which is
a deliberate trade-off for a zero-cost demo (see [Architecture](#architecture-decisions)).

## Tech stack

Versions are the ones actually installed in this repo:

- **Backend:** PHP 8.4.22, Laravel 13.15.0, Laravel Sanctum 4.3.2 (API tokens),
  Eloquent, migrations and seeders, PHPUnit 12.5.29.
- **Frontend:** Vite 8.0.16, React 19.2.7, TypeScript 6.0.3, Tailwind CSS 4.3.0,
  React Router 7.17.0.
- **Database:** SQLite. **Hosting:** a single Render Docker web service serving
  both the API and the built React app from the same origin.

## The domain in one paragraph

A `User` (member) is verified for one `Sector`. `Brand`s have `Offer`s, each
targeting a sector with a date window, an optional capacity and a hidden
`discount_code`. A member submits a `VerificationRequest`; an admin approves it,
which flips the member to verified. A verified member can then redeem an offer
for their sector, which creates a `Redemption` and issues the code, exactly once
per member per offer (enforced by both the service and a database unique index).

## Redemption gates

`RedemptionService::redeem(User, Offer)` checks the following in order, throwing a
specific typed exception (mapped centrally to JSON) on the first failure:

| # | Gate | Failure HTTP | `error` code |
| --- | --- | --- | --- |
| 1 | Member is verified | 403 | `not_verified` |
| 2 | Offer is active | 422 | `offer_inactive` |
| 3 | Offer sector matches member sector | 403 | `sector_mismatch` |
| 4 | Now within `[starts_at, expires_at]` | 422 | `outside_window` |
| 5 | Not already redeemed by this member | 409 | `already_redeemed` |
| 6 | Capacity not reached | 409 | `capacity_reached` |

## Laravel and .NET mapping

This table is both documentation and my own translation layer.

| Laravel | .NET equivalent |
| --- | --- |
| Eloquent ORM | Entity Framework |
| Migrations | EF Migrations |
| Artisan CLI | `dotnet` CLI |
| Composer | NuGet |
| Middleware | ASP.NET middleware |
| Sanctum API tokens | JWT Bearer auth |
| Controllers | API controllers |
| Service container / DI | built-in DI container |
| Form Requests | model validation / FluentValidation |
| API Resources | DTOs plus response shaping |
| Service classes (`RedemptionService`) | application/service layer |
| Gates and Policies | authorization policies |
| Blade (not used here, React instead) | Razor |

## Run it locally

Prerequisites: PHP 8.4 with Composer, and Node 20 or newer. On Windows,
[Laravel Herd](https://herd.laravel.com) bundles PHP and Composer; otherwise
install PHP 8.4 with the `pdo_sqlite`, `mbstring`, `openssl`, `curl`, `zip` and
`fileinfo` extensions.

```bash
# Backend
cd api
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed          # creates and seeds database/database.sqlite
php artisan serve                   # serves http://localhost:8000

# Frontend (separate terminal)
cd web
npm install
npm run dev                         # serves http://localhost:5173, proxies /api to :8000
```

For a production-like single-origin run, build the SPA into Laravel's `public/`
and serve only Laravel:

```bash
cd web && npm run build             # outputs into ../api/public
cd ../api && php artisan serve      # http://localhost:8000 serves the app and the API
```

Run the tests (the backend is fully testable with no frontend present):

```bash
cd api && php artisan test
```

## API by example

Token (Bearer) auth means everything is reproducible with `curl`. Replace `$APP`
with the base URL (`http://localhost:8000` locally). The offer IDs below are the
seeded ones.

```bash
# Log in as the seeded nurse and get a Bearer token.
curl -X POST $APP/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"nurse@demo.test","password":"password"}'
# responds 200: { "user": { "verification_status": "verified", ... }, "token": "2|abc..." }

TOKEN="2|abc..."   # paste from the response above

# List offers for the member's sector (newest first). discount_code is not
# included here; it is only issued on redemption.
curl $APP/api/offers -H "Authorization: Bearer $TOKEN"

# Redeem a matching, active offer. This issues the code.
curl -X POST $APP/api/offers/1/redeem -H "Authorization: Bearer $TOKEN"
# responds 201: { "data": { "code_issued": "APPLE-HEALTH-20", "offer": {...} } }
```

The failure responses are the most interesting part: same endpoint, different
member or offer state.

```bash
# Redeem the same offer again.
curl -X POST $APP/api/offers/1/redeem -H "Authorization: Bearer $TOKEN"
# responds 409: { "error": "already_redeemed", "message": "You have already redeemed this offer." }

# Redeem a teacher-only (education) offer as a healthcare member.
curl -X POST $APP/api/offers/6/redeem -H "Authorization: Bearer $TOKEN"
# responds 403: { "error": "sector_mismatch", "message": "This offer is not available for your verified sector." }

# Redeem an expired offer.
curl -X POST $APP/api/offers/2/redeem -H "Authorization: Bearer $TOKEN"
# responds 422: { "error": "outside_window", "message": "This offer has expired." }
```

### Endpoint summary

| Method | Path | Who | Purpose |
| --- | --- | --- | --- |
| POST | `/api/register` | public | Create a member (pending) and return a token |
| POST | `/api/login` | public | Return a token |
| POST | `/api/logout` | member | Revoke the current token |
| GET | `/api/user` | member | The current member |
| GET | `/api/offers` | member | Offers for the member's sector |
| POST | `/api/offers/{id}/redeem` | member | Redeem (the centerpiece) |
| GET | `/api/redemptions` | member | The member's redemption history |
| POST | `/api/verification` | member | Submit proof of eligibility |
| GET | `/api/brands` | member | Brands with their offers |
| GET | `/api/admin/verifications` | admin | Pending verification queue |
| POST | `/api/admin/verifications/{id}/approve` and `/reject` | admin | Review a request |
| `apiResource` | `/api/admin/brands` | admin | Brand CRUD (the resource-controller exhibit) |
| POST, PUT | `/api/admin/offers` | admin | Create and update offers |

## Architecture decisions

- **Service layer for redemption.** All the business rules live in
  `RedemptionService`, not the controller. That is the same separation I use in
  .NET (thin controllers, logic in a unit-testable service). It is the single
  most important file and the easiest to reason about and test in isolation.
- **Token auth, not SPA cookie mode.** Sanctum supports both. I chose API tokens
  because the API is then independently testable from `curl` or Postman,
  documentable with clean examples, stateless, and identical to how a mobile or
  third-party client would consume it. It also maps directly to the JWT Bearer
  flow I use in .NET. The other valid option for a same-origin first-party SPA is
  Sanctum's cookie/CSRF mode, which I would reach for if the app were strictly
  first-party and I wanted httpOnly-cookie sessions.
- **One same-origin service.** Laravel serves the built React app from `public/`
  and the `/api/*` routes, so there is no CORS to configure, auth just works, and
  there is a single URL to share. The repo stays cleanly split into `/api` and
  `/web`; only the build bundles React into Laravel's `public/`.
- **SQLite, seeded at build time.** This mirrors the in-memory database choice in
  my .NET projects and keeps the demo portable. The seeded database is baked into
  the Docker image, so the known demo data is present on every boot;
  user-registered data is ephemeral, which is acceptable and expected for a
  free-tier demo.
- **Typed exceptions plus central mapping.** Each redemption failure is its own
  exception carrying an HTTP status and an error code, mapped to JSON in one place
  (`bootstrap/app.php`). Like domain exceptions mapped in .NET middleware.

## Deployment

One Render Docker web service (free tier). The multi-stage
[`Dockerfile`](Dockerfile) builds the React app, installs PHP dependencies, bakes
the seeded SQLite database into the image, caches routes, and serves via
`php artisan serve` bound to `$PORT`.

1. Push this repo to GitHub.
2. On Render, choose New then Blueprint (uses [`render.yaml`](render.yaml)), or
   New then Web Service with the Docker runtime.
3. Set environment variables (not committed):
   - `APP_KEY`: generate with `php artisan key:generate --show` and paste the
     `base64:...` value.
   - `APP_ENV=production`, `APP_DEBUG=false`, `DB_CONNECTION=sqlite`.
   - `APP_URL`: the live service URL (after the first deploy).
4. Render builds the image and runs it. HTTPS is terminated at Render's proxy;
   the app trusts that proxy and forces HTTPS in production so asset and URL links
   are correct.

`php artisan serve` is single-threaded, which is a deliberate and easily-explained
choice for a low-traffic demo. For real traffic I would put it behind nginx with
php-fpm, or FrankenPHP (see below).

## What I would do with more time

- **Real verification uploads:** store an actual document (S3 or a Render disk)
  instead of a free-text `proof_reference`.
- **Rate limiting** on the redemption endpoint, and a queue for any async work.
- **Postgres plus a persistent disk** so registered users and redemptions survive
  restarts (the demo intentionally trades this away).
- **Sanctum cookie auth** if the app were strictly a first-party SPA.
- **nginx with php-fpm, or FrankenPHP** for concurrency instead of `artisan serve`.
- **Observability:** structured logging, error tracking and uptime checks, plus a
  CI pipeline running the test suite on every push.
