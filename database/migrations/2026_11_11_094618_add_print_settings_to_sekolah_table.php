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
        Schema::table('sekolah', function (Blueprint $table) {
            $table->string('default_paper_size', 10)->default('a4')->after('batas_izin_maksimal');
            $table->string('default_paper_orientation', 15)->default('portrait')->after('default_paper_size');
            $table->string('default_margin_preset', 15)->default('normal')->after('default_paper_orientation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sekolah', function (Blueprint $table) {
            $table->dropColumn([
                'default_paper_size',
                'default_paper_orientation',
                'default_margin_preset',
            ]);
        });
    }
};
