<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nilai', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelas_siswa_id')->constrained('kelas_siswa')->cascadeOnDelete();
            $table->foreignId('mapel_id')->constrained('mata_pelajaran')->cascadeOnDelete();
            $table->enum('periode', ['sts', 'sas']);
            $table->decimal('formatif', 5, 2)->nullable();
            $table->decimal('sumatif', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['kelas_siswa_id', 'mapel_id', 'periode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nilai');
    }
};
