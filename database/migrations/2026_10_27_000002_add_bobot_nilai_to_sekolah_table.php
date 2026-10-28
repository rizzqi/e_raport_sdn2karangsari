<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sekolah', function (Blueprint $table) {
            $table->unsignedTinyInteger('bobot_formatif')->default(40)->after('kontak');
            $table->unsignedTinyInteger('bobot_uts')->default(30)->after('bobot_formatif');
            $table->unsignedTinyInteger('bobot_uas')->default(30)->after('bobot_uts');
        });
    }

    public function down(): void
    {
        Schema::table('sekolah', function (Blueprint $table) {
            $table->dropColumn(['bobot_formatif', 'bobot_uts', 'bobot_uas']);
        });
    }
};
