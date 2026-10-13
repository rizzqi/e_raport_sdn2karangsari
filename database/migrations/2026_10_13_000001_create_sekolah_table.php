<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sekolah', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->default('SD NEGERI 2 KARANGSARI');
            $table->text('alamat')->nullable();
            $table->string('pemda')->default('PEMERINTAH KABUPATEN BANYUWANGI');
            $table->string('dinas')->default('DINAS PENDIDIKAN');
            $table->string('kota')->default('Banyuwangi');
            $table->string('kepsek')->nullable();
            $table->string('nip')->nullable();
            $table->string('logo')->nullable();
            $table->string('ttd')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sekolah');
    }
};
