@php
    $activeMode = $activeMode ?? 'mapel';
@endphp
<div class="relative inline-block text-left" x-data="{ open: false }">
    <button type="button" @click="open = !open" @click.outside="open = false"
            id="btnDropdownInputNilai"
            class="px-3.5 py-2 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white transition shadow-sm flex items-center gap-1.5 cursor-pointer">
        <i class="fa-solid fa-pen-to-square"></i>
        <span>Input Nilai</span>
        <i class="fa-solid fa-chevron-down text-[10px] ml-0.5 transition-transform" :class="{ 'rotate-180': open }"></i>
    </button>
    <div x-show="open" x-transition
         class="absolute right-0 mt-2 w-72 bg-white rounded-2xl border border-slate-200 py-2 z-50 divide-y divide-slate-100 shadow-2xl" style="display: none;">

        <div class="py-1">
            <div class="px-3.5 py-1 text-[10px] font-black uppercase text-slate-400 bg-slate-50 tracking-wider">
                4 Form Input Terpisah (1 Arah)
            </div>

            @if($mapel)
                <a href="{{ route('kelas.mapel.formatif-harian', ['kelas' => $kelas->id, 'mapel' => $mapel->id, 'tahun' => $tahun]) }}"
                   class="flex items-center justify-between gap-2 px-3.5 py-2.5 text-xs font-semibold transition {{ $activeMode === 'harian' ? 'bg-emerald-50 text-emerald-800 font-bold' : 'text-slate-700 hover:bg-slate-50 hover:text-emerald-700' }}">
                    <span class="flex items-center gap-2.5">
                        <span class="w-5 h-5 rounded-md bg-emerald-100 text-emerald-700 font-black text-[10px] flex items-center justify-center shrink-0">1</span>
                        <span>Formatif Harian</span>
                    </span>
                    <span class="text-[10px] text-slate-400 font-medium">TP / Tugas</span>
                </a>

                <a href="{{ route('kelas.mapel.nilai', ['kelas' => $kelas->id, 'mapel' => $mapel->id, 'tab' => 'formatif', 'tahun' => $tahun]) }}"
                   class="flex items-center justify-between gap-2 px-3.5 py-2.5 text-xs font-semibold transition {{ $activeMode === 'formatif' ? 'bg-blue-50 text-blue-800 font-bold' : 'text-slate-700 hover:bg-slate-50 hover:text-blue-700' }}">
                    <span class="flex items-center gap-2.5">
                        <span class="w-5 h-5 rounded-md bg-blue-100 text-blue-700 font-black text-[10px] flex items-center justify-center shrink-0">2</span>
                        <span>Formatif Bulanan</span>
                    </span>
                    <span class="text-[10px] text-slate-400 font-medium">Rekap Bulan</span>
                </a>

                <a href="{{ route('kelas.mapel.nilai', ['kelas' => $kelas->id, 'mapel' => $mapel->id, 'tab' => 'sts', 'tahun' => $tahun]) }}"
                   class="flex items-center justify-between gap-2 px-3.5 py-2.5 text-xs font-semibold transition {{ $activeMode === 'sts' ? 'bg-amber-50 text-amber-900 font-bold' : 'text-slate-700 hover:bg-slate-50 hover:text-amber-700' }}">
                    <span class="flex items-center gap-2.5">
                        <span class="w-5 h-5 rounded-md bg-amber-100 text-amber-800 font-black text-[10px] flex items-center justify-center shrink-0">3</span>
                        <span>Form Nilai STS</span>
                    </span>
                    <span class="text-[10px] text-slate-400 font-medium">Tengah Smt</span>
                </a>

                <a href="{{ route('kelas.mapel.nilai', ['kelas' => $kelas->id, 'mapel' => $mapel->id, 'tab' => 'sas', 'tahun' => $tahun]) }}"
                   class="flex items-center justify-between gap-2 px-3.5 py-2.5 text-xs font-semibold transition {{ $activeMode === 'sas' ? 'bg-indigo-50 text-indigo-800 font-bold' : 'text-slate-700 hover:bg-slate-50 hover:text-indigo-700' }}">
                    <span class="flex items-center gap-2.5">
                        <span class="w-5 h-5 rounded-md bg-indigo-100 text-indigo-700 font-black text-[10px] flex items-center justify-center shrink-0">4</span>
                        <span>Form Nilai SAS</span>
                    </span>
                    <span class="text-[10px] text-slate-400 font-medium">Akhir Smt</span>
                </a>
            @endif
        </div>

        <div class="py-1">
            <div class="px-3.5 py-1 text-[10px] font-black uppercase text-slate-400 bg-slate-50 tracking-wider">
                Rekap &amp; Catatan
            </div>
            @if($mapel)
                <a href="{{ route('kelas.mapel.nilai', ['kelas' => $kelas->id, 'mapel' => $mapel->id, 'tab' => 'mapel', 'tahun' => $tahun]) }}"
                   class="flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold transition {{ $activeMode === 'mapel' ? 'bg-slate-100 text-slate-900 font-bold' : 'text-slate-700 hover:bg-slate-50' }}">
                    <i class="fa-solid fa-eye text-emerald-600 w-4 text-center"></i>
                    <span>Lembar Nilai Rapor (Read-Only)</span>
                </a>
                <a href="{{ route('kelas.mapel.nilai', ['kelas' => $kelas->id, 'mapel' => $mapel->id, 'tab' => 'rapor', 'tahun' => $tahun]) }}"
                   class="flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold transition {{ $activeMode === 'rapor' ? 'bg-indigo-50 text-indigo-800 font-bold' : 'text-slate-700 hover:bg-slate-50' }}">
                    <i class="fa-solid fa-wand-magic-sparkles text-indigo-600 w-4 text-center"></i>
                    <span>Catatan Guru (Otomatis)</span>
                </a>
            @endif
            <a href="{{ route('kelas.mapel.nilai', ['kelas' => $kelas->id, 'mapel' => $mapel?->id, 'tab' => 'pembiasaan', 'tahun' => $tahun]) }}"
               class="flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold transition {{ $activeMode === 'pembiasaan' ? 'bg-amber-50 text-amber-800 font-bold' : 'text-slate-700 hover:bg-slate-50' }}">
                <i class="fa-solid fa-star text-amber-500 w-4 text-center"></i>
                <span>Pembiasaan &amp; Kehadiran</span>
            </a>
            <a href="{{ route('kelas.mapel.nilai', ['kelas' => $kelas->id, 'mapel' => $mapel?->id, 'tab' => 'ekstrakurikuler', 'tahun' => $tahun]) }}"
               class="flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-semibold transition {{ ($tab ?? '') === 'ekstrakurikuler' ? 'bg-teal-50 text-teal-800' : 'text-slate-700 hover:bg-slate-50' }}">
                <i class="fa-solid fa-campground w-4 text-center {{ ($tab ?? '') === 'ekstrakurikuler' ? 'text-teal-600' : 'text-slate-400' }}"></i>
                <span>Ekstrakurikuler</span>
            </a>
        </div>
    </div>
</div>

