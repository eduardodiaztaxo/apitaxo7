# AGENTS.md — Safin / Taxo Cloud API

Laravel 8 multi-tenant asset-management API. Auth via Sanctum with custom refresh-token flow and per-tenant dynamic database switching.

## Quick start

```bash
cp .env.example .env        # edit DB creds as needed
composer install
php artisan key:generate
npm install && npm run dev  # Mix (not Vite) — see package.json scripts
php artisan serve
```

## Key architecture

- **Multi-database**: `config/database.php` defines ~30 MySQL connections per client (e.g. `mysql_esmax`, `mysql_junji`, `mysql_bci`). Default connection is `mysql_auth` (prefix=`api_`). `mysql_base` is a no-prefix variant for raw auth queries.
- **Dynamic DB switching**: `DatabaseSwitcher` middleware (`switch.database`) reads `user->conn_field` and calls `DB::setDefaultConnection()` at runtime. Applied to all protected V1/V2 API routes.
- **Auth**: `POST /api/login` (email) and `POST /api/login-by-user` (name) return a Sanctum token + custom refresh token. Token expiration: 960 min (16h). Refresh: 7200 min (5d) — see `config/sanctum.php`. Custom `PersonalAccessToken` model at `app/Models/Sanctum/PersonalAccessToken.php`, registered via `AuthServiceProvider` (`Sanctum::usePersonalAccessTokenModel()`). The `User` model overrides `createToken()` to support `expires_at`. Expired-token detection is handled in `app/Exceptions/Handler.php` (returns `type: invalid_token`).
- **Permissions**: `roles.permissions` middleware checks entity-level CRUD via `roles_entities_permissions` table. Hardcoded entity 4 grants full access.
- **API versions**: `routes/api.php` requires public, v1/protected, v1/commands, and v2 route files. All protected V1/V2 routes use `auth:sanctum` + `switch.database`. V1 routes are organized into `activos.php`, `inventarios.php`, `ciclos.php`, `responsibles.php`, `emplazamientos.php`, `zones.php`, `auditorias.php`, `maps.php`, `logger.php`. `commands.php` exposes admin command endpoints (marker-to-area relations).

## Commands

| Command | Description |
|---------|-------------|
| `php artisan serve` | Dev server |
| `npm run dev` / `npm run prod` | Mix asset build |
| `npm run watch` | Watch for asset changes |
| `phpunit` | Run all tests |
| `phpunit --testsuite=Unit` | Unit tests |
| `phpunit --testsuite=Feature` | Feature tests |
| `php artisan telescope:*` | Debug via Laravel Telescope |

## Testing

- Tests in `tests/Feature` (auth, zonas) and `tests/Unit`. DB tests use `array` cache driver and `sync` queue — see `phpunit.xml`. No in-memory SQLite by default; uncomment the `DB_CONNECTION` / `DB_DATABASE` lines to enable.

## Important conventions & gotchas

- **Asset build**: Uses Laravel Mix (`webpack.mix.js`). A `vite.config.js` exists but is unused — do not run `vite`.
- **Autoloaded helpers**: `app/Helpers/helper_functions.php` (accent removal, RUT formatting, marker icons). Already in `composer.json` `autoload.files`.
- **Request logging**: Every API request is logged to `log_api` table via `LogRequestMiddleware` (global middleware in `Kernel.php`).
- **Image paths**: Configured via `.env` — `TAXOFILES` (documents) and `TAXOIMAGES` (images) point to an external filesystem. `TAXOIMAGES_URL` is used for serving images.
- **Queue**: `QUEUE_CONNECTION=database` by default. Run `php artisan queue:work` for background jobs (image processing, password updates, command execution).
- **Rate limiting**: API throttled at 600 req/min per user/IP (`RouteServiceProvider`).
- **PDF documents**: Uses `edualejandrodiaz/easy-legal-pdf-documents` from a private VCS repo (`https://github.com/edualejandrodiaz/easy-legal-pdf-documents.git`).
- **Feature test DB**: `phpunit.xml` has `DB_CONNECTION` and `DB_DATABASE` for SQLite commented out. Uncomment those lines to enable in-memory SQLite for tests.
