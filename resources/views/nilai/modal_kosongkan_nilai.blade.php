<!-- Modal Kosongkan / Reset Nilai Kelas Saat Terjadi Salah Input -->
<div id="modalKosongkanNilai" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-3xl max-w-lg w-full shadow-2xl border border-slate-100 overflow-hidden animate-in fade-in duration-200">

        <!-- Header Modal -->
        <div class="p-5 border-b border-rose-100 bg-rose-50/70 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-2xl bg-rose-100 text-rose-700 flex items-center justify-center text-lg font-bold shadow-xs">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div>
                    <h3 class="text-base font-black text-rose-950">
                        Kosongkan Nilai Kelas
                    </h3>
                    <p class="text-xs text-rose-700 mt-0.5">
                        Rombel: <strong>{{ $kelas->nama }}</strong> &bull; TP {{ $kelas->tahunAjaran->nama_lengkap ?? '-' }}
                    </p>
                </div>
            </div>
            <button type="button" onclick="document.getElementById('modalKosongkanNilai').classList.add('hidden')"
                class="w-8 h-8 rounded-full bg-white/80 hover:bg-white text-slate-500 hover:text-slate-700 flex items-center justify-center transition shadow-2xs">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form action="{{ route('nilai.kosongkan.kelas', $kelas->id) }}" method="POST" class="p-6 space-y-4">
            @csrf

            <!-- Informasi Penjelasan -->
            <div class="p-3.5 bg-amber-50 border border-amber-200/80 rounded-2xl text-xs text-amber-950 flex items-start space-x-2.5">
                <i class="fa-solid fa-circle-info text-amber-600 text-sm mt-0.5 shrink-0"></i>
                <div>
                    <p class="font-bold">Koreksi Kesalahan Input Massal</p>
                    <p class="text-[11px] text-amber-900 mt-0.5 leading-relaxed">
                        Form ini digunakan jika terjadi salah input nilai atau salah impor spreadsheet. Anda dapat mengosongkan nilai untuk mata pelajaran tertentu saja atau sekaligus semua mata pelajaran pada rombel ini.
                    </p>
                </div>
            </div>

            <!-- Pilihan Periode Penilaian -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Periode Penilaian yang Dikosongkan *
                </label>
                <div class="grid grid-cols-3 gap-2">
                    <label class="border border-slate-200 rounded-xl p-2.5 text-center cursor-pointer hover:bg-slate-50 transition has-[:checked]:border-rose-500 has-[:checked]:bg-rose-50/50 has-[:checked]:text-rose-900">
                        <input type="radio" name="periode" value="sts" class="sr-only" {{ (isset($periode) && $periode === 'sts') || !isset($periode) ? 'checked' : '' }}>
                        <span class="block text-xs font-bold">STS</span>
                        <span class="block text-[10px] text-slate-500">Tengah Semester</span>
                    </label>
                    <label class="border border-slate-200 rounded-xl p-2.5 text-center cursor-pointer hover:bg-slate-50 transition has-[:checked]:border-rose-500 has-[:checked]:bg-rose-50/50 has-[:checked]:text-rose-900">
                        <input type="radio" name="periode" value="sas" class="sr-only" {{ isset($periode) && $periode === 'sas' ? 'checked' : '' }}>
                        <span class="block text-xs font-bold">SAS</span>
                        <span class="block text-[10px] text-slate-500">Akhir Semester</span>
                    </label>
                    <label class="border border-slate-200 rounded-xl p-2.5 text-center cursor-pointer hover:bg-slate-50 transition has-[:checked]:border-rose-500 has-[:checked]:bg-rose-50/50 has-[:checked]:text-rose-900">
                        <input type="radio" name="periode" value="semua" class="sr-only">
                        <span class="block text-xs font-bold">Semua</span>
                        <span class="block text-[10px] text-slate-500">STS & SAS</span>
                    </label>
                </div>
            </div>

            <!-- Pilihan Cakupan Mata Pelajaran -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Cakupan Mata Pelajaran *
                </label>
                @php
                    $listMapel = isset($mapels) ? $mapels : $kelas->mataPelajaran();
                @endphp
                <select name="mapel_id" class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-rose-500">
                    @if(isset($activeMapel) && $activeMapel)
                        <option value="{{ $activeMapel->id }}" selected>
                            &bull; Hanya Mapel Ini: {{ $activeMapel->nama }} (Rekomendasi)
                        </option>
                    @endif
                    <option value="semua" {{ !isset($activeMapel) ? 'selected' : '' }}>
                        &bull; Seluruh Mata Pelajaran di Kelas Ini (Sekaligus)
                    </option>
                    <optgroup label="Pilih Mata Pelajaran Khusus:">
                        @foreach($listMapel as $m)
                            @if(!isset($activeMapel) || $activeMapel->id !== $m->id)
                                <option value="{{ $m->id }}">
                                    {{ $m->nama }}
                                </option>
                            @endif
                        @endforeach
                    </optgroup>
                </select>
                <span class="text-[11px] text-slate-400 mt-1 block">
                    Pilih mapel tertentu jika hanya 1 mapel yang salah, atau pilih "Seluruh Mata Pelajaran" jika ingin mereset total.
                </span>
            </div>

            <!-- Opsi Tambahan -->
            <div class="bg-slate-50 p-3 rounded-2xl border border-slate-200/70 space-y-2">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-600 block mb-1">Opsi Tambahan (Opsional)</span>
                <label class="flex items-center space-x-2 text-xs font-semibold text-slate-700 cursor-pointer">
                    <input type="checkbox" name="kosongkan_kehadiran" value="1" class="rounded text-rose-600 focus:ring-rose-500 border-slate-300">
                    <span>Kosongkan juga data Kehadiran (Sakit, Izin, Alpa) sekelas</span>
                </label>
                <label class="flex items-center space-x-2 text-xs font-semibold text-slate-700 cursor-pointer">
                    <input type="checkbox" name="kosongkan_pembiasaan" value="1" class="rounded text-rose-600 focus:ring-rose-500 border-slate-300">
                    <span>Kosongkan juga Nilai Pembiasaan siswa sekelas</span>
                </label>
            </div>

            <!-- Peringatan & Konfirmasi -->
            <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl space-y-2">
                <p class="text-xs text-rose-900 font-bold flex items-center">
                    <i class="fa-solid fa-radiation mr-1.5 text-rose-600"></i> Tindakan ini tidak dapat dibatalkan!
                </p>
                <label class="flex items-start space-x-2 text-xs text-slate-800 cursor-pointer">
                    <input type="checkbox" name="konfirmasi" value="1" required class="mt-0.5 rounded text-rose-600 focus:ring-rose-500 border-slate-300">
                    <span class="font-bold">Saya menyatakan yakin dan sadar ingin mengosongkan nilai untuk rombel {{ $kelas->nama }}.</span>
                </label>
            </div>

            <!-- Tombol Aksi -->
            <div class="flex justify-end space-x-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('modalKosongkanNilai').classList.add('hidden')"
                    class="px-4 py-2.5 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                    Batal
                </button>
                <button type="submit"
                    onclick="return confirm('Apakah Anda benar-benar yakin ingin mengosongkan nilai kelas {{ $kelas->nama }}?');"
                    class="px-5 py-2.5 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white shadow-md transition flex items-center">
                    <i class="fa-solid fa-trash-can mr-1.5"></i> Kosongkan Nilai Sekarang
                </button>
            </div>
        </form>
    </div>
</div>
