<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tambah kolom nilai_praktik di nilai_formatif_bulanan untuk PJOK (formatif + praktikum per bulan)
        if (Schema::hasTable('nilai_formatif_bulanan') && ! Schema::hasColumn('nilai_formatif_bulanan', 'nilai_praktik')) {
            Schema::table('nilai_formatif_bulanan', function (Blueprint $table) {
                $table->decimal('nilai_praktik', 5, 2)->nullable()->after('nilai');
            });
        }

        // 2. Tambah kolom praktikum di nilai_rapor_mapel (praktik 1 pra-UTS & praktik 2 pra-UAS untuk Pendidikan Agama)
        if (Schema::hasTable('nilai_rapor_mapel')) {
            Schema::table('nilai_rapor_mapel', function (Blueprint $table) {
                if (! Schema::hasColumn('nilai_rapor_mapel', 'praktik_1')) {
                    $table->decimal('praktik_1', 5, 2)->nullable()->after('rata_formatif');
                }
                if (! Schema::hasColumn('nilai_rapor_mapel', 'praktik_2')) {
                    $table->decimal('praktik_2', 5, 2)->nullable()->after('praktik_1');
                }
                if (! Schema::hasColumn('nilai_rapor_mapel', 'rata_praktik')) {
                    $table->decimal('rata_praktik', 5, 2)->nullable()->after('praktik_2');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('nilai_formatif_bulanan') && Schema::hasColumn('nilai_formatif_bulanan', 'nilai_praktik')) {
            Schema::table('nilai_formatif_bulanan', function (Blueprint $table) {
                $table->dropColumn('nilai_praktik');
            });
        }

        if (Schema::hasTable('nilai_rapor_mapel')) {
            Schema::table('nilai_rapor_mapel', function (Blueprint $table) {
                $columns = [];
                if (Schema::hasColumn('nilai_rapor_mapel', 'praktik_1')) {
                    $columns[] = 'praktik_1';
                }
                if (Schema::hasColumn('nilai_rapor_mapel', 'praktik_2')) {
                    $columns[] = 'praktik_2';
                }
                if (Schema::hasColumn('nilai_rapor_mapel', 'rata_praktik')) {
                    $columns[] = 'rata_praktik';
                }
                if (! empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
