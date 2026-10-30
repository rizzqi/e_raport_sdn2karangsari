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
        // 1. Tabel Definisi Penilaian Formatif Harian per Kelas, Mapel, dan Bulan
        if (! Schema::hasTable('penilaian_formatif_harian')) {
            Schema::create('penilaian_formatif_harian', function (Blueprint $table) {
                $table->id();
                $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
                $table->foreignId('mapel_id')->constrained('mata_pelajaran')->cascadeOnDelete();
                $table->unsignedSmallInteger('tahun');
                $table->unsignedTinyInteger('bulan'); // 1 s.d. 12
                $table->string('nama', 100); // Misal: "Harian 1", "TP 1", "Tugas 1"
                $table->string('lingkup_materi', 255)->nullable();
                $table->date('tanggal')->nullable();
                $table->decimal('bobot', 4, 2)->default(1.00);
                $table->string('jenis', 20)->default('teori'); // 'teori' atau 'praktik' (untuk PJOK)
                $table->unsignedSmallInteger('urutan')->default(1);
                $table->timestamps();

                $table->index(['kelas_id', 'mapel_id', 'tahun', 'bulan'], 'idx_penilaian_formatif_harian_scope');
            });
        }

        // 2. Tabel Nilai Formatif Harian Siswa
        if (! Schema::hasTable('nilai_formatif_harian')) {
            Schema::create('nilai_formatif_harian', function (Blueprint $table) {
                $table->id();
                $table->foreignId('penilaian_formatif_harian_id')->constrained('penilaian_formatif_harian')->cascadeOnDelete();
                $table->foreignId('kelas_siswa_id')->constrained('kelas_siswa')->cascadeOnDelete();
                $table->decimal('nilai', 5, 2)->nullable();
                $table->text('catatan')->nullable();
                $table->timestamps();

                $table->unique(['penilaian_formatif_harian_id', 'kelas_siswa_id'], 'idx_nilai_formatif_harian_unique');
                $table->index(['kelas_siswa_id'], 'idx_nilai_formatif_harian_ks');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nilai_formatif_harian');
        Schema::dropIfExists('penilaian_formatif_harian');
    }
};
