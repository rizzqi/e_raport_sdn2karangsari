@php
    $activeMode = $activeMode ?? 'mapel';
    $dropdownUid = 'dropdown_input_' . uniqid();
@endphp
<div class="dropdown relative inline-block text-left" 
     :class="{ 'z-50': open, 'z-10': !open }"
     x-data="{ 
         open: false,
         id: '{{ $dropdownUid }}',
         toggle() {
             this.open = !this.open;
             if (this.open) {
                 window.dispatchEvent(new CustomEvent('close-dropdowns', { detail: { except: this.id } }));
             }
         }
     }"
     @close-dropdowns.window="if ($event.detail.except !== id) open = false"
     @keydown.escape.window="open = false"
     @click.outside="open = false">

    <!-- Tombol Trigger Dropdown -->
    <button type="button" @click="toggle()"
            id="btnDropdownInputNilai"
            class="px-3.5 py-2 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white transition shadow-sm flex items-center gap-1.5 cursor-pointer">
        <i class="fa-solid fa-pen-to-square"></i>
        <span>Input Nilai</span>
        <i class="fa-solid fa-chevron-down text-[10px] ml-0.5 transition-transform" :class="{ 'rotate-180': open }"></i>
    </button>

    <!-- Backdrop Gelap di Layar HP (<= 640px) -->
    <div x-show="open" 
         x-transition:enter="transition-opacity ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="open = false"
         class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 sm:hidden"
         style="display: none;"></div>

    <!-- Menu Dropdown (Desktop: Popover, Mobile: Bottom Sheet) -->
    <div x-show="open" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="translate-y-full sm:translate-y-0 sm:opacity-0 sm:scale-95"
         x-transition:enter-end="translate-y-0 sm:opacity-100 sm:scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="translate-y-0 sm:opacity-100 sm:scale-100"
         x-transition:leave-end="translate-y-full sm:translate-y-0 sm:opacity-0 sm:scale-95"
         class="dropdown-menu absolute right-0 mt-2 w-72 bg-white rounded-2xl border border-slate-200 py-2 z-50 divide-y divide-slate-100 shadow-2xl max-h-[70vh] sm:max-h-[80vh] overflow-y-auto scrollbar-thin"
         style="display: none;">

        <!-- Mobile Header & Drag Handle (Khusus HP <=640px) -->
        <div class="sm:hidden px-4 pt-2.5 pb-2 border-b border-slate-100 bg-slate-50/80">
            <div class="w-10 h-1 bg-slate-300 rounded-full mx-auto mb-2"></div>
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-pen-to-square text-emerald-600"></i>
                    <span>Menu Input Nilai &amp; Rapor</span>
                </span>
                <button type="button" @click="open = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>
        </div>

        <div class="py-1">
            <div class="px-3.5 py-1 text-[10px] font-black uppercase text-slate-400 bg-slate-50 tracking-wider">
                4 Form Input Terpisah (1 Arah)
            </div>

            @if($mapel)
                <a href="{{ route('kelas.mapel.formatif-harian', ['kelas' => $kelas->id, 'mapel' => $mapel->id, 'tahun' => $tahun]) }}"
                   class="flex items-center justify-between gap-2 px-4 py-2.5 min-h-[44px] sm:min-h-0 sm:py-2 sm:px-3.5 text-xs font-semibold transition {{ $activeMode === 'harian' ? 'bg-emerald-50 text-emerald-800 font-bold' : 'text-slate-700 hover:bg-slate-50 hover:text-emerald-700' }}">
                    <span class="flex items-center gap-2.5">
                        <span class="w-5 h-5 rounded-md bg-emerald-100 text-emerald-700 font-black text-[10px] flex items-center justify-center shrink-0">1</span>
                        <span>Formatif Harian</span>
                    </span>
                    <span class="text-[10px] text-slate-400 font-medium">TP / Tugas</span>
                </a>

                <a href="{{ route('kelas.mapel.nilai', ['kelas' => $kelas->id, 'mapel' => $mapel->id, 'tab' => 'formatif', 'tahun' => $tahun]) }}"
                   class="flex items-center justify-between gap-2 px-4 py-2.5 min-h-[44px] sm:min-h-0 sm:py-2 sm:px-3.5 text-xs font-semibold transition {{ $activeMode === 'formatif' ? 'bg-blue-50 text-blue-800 font-bold' : 'text-slate-700 hover:bg-slate-50 hover:text-blue-700' }}">
                    <span class="flex items-center gap-2.5">
                        <span class="w-5 h-5 rounded-md bg-blue-100 text-blue-700 font-black text-[10px] flex items-center justify-center shrink-0">2</span>
                        <span>Formatif Bulanan</span>
                    </span>
                    <span class="text-[10px] text-slate-400 font-medium">Rekap Bulan</span>
                </a>

                <a href="{{ route('kelas.mapel.nilai', ['kelas' => $kelas->id, 'mapel' => $mapel->id, 'tab' => 'sts', 'tahun' => $tahun]) }}"
                   class="flex items-center justify-between gap-2 px-4 py-2.5 min-h-[44px] sm:min-h-0 sm:py-2 sm:px-3.5 text-xs font-semibold transition {{ $activeMode === 'sts' ? 'bg-amber-50 text-amber-900 font-bold' : 'text-slate-700 hover:bg-slate-50 hover:text-amber-700' }}">
                    <span class="flex items-center gap-2.5">
                        <span class="w-5 h-5 rounded-md bg-amber-100 text-amber-800 font-black text-[10px] flex items-center justify-center shrink-0">3</span>
                        <span>Form Nilai STS</span>
                    </span>
                    <span class="text-[10px] text-slate-400 font-medium">Tengah Smt</span>
                </a>

                <a href="{{ route('kelas.mapel.nilai', ['kelas' => $kelas->id, 'mapel' => $mapel->id, 'tab' => 'sas', 'tahun' => $tahun]) }}"
                   class="flex items-center justify-between gap-2 px-4 py-2.5 min-h-[44px] sm:min-h-0 sm:py-2 sm:px-3.5 text-xs font-semibold transition {{ $activeMode === 'sas' ? 'bg-indigo-50 text-indigo-800 font-bold' : 'text-slate-700 hover:bg-slate-50 hover:text-indigo-700' }}">
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
                   class="flex items-center gap-2.5 px-4 py-2.5 min-h-[44px] sm:min-h-0 sm:py-2 sm:px-3.5 text-xs font-semibold transition {{ $activeMode === 'mapel' ? 'bg-slate-100 text-slate-900 font-bold' : 'text-slate-700 hover:bg-slate-50' }}">
                    <i class="fa-solid fa-eye text-emerald-600 w-4 text-center"></i>
                    <span>Lembar Nilai Rapor (Read-Only)</span>
                </a>
                <a href="{{ route('kelas.mapel.nilai', ['kelas' => $kelas->id, 'mapel' => $mapel->id, 'tab' => 'rapor', 'tahun' => $tahun]) }}"
                   class="flex items-center gap-2.5 px-4 py-2.5 min-h-[44px] sm:min-h-0 sm:py-2 sm:px-3.5 text-xs font-semibold transition {{ $activeMode === 'rapor' ? 'bg-indigo-50 text-indigo-800 font-bold' : 'text-slate-700 hover:bg-slate-50' }}">
                    <i class="fa-solid fa-wand-magic-sparkles text-indigo-600 w-4 text-center"></i>
                    <span>Catatan Guru (Otomatis)</span>
                </a>
            @endif
            <a href="{{ route('kelas.mapel.nilai', ['kelas' => $kelas->id, 'mapel' => $mapel?->id, 'tab' => 'pembiasaan', 'tahun' => $tahun]) }}"
               class="flex items-center gap-2.5 px-4 py-2.5 min-h-[44px] sm:min-h-0 sm:py-2 sm:px-3.5 text-xs font-semibold transition {{ $activeMode === 'pembiasaan' ? 'bg-amber-50 text-amber-800 font-bold' : 'text-slate-700 hover:bg-slate-50' }}">
                <i class="fa-solid fa-star text-amber-500 w-4 text-center"></i>
                <span>Pembiasaan &amp; Kehadiran</span>
            </a>
            <a href="{{ route('kelas.mapel.nilai', ['kelas' => $kelas->id, 'mapel' => $mapel?->id, 'tab' => 'ekstrakurikuler', 'tahun' => $tahun]) }}"
               class="flex items-center gap-2.5 px-4 py-2.5 min-h-[44px] sm:min-h-0 sm:py-2 sm:px-3.5 text-xs font-semibold transition {{ ($tab ?? '') === 'ekstrakurikuler' ? 'bg-teal-50 text-teal-800' : 'text-slate-700 hover:bg-slate-50' }}">
                <i class="fa-solid fa-campground w-4 text-center {{ ($tab ?? '') === 'ekstrakurikuler' ? 'text-teal-600' : 'text-slate-400' }}"></i>
                <span>Ekstrakurikuler</span>
            </a>
        </div>
    </div>
</div>
