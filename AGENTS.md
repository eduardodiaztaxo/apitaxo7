# AGENTS.md

## Project Overview

Laravel 8 API backend for **Safin Taxo** — an asset inventory management system. Serves a mobile Angular/Ionic/Capacitor frontend (`safin-taxo-app`) that works both online and offline via SQLite dumps.

## Architecture

### Multi-tenant via database switching
- Each client has its own MySQL database (e.g. `mysql_esmax`, `mysql_junji`, `mysql_desa`, etc.)
- `config/database.php` defines ~30+ named connections
- Middleware `switch.database` (`app/Http/Middleware/DatabaseSwitcher.php`) sets `DB::setDefaultConnection($user->conn_field)` per authenticated request
- The `mysql_auth` connection (prefix `api_`) stores users/sessions — always the default
- When querying client data, the active connection is determined at runtime; never hardcode a connection name

### Offline SQLite dump pipeline
The app downloads a SQLite file to work offline. Generation flow:

```
php artisan command:export-for-offline-inventory-sqlite --connection=<conn_field> --cycle=<cycle_id>
```

1. `app/Console/Commands/ExportInventoryCycleSQLiteDatabase.php` orchestrates the export
2. Calls individual dump services in `app/Services/Dump/Tables/` (one per SQLite table)
3. Each dump service calls `InventariosOfflineController` methods directly (not via HTTP) to get data
4. Data is inserted into a SQLite file at `storage/app/db-dumps/`
5. Zipped and registered in `db_audits_dumps` table for the frontend to download

**Known bug (N4-N6 emplazamientos)**: `CycleCatsNn()` at `InventariosOfflineController.php:217` filters emplazamientos by `idAgenda IN (puntos asignados al ciclo)`. If an asset exists at a point NOT assigned to the cycle, its N4+ emplazamiento row is excluded from the SQLite dump, causing missing location levels in offline mode.

### Key domain entities
| Table | Model | Description |
|---|---|---|
| `inv_ciclos` | `InvCiclo` | Inventory cycle (type 1=Inventory, others=Audit) |
| `inv_ciclos_puntos` | `InvCicloPunto` | Pivot: which points (addresses) belong to a cycle |
| `inv_ciclos_categorias` | `Inv_ciclos_categorias` | Pivot: which asset categories a cycle counts |
| `ubicaciones_geograficas` | `UbicacionGeografica` | Physical address/point (idUbicacionGeo = idAgenda in emplazamiento tables) |
| `ubicaciones_n1`..`ubicaciones_n6` | `EmplazamientoN1`..`EmplazamientoN6` | Hierarchical location levels (N1=2chars, N2=4chars, ... N6=12chars code) |
| `crud_activos` | `CrudActivo` | Master asset catalog ("should be") |
| `inv_inventario` | `Inventario` | Actual counted inventory records ("is") |

### API routes
- `routes/api.php` loads `public.php`, `protected.php`, `commands.php`, `v2.php`
- V1 routes: `routes/api/v1/{activos,inventarios,ciclos,emplazamientos,zones,responsibles,auditorias,maps,logger}.php`
- V2 routes: `routes/api/v2.php` — generic emplazamiento level endpoints
- All protected routes use `auth:sanctum` + `switch.database` middleware

## Commands

```bash
# Run dev server
php artisan serve

# Generate offline SQLite for a specific cycle
php artisan command:export-for-offline-inventory-sqlite --connection=mysql_desa --cycle=5

# Generate offline SQLite for audit cycles
php artisan command:export-for-offline-audit-sqlite --connection=<conn> --cycle=<id>

# Run tests
./vendor/bin/phpunit
./vendor/bin/phpunit --testsuite=Unit
./vendor/bin/phpunit --testsuite=Feature
```

No lint, typecheck, or static analysis tools are configured.

## Conventions

- PHP 7.3+ / 8.0 compatible — avoid PHP 8.1+ syntax (enums, fibers, readonly properties)
- Global helper functions in `app/Helpers/helper_functions.php` (autoloaded via composer `files`)
- Dump services implement `DumpSQLiteInterface` and follow the pattern: `createTable()` → `runFromController()` → `insert()` → `createIndexes()`
- Emplazamiento levels N1-N3 have dedicated dump services; N4-N6 share `EmplazamientoNnDumpService` with `setLevel()`
- `InventariosOfflineController` methods are called both as HTTP endpoints AND directly instantiated by dump services (no HTTP layer in between)
- Database column naming is inconsistent: `codigoUbicacion_N1` (underscore) vs `codigoUbicacionN3` (no underscore) — match existing column names exactly per table
