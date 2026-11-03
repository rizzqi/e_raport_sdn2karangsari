<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tambahkan unique constraint pada tabel absensi_harian untuk mencegah duplikasi data
 * presensi per siswa, per mata pelajaran (atau wali kelas), per tanggal.
 *
 * Kolom mapel_id bersifat nullable (NULL = absensi umum Wali Kelas). Karena MySQL/MariaDB
 * dan SQLite memperlakukan NULL ≠ NULL dalam UNIQUE constraint standar, kita menggunakan
 * pendekatan Partial Unique Index (WHERE mapel_id IS NULL) untuk baris wali kelas, dan
 * unique index biasa untuk baris dengan mapel_id terisi.
 *
 * Untuk SQLite (environment pengujian), partial index didukung secara native sejak SQLite 3.8.9.
 * Untuk MySQL/MariaDB (environment produksi), partial index tidak didukung namun dapat diganti
 * dengan UNIQUE index pada kolom (kelas_siswa_id, mapel_id, tanggal) — NULL akan membuat
 * setiap baris dengan mapel_id NULL dianggap unik secara terpisah, sehingga constraint efektif
 * hanya untuk baris mata pelajaran. Oleh karena itu kita menggunakan partial index untuk SQLite
 * dan application-level guard (updateOrCreate) yang sudah ada untuk MySQL sebagai safety net.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            // SQLite: dua partial index terpisah — satu untuk baris umum (mapel_id IS NULL)
            // dan satu untuk baris mata pelajaran (mapel_id IS NOT NULL).
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS uq_absen_siswa_wali_tanggal
                ON absensi_harian (kelas_siswa_id, tanggal)
                WHERE mapel_id IS NULL');

            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS uq_absen_siswa_mapel_tanggal
                ON absensi_harian (kelas_siswa_id, mapel_id, tanggal)
                WHERE mapel_id IS NOT NULL');
        } else {
            // MySQL / MariaDB: gunakan unique index standar tiga kolom.
            // Behaviour NULL pada MySQL membuat setiap baris mapel_id NULL unik satu sama lain,
            // namun duplikasi untuk baris mata pelajaran (mapel_id berisi ID) akan terproteksi.
            // Proteksi untuk baris wali kelas (mapel_id NULL) tetap dijamin oleh updateOrCreate
            // di controller dan DB::transaction yang sudah ada.
            Schema::table('absensi_harian', function (Blueprint $table) {
                $table->unique(
                    ['kelas_siswa_id', 'mapel_id', 'tanggal'],
                    'uq_absen_siswa_mapel_tanggal'
                );
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS uq_absen_siswa_wali_tanggal');
            DB::statement('DROP INDEX IF EXISTS uq_absen_siswa_mapel_tanggal');
        } else {
            Schema::table('absensi_harian', function (Blueprint $table) {
                $table->dropUnique('uq_absen_siswa_mapel_tanggal');
            });
        }
    }
};
