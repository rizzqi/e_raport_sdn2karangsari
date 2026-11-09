<!-- Modal Edit Nama dan NIP Wali Kelas -->
<div id="modalEditWaliKelas" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl p-6 border border-slate-100">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div>
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                    <i class="fa-solid fa-user-pen text-amber-600 mr-2 text-base"></i> Edit Wali Kelas &amp; NIP
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">{{ $kelas->nama }} &bull; TP {{ $kelas->tahunAjaran->nama_lengkap ?? '-' }}</p>
            </div>
            <button type="button" onclick="document.getElementById('modalEditWaliKelas').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-2xl leading-none">&times;</button>
        </div>

        <form action="{{ route('kelas.update.wali', $kelas->id) }}" method="POST" class="space-y-4 mt-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                    Nama Lengkap &amp; Gelar Wali Kelas *
                </label>
                <input type="text" name="wali_nama" value="{{ old('wali_nama', $kelas->wali_nama ?: ($kelas->wali->name ?? '')) }}" required
                    placeholder="Contoh: Dewi Lestari, S.Pd."
                    class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm font-semibold focus:ring-2 focus:ring-emerald-500">
                <span class="text-[11px] text-slate-500 mt-1 block">Nama ini akan dicetak pada tanda tangan raport semester.</span>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                    Nomor Induk Pegawai (NIP)
                </label>
                <input type="text" name="wali_nip" value="{{ old('wali_nip', $kelas->wali_nip) }}"
                    placeholder="Contoh: 19880315 201101 2 008"
                    class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm font-mono focus:ring-2 focus:ring-emerald-500">
                <span class="text-[11px] text-slate-500 mt-1 block">Kosongkan atau beri tanda strip (-) jika belum memiliki NIP.</span>
            </div>

            <div class="pt-3 border-t border-slate-100 flex justify-end space-x-2">
                <button type="button" onclick="document.getElementById('modalEditWaliKelas').classList.add('hidden')"
                    class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-emerald-600 text-white hover:bg-emerald-700 shadow-sm flex items-center transition">
                    <i class="fa-solid fa-check mr-1.5"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
