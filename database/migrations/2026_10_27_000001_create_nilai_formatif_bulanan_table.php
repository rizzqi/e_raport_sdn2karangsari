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
        Schema::create('nilai_formatif_bulanan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelas_siswa_id')->constrained('kelas_siswa')->cascadeOnDelete();
            $table->foreignId('mapel_id')->constrained('mata_pelajaran')->cascadeOnDelete();
            $table->unsignedSmallInteger('tahun');
            $table->unsignedTinyInteger('bulan'); // 1 s.d. 12
            $table->decimal('nilai', 5, 2)->nullable();
            $table->string('lingkup_materi', 255)->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique(['kelas_siswa_id', 'mapel_id', 'tahun', 'bulan'], 'idx_formatif_bulanan_unique');
            $table->index(['mapel_id', 'tahun', 'bulan'], 'idx_formatif_mapel_periode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nilai_formatif_bulanan');
    }
};
