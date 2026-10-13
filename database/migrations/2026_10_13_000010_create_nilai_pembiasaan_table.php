<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nilai_pembiasaan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelas_siswa_id')->constrained('kelas_siswa')->cascadeOnDelete();
            $table->foreignId('pembiasaan_id')->constrained('pembiasaan')->cascadeOnDelete();
            $table->enum('periode', ['sts', 'sas']);
            $table->enum('predikat', ['Sangat Baik', 'Baik', 'Cukup', 'Perlu Bimbingan'])->default('Baik');
            $table->timestamps();

            $table->unique(['kelas_siswa_id', 'pembiasaan_id', 'periode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nilai_pembiasaan');
    }
};
