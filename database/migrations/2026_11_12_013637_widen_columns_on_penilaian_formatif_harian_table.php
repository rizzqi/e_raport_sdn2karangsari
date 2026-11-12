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
        Schema::table('penilaian_formatif_harian', function (Blueprint $table) {
            $table->string('nama', 150)->change();
            $table->string('lingkup_materi', 500)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penilaian_formatif_harian', function (Blueprint $table) {
            $table->string('nama', 100)->change();
            $table->string('lingkup_materi', 255)->nullable()->change();
        });
    }
};
