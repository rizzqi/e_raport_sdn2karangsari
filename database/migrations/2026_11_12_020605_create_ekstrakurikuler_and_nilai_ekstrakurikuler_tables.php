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
        Schema::create('ekstrakurikuler', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 150);
            $table->integer('urutan')->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('nilai_ekstrakurikuler', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelas_siswa_id')->constrained('kelas_siswa')->onDelete('cascade');
            $table->foreignId('ekstrakurikuler_id')->constrained('ekstrakurikuler')->onDelete('cascade');
            $table->enum('periode', ['sts', 'sas'])->default('sts');
            $table->enum('predikat', ['Sangat Baik', 'Baik', 'Cukup', 'Perlu Bimbingan']);
            $table->string('keterangan', 255)->nullable();
            $table->timestamps();

            $table->unique(['kelas_siswa_id', 'ekstrakurikuler_id', 'periode'], 'nilai_ekskul_unique');
        });

        if (DB::table('ekstrakurikuler')->count() === 0) {
            $now = now();
            DB::table('ekstrakurikuler')->insert([
                ['nama' => 'Pramuka', 'urutan' => 1, 'aktif' => true, 'created_at' => $now, 'updated_at' => $now],
                ['nama' => 'Olahraga / Futsal', 'urutan' => 2, 'aktif' => true, 'created_at' => $now, 'updated_at' => $now],
                ['nama' => 'Seni Tari & Musik', 'urutan' => 3, 'aktif' => true, 'created_at' => $now, 'updated_at' => $now],
                ['nama' => "Baca Tulis Al-Qur'an (BTQ)", 'urutan' => 4, 'aktif' => true, 'created_at' => $now, 'updated_at' => $now],
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nilai_ekstrakurikuler');
        Schema::dropIfExists('ekstrakurikuler');
    }
};
