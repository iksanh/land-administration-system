# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A Laravel 13 + Livewire 4 admin application for **land-permit administration in Kabupaten Bone Bolango** (Gorontalo, Indonesia). It is a **port of an existing FastAPI app** onto Laravel. Many files carry `Ports app/api/routes/<x>.py` comments pointing at the original endpoint they replace — preserve the documented behavior when changing these files. The domain language is Indonesian (permohonan = application, pemohon = applicant, tanah = land parcel, berkas = document, pemeriksaan = inspection, wilayah = administrative region, layanan = service).

## Commands

```powershell
# Run everything (server + queue + log tailer + vite) — Composer script
composer dev

# Or individually
php artisan serve
npm run dev                 # vite dev server
npm run build               # production assets

# Tests (Composer script clears config first)
composer test
php artisan test                                    # all
php artisan test --filter=ManagePermohonanTest      # one class
php artisan test tests/Feature/AuthTest.php          # one file

# Lint / format (Laravel Pint)
./vendor/bin/pint           # fix
./vendor/bin/pint --test    # check only

php artisan migrate
php artisan db:seed         # default admin: admin@app.com / admin123 (UserSeeder)
```

## Database — important

- The app runs on **MySQL / MariaDB** (`DB_CONNECTION=mysql`, database `db_phpt`). It was migrated off the original FastAPI PostgreSQL schema — when touching DB code, prefer portable Laravel query-builder methods and avoid Postgres-only SQL. Tests run against `db_phpt_test` (mysql) — see `phpunit.xml`. The test database must be created manually (`CREATE DATABASE db_phpt_test`), as the engine won't auto-create it; `RefreshDatabase` then rebuilds the schema each run.
- Migrations in `database/migrations/2026_06_09_03*` build the domain schema with `timestamp(...)->useCurrent()`, enum-as-string columns, a `json` column (`pemeriksaan_berkas.catatan`, read via the model's `array` cast), and `CHECK` constraints (`tanah`). Match the existing schema rather than "Laravel defaults" when adding columns.
- MySQL has no `NULLS LAST` or `ILIKE`. Emulate ordering with `ORDER BY col IS NULL, col ASC` (see `App\Support\PemeriksaanSheet`); use `LIKE` for case-insensitive search (case-insensitive under the default utf8mb4 collation).
- Models reflect schema quirks that differ from Laravel conventions — read the model before assuming defaults:
  - `User` authenticates against a `hashed_password` column (not `password`); `getAuthPassword()` is overridden. Roles are plain strings (`admin`, `petugas`).
  - Most models use `HasUuids` string PKs (`$incrementing = false`); `PermohonanAuditLog` is the exception (auto-increment int).
  - Timestamp support is uneven: some tables have only `created_at` (`const UPDATED_AT = null`), `permohonan` has both.

## Architecture

- **UI = Livewire full-page components**, not Blade controllers. `routes/web.php` maps each route directly to a `App\Livewire\*` class (e.g. `Route::get('/permohonan', ManagePermohonan::class)`). Each component pairs with a view in `resources/views/livewire/**` and a `#[Layout('components.layouts.app')]` attribute. Plain controllers exist only for non-Livewire output (`PemeriksaanPrintController` renders a printable sheet).
- **Auth & roles**: session auth via Livewire `Auth\Login`. Admin-only routes are wrapped in `->middleware('role:admin')`, an alias for `App\Http\Middleware\EnsureRole` (registered in `bootstrap/app.php`), which ports FastAPI's `get_current_admin` and `abort(403)`s in Indonesian. Guests are redirected to `/dashboard` when hitting guest-only pages.
- **Status workflow** (`Permohonan`): status is an enum cast (`PermohonanStatusEnum`: DRAFT → SUBMITTED → … → SELESAI/DITOLAK). Status is **never** changed through the main edit form — only via `ManagePermohonan::changeStatus()`, which runs in a `DB::transaction` and writes a `permohonan_audit_log` row (old status, new status, `petugas_id`, optional note). Deletion is allowed only while status is `DRAFT`. The `AuditLogTimeline` component reads this log.
- **Cascading region picker**: `App\Livewire\Concerns\WithWilayahPicker` is a trait reused by components with a `public string $desa_id` (ManagePemohon, ManageTanah). It drives a Provinsi → Kabupaten → Kecamatan → Desa cascade over the `ref_*` tables. Host components must call `syncWilayahFromDesa()` in `edit()`, `resetWilayah()` in `resetForm()`, and merge `wilayahLists()` into render data — see the trait's docblock.
- **Inspection sheet**: `App\Support\PemeriksaanSheet::build()` is the shared source of truth for the printable berkas-inspection sheet — it orders rows by `map_layanan_berkas.urutan` and groups child berkas under parents. Both the print controller and the in-app preview modal render the same `pemeriksaan._sheet` partial from it. Don't duplicate this ordering/grouping logic.
- **Enums** live in `app/Enums` and are used as Eloquent casts + `Rule::enum()` validation. Columns are stored as plain strings.

## Conventions

- Naming follows the Indonesian domain (table names like `mst_layanan`, `ref_desa`, `permohonan_audit_log`). Keep `$table` explicit on models since names don't follow Laravel pluralization.
- User-facing flash messages and validation/error text are in Indonesian.
- Tests are PHPUnit Feature tests that drive Livewire components via `Livewire::test(...)` with `RefreshDatabase`; `ModelRoundTripTest` guards the schema-quirk mappings above.

## UI conventions

- **Layout** (`components/layouts/app.blade.php`) is responsive: the sidebar is an off-canvas drawer below the `lg` breakpoint (Alpine `x-data="{ sidebarOpen }"`, toggled by the header hamburger), and static from `lg` up. Alpine ships with Livewire — no separate import. Tables sit in `overflow-x-auto` wrappers and the main column is `min-w-0` so wide tables scroll instead of breaking the layout.
- **List search**: listing components expose a `public string $search` and filter in `render()` with `->when($this->search !== '', ...)` using `like` (see `ManageLayanan`, `ManagePemohon`, etc.). Views add the shared `<x-search-bar model="search" placeholder="..." :count="$list->count()" />` toolbar above the table and make the `@empty` row search-aware. Keep stat cards (e.g. `ManageUsers`) on an unfiltered query so search doesn't skew them.
- Styling is Ant-Design-flavoured Tailwind v4 (accent `#1677ff`). After adding new utility classes, run `npm run build` — the running `php artisan serve` only serves the prebuilt `public/build` CSS.
