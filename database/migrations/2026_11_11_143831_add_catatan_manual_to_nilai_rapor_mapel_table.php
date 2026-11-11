<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('nilai_rapor_mapel', function (Blueprint $table) {
            $table->boolean('catatan_manual')->default(false)->after('catatan');
        });

        // Catatan yang sudah ada sebelumnya dianggap catatan manual agar tidak tertimpa otomatis.
        DB::table('nilai_rapor_mapel')
            ->whereNotNull('catatan')
            ->whereRaw("TRIM(catatan) != ''")
            ->update(['catatan_manual' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nilai_rapor_mapel', function (Blueprint $table) {
            $table->dropColumn('catatan_manual');
        });
    }
};
