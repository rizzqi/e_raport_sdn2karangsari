<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sekolah', function (Blueprint $table) {
            $table->unsignedSmallInteger('hari_efektif_semester')->default(108)->after('bobot_uas');
            $table->unsignedTinyInteger('persen_kehadiran_minimal')->default(75)->after('hari_efektif_semester');
            $table->unsignedTinyInteger('batas_alpa_maksimal')->default(3)->after('persen_kehadiran_minimal');
            $table->unsignedTinyInteger('batas_izin_maksimal')->default(7)->after('batas_alpa_maksimal');
        });
    }

    public function down(): void
    {
        Schema::table('sekolah', function (Blueprint $table) {
            $table->dropColumn([
                'hari_efektif_semester',
                'persen_kehadiran_minimal',
                'batas_alpa_maksimal',
                'batas_izin_maksimal',
            ]);
        });
    }
};
