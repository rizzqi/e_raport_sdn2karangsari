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
        Schema::create('absensi_harian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelas_siswa_id')->constrained('kelas_siswa')->cascadeOnDelete();
            // mapel_id null = Absensi Umum / Wali Kelas
            // mapel_id terisi = Absensi Mata Pelajaran (misal: Agama atau PJOK)
            $table->foreignId('mapel_id')->nullable()->constrained('mata_pelajaran')->nullOnDelete();
            $table->date('tanggal');
            $table->string('status', 5)->default('H'); // H = Hadir, S = Sakit, I = Izin, A = Alpa
            $table->string('keterangan', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['kelas_siswa_id', 'tanggal'], 'idx_absen_siswa_tanggal');
            $table->index(['mapel_id', 'tanggal'], 'idx_absen_mapel_tanggal');
            $table->index(['tanggal'], 'idx_absen_tanggal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absensi_harian');
    }
};
