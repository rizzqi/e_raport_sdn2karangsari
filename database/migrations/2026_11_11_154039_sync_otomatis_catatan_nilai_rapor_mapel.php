<?php

use App\Models\NilaiRaporMapel;
use App\Services\HitungNilaiService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        NilaiRaporMapel::where('is_locked', false)
            ->with(['mataPelajaran', 'kelasSiswa.kelas'])
            ->chunkById(100, function ($records): void {
                foreach ($records as $rapor) {
                    $rapor->predikat = NilaiRaporMapel::hitungPredikat(
                        $rapor->nilai_rapor !== null ? (float) $rapor->nilai_rapor : null
                    );
                    HitungNilaiService::sinkronkanCatatanOtomatis($rapor);
                    if ($rapor->isDirty(['predikat', 'catatan', 'catatan_manual'])) {
                        $rapor->save();
                    }
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tidak ada rollback untuk sinkronisasi catatan otomatis.
    }
};
