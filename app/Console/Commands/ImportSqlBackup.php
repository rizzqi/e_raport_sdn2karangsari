<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImportSqlBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:import-backup {file=backup_database_e_raport_lengkap.sql} {--fresh : Jalankan migrate:fresh sebelum impor}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Impor data dari file backup database MySQL/MariaDB (.sql) ke database saat ini (SQLite atau MySQL)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $fileName = $this->argument('file');
        $filePath = base_path($fileName);

        if (! File::exists($filePath)) {
            $this->error("File backup tidak ditemukan: {$filePath}");

            return self::FAILURE;
        }

        $connection = DB::connection();
        $driver = $connection->getDriverName();
        $this->info("Menyiapkan database target (Driver: {$driver})...");

        if ($this->option('fresh')) {
            $this->info("Menjalankan migrasi bersih (migrate:fresh)...");
            $this->call('migrate:fresh', ['--force' => true]);
        }

        $this->info("Membaca file SQL: {$fileName}...");
        $sqlContent = File::get($filePath);

        // 1. Ekstrak daftar kolom untuk setiap tabel secara line-by-line dari statement CREATE TABLE
        $lines = explode("\n", $sqlContent);
        $currentTable = null;
        $tableColumns = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if (preg_match('/^CREATE\s+TABLE\s+[`"]?([a-zA-Z0-9_]+)[`"]?\s*\(/i', $trimmed, $m)) {
                $currentTable = $m[1];
                $tableColumns[$currentTable] = [];
                continue;
            }

            if ($currentTable !== null) {
                if (str_starts_with($trimmed, ')') || preg_match('/^\)\s*(?:ENGINE|DEFAULT|;)/i', $trimmed)) {
                    $currentTable = null;
                    continue;
                }

                if (preg_match('/^`([a-zA-Z0-9_]+)`\s+/', $trimmed, $colMatch)) {
                    $tableColumns[$currentTable][] = "`{$colMatch[1]}`";
                }
            }
        }

        // Format kolom menjadi string "(col1, col2, ...)"
        $formattedTableColumns = [];
        foreach ($tableColumns as $tbl => $cols) {
            if (! empty($cols)) {
                $formattedTableColumns[$tbl] = '('.implode(', ', $cols).')';
            }
        }

        // 2. Cari semua blok INSERT INTO `nama_tabel` VALUES ...;
        $insertPattern = '/INSERT\s+INTO\s+[`"]?([a-zA-Z0-9_]+)[`"]?\s+VALUES\s*([\s\S]*?);(?=\r?\n|$)/m';
        preg_match_all($insertPattern, $sqlContent, $matches, PREG_SET_ORDER);

        if (empty($matches)) {
            $this->warn("Tidak ditemukan statement INSERT INTO pada file backup.");

            return self::SUCCESS;
        }

        $this->info('Ditemukan '.count($matches).' grup data tabel untuk diimpor.');

        // Nonaktifkan pemeriksaan foreign key sementara
        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
        }

        $ignoredTables = ['migrations', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs'];

        DB::beginTransaction();

        try {
            foreach ($matches as $match) {
                $tableName = $match[1];
                $valuesSql = $match[2];

                if (in_array(strtolower($tableName), $ignoredTables, true)) {
                    continue;
                }

                // Kosongkan tabel sebelum isi data
                DB::table($tableName)->truncate();

                // Format penyesuaian untuk SQLite
                if ($driver === 'sqlite') {
                    // Konversi escaping single quote dari MySQL (\') menjadi standard SQL ('')
                    $valuesSql = preg_replace("/(?<!\\\\)\\\\'/", "''", $valuesSql);
                    // Konversi double backslash path Windows jika ada
                    $valuesSql = str_replace('\\\\', '/', $valuesSql);
                }

                $columnList = $formattedTableColumns[$tableName] ?? '';
                $query = "INSERT INTO `{$tableName}` {$columnList} VALUES {$valuesSql};";

                DB::unprepared($query);

                $count = DB::table($tableName)->count();
                $this->line("  ✓ <info>{$tableName}</info>: {$count} data berhasil diimpor.");
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("Gagal mengimpor database: {$e->getMessage()}");

            if ($driver === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = ON;');
            } else {
                DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
            }

            return self::FAILURE;
        }

        // Aktifkan kembali foreign key
        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
        }

        $this->newLine();
        $this->info("==========================================================");
        $this->info("  [SUKSES] Seluruh data backup berhasil diimpor ke SQLite!");
        $this->info("==========================================================");

        return self::SUCCESS;
    }
}
