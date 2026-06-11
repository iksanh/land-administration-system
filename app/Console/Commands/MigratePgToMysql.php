<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-off data copy from the legacy PostgreSQL database (db_fastapi) into the
 * current MySQL/MariaDB database (db_phpt). Schema is already in place via the
 * Laravel migrations; this only moves rows. Re-runnable: each table is
 * truncated before load. Source connection is configured inline so no .env or
 * config/database.php change is needed.
 *
 *   php artisan db:migrate-from-pgsql
 */
class MigratePgToMysql extends Command
{
    protected $signature = 'db:migrate-from-pgsql
        {--host=127.0.0.1}
        {--port=5432}
        {--database=db_fastapi}
        {--username=postgres}
        {--password=postgres}';

    protected $description = 'Copy all domain data from the legacy PostgreSQL db into MySQL/MariaDB';

    /** Domain tables in FK-dependency order (Laravel system tables are skipped). */
    private array $tables = [
        'users',
        'ref_provinsi',
        'ref_kabupaten',
        'ref_kecamatan',
        'ref_desa',
        'mst_layanan',
        'mst_berkas_item',
        'map_layanan_berkas',
        'mst_catatan',
        'pemohon',
        'tanah',
        'permohonan',
        'pemeriksaan_berkas',
        'permohonan_audit_log',
    ];

    /** Columns that are boolean in the schema (names are unique to booleans). */
    private array $boolColumns = ['is_active', 'is_mandatory'];

    /** Columns to normalise from timestamptz to "Y-m-d H:i:s". */
    private array $datetimeColumns = ['created_at', 'updated_at'];

    public function handle(): int
    {
        config(['database.connections.pgsrc' => [
            'driver' => 'pgsql',
            'host' => $this->option('host'),
            'port' => (int) $this->option('port'),
            'database' => $this->option('database'),
            'username' => $this->option('username'),
            'password' => $this->option('password'),
            'charset' => 'utf8',
            'search_path' => 'public',
            'sslmode' => 'prefer',
        ]]);
        DB::purge('pgsrc');

        $src = DB::connection('pgsrc');
        $dst = DB::connection('mysql');

        $this->info("Source: pgsql {$this->option('database')}  ->  Target: mysql ".$dst->getDatabaseName());

        try {
            $src->getPdo();
        } catch (\Throwable $e) {
            $this->error('Cannot connect to PostgreSQL: '.$e->getMessage());

            return self::FAILURE;
        }

        $dst->statement('SET FOREIGN_KEY_CHECKS=0');

        $grandTotal = 0;
        try {
            foreach ($this->tables as $table) {
                if (! $src->getSchemaBuilder()->hasTable($table)) {
                    $this->warn(str_pad($table, 26)." - not in source, skipped");
                    continue;
                }

                $dst->table($table)->truncate();

                $rows = $src->table($table)->get()
                    ->map(fn ($row) => $this->transform((array) $row))
                    ->all();

                foreach (array_chunk($rows, 500) as $batch) {
                    $dst->table($table)->insert($batch);
                }

                $count = count($rows);
                $grandTotal += $count;
                $this->line(str_pad($table, 26).' '.str_pad($count, 6, ' ', STR_PAD_LEFT).' rows');
            }
        } finally {
            $dst->statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $this->info("Done. {$grandTotal} rows copied across ".count($this->tables).' tables.');

        return self::SUCCESS;
    }

    /** Normalise a single source row for MySQL insertion. */
    private function transform(array $row): array
    {
        foreach ($row as $key => $value) {
            if ($value === null) {
                continue;
            }

            if (in_array($key, $this->boolColumns, true)) {
                $row[$key] = ($value === true || $value === 't' || $value === '1' || $value === 1) ? 1 : 0;
            } elseif (in_array($key, $this->datetimeColumns, true)) {
                $row[$key] = Carbon::parse($value)->format('Y-m-d H:i:s');
            }
            // UUIDs, decimals, dates (Y-m-d), text and the jsonb `catatan`
            // string all transfer verbatim into their MySQL equivalents.
        }

        return $row;
    }
}
