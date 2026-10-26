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
        Schema::table('kelas', function (Blueprint $table) {
            $table->date('tanggal_raport_sts')->nullable()->after('wali_nip');
            $table->date('tanggal_raport_sas')->nullable()->after('tanggal_raport_sts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kelas', function (Blueprint $table) {
            $table->dropColumn(['tanggal_raport_sts', 'tanggal_raport_sas']);
        });
    }
};
