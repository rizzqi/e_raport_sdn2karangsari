<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nilai_rapor_mapel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelas_siswa_id')->constrained('kelas_siswa')->cascadeOnDelete();
            $table->foreignId('mapel_id')->constrained('mata_pelajaran')->cascadeOnDelete();
            $table->integer('tahun');
            $table->string('semester', 20)->default('ganjil'); // 'ganjil' atau 'genap'
            $table->decimal('rata_formatif', 5, 2)->nullable();
            $table->decimal('nilai_uts', 5, 2)->nullable();
            $table->decimal('nilai_uas', 5, 2)->nullable();
            $table->decimal('nilai_rapor', 5, 2)->nullable();
            $table->string('predikat', 5)->nullable(); // 'A', 'B', 'C', 'D'
            $table->text('catatan')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['kelas_siswa_id', 'mapel_id', 'tahun', 'semester'], 'unique_nilai_rapor_mapel');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nilai_rapor_mapel');
    }
};
