@php
    $dropdownLabel = $label ?? 'Cetak';
    $btnClass = $buttonClass ?? 'px-3.5 py-2 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white transition shadow-sm flex items-center gap-1.5 cursor-pointer';
    $dropdownUid = 'dropdown_cetak_' . uniqid();
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
    <button type="button" @click="toggle()" class="{{ $btnClass }}">
        <i class="fa-solid fa-print"></i>
        <span>{{ $dropdownLabel }}</span>
        <i class="fa-solid fa-chevron-down text-[10px] ml-0.5 opacity-80 transition-transform" :class="{ 'rotate-180': open }"></i>
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
         class="dropdown-menu absolute right-0 mt-2 w-72 bg-white rounded-2xl border border-slate-200 py-2 divide-y divide-slate-100 shadow-2xl z-50 max-h-[70vh] sm:max-h-[80vh] overflow-y-auto scrollbar-thin"
         style="display: none;">

        <!-- Mobile Header & Drag Handle (Khusus HP <=640px) -->
        <div class="sm:hidden px-4 pt-2.5 pb-2 border-b border-slate-100 bg-slate-50/80">
            <div class="w-10 h-1 bg-slate-300 rounded-full mx-auto mb-2"></div>
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-print text-blue-600"></i>
                    <span>Menu Cetak Dokumen</span>
                </span>
                <button type="button" @click="open = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>
        </div>

        <!-- 1. Cetak Formatif -->
        <div>
            <div class="px-3.5 py-1.5 text-[10px] font-black uppercase tracking-wider text-purple-700 bg-purple-50/70">
                1. Cetak Formatif
            </div>
            <div class="py-1">
                <button type="button" @click="open = false; window.openModalCetakUniversal({ jenis: 'formatif_bulanan', cakupan: 'kelas' })"
                        class="w-full text-left flex items-center gap-2.5 px-4 py-2.5 min-h-[44px] sm:min-h-0 sm:py-2 sm:px-3.5 text-xs font-semibold text-slate-700 hover:bg-purple-50 hover:text-purple-900 transition cursor-pointer">
                    <i class="fa-solid fa-calendar-day text-purple-500 w-4 text-center"></i>
                    <span>Formatif Per Bulan (Semua Mapel)</span>
                </button>
                <button type="button" @click="open = false; window.openModalCetakUniversal({ jenis: 'formatif_semester', cakupan: 'kelas' })"
                        class="w-full text-left flex items-center gap-2.5 px-4 py-2.5 min-h-[44px] sm:min-h-0 sm:py-2 sm:px-3.5 text-xs font-semibold text-slate-700 hover:bg-purple-50 hover:text-purple-900 transition cursor-pointer">
                    <i class="fa-solid fa-calendar-days text-purple-600 w-4 text-center"></i>
                    <span>Formatif 1 Semester (Semua Mapel)</span>
                </button>
            </div>
        </div>

        <!-- 2. Cetak STS -->
        <div>
            <div class="px-3.5 py-1.5 text-[10px] font-black uppercase tracking-wider text-amber-700 bg-amber-50/70">
                2. Cetak STS
            </div>
            <div class="py-1">
                <button type="button" @click="open = false; window.openModalCetakUniversal({ jenis: 'sts_daftar', cakupan: 'kelas' })"
                        class="w-full text-left flex items-center gap-2.5 px-4 py-2.5 min-h-[44px] sm:min-h-0 sm:py-2 sm:px-3.5 text-xs font-semibold text-slate-700 hover:bg-amber-50 hover:text-amber-900 transition cursor-pointer">
                    <i class="fa-solid fa-table-list text-amber-600 w-4 text-center"></i>
                    <span>Daftar Nilai STS (Semua Mapel)</span>
                </button>
                <button type="button" @click="open = false; window.openModalCetakUniversal({ jenis: 'sts_lembar', cakupan: 'kelas' })"
                        class="w-full text-left flex items-center gap-2.5 px-4 py-2.5 min-h-[44px] sm:min-h-0 sm:py-2 sm:px-3.5 text-xs font-semibold text-slate-700 hover:bg-amber-50 hover:text-amber-900 transition cursor-pointer">
                    <i class="fa-solid fa-sheet-plastic text-amber-500 w-4 text-center"></i>
                    <span>Lembar Rapor STS</span>
                </button>
            </div>
        </div>

        <!-- 3. Cetak SAS -->
        <div>
            <div class="px-3.5 py-1.5 text-[10px] font-black uppercase tracking-wider text-blue-700 bg-blue-50/70">
                3. Cetak SAS
            </div>
            <div class="py-1">
                <button type="button" @click="open = false; window.openModalCetakUniversal({ jenis: 'sas_daftar', cakupan: 'kelas' })"
                        class="w-full text-left flex items-center gap-2.5 px-4 py-2.5 min-h-[44px] sm:min-h-0 sm:py-2 sm:px-3.5 text-xs font-semibold text-slate-700 hover:bg-blue-50 hover:text-blue-900 transition cursor-pointer">
                    <i class="fa-solid fa-table-list text-blue-600 w-4 text-center"></i>
                    <span>Daftar Nilai SAS (Semua Mapel)</span>
                </button>
                <button type="button" @click="open = false; window.openModalCetakUniversal({ jenis: 'sas_lembar', cakupan: 'kelas' })"
                        class="w-full text-left flex items-center gap-2.5 px-4 py-2.5 min-h-[44px] sm:min-h-0 sm:py-2 sm:px-3.5 text-xs font-semibold text-slate-700 hover:bg-blue-50 hover:text-blue-900 transition cursor-pointer">
                    <i class="fa-solid fa-sheet-plastic text-blue-500 w-4 text-center"></i>
                    <span>Lembar Rapor SAS</span>
                </button>
            </div>
        </div>

        <!-- 4. Cetak Rapor (Nilai Akhir, Predikat & Catatan Guru) -->
        <div>
            <div class="px-3.5 py-1.5 text-[10px] font-black uppercase tracking-wider text-emerald-700 bg-emerald-50/70">
                4. Cetak Rapor
            </div>
            <div class="py-1">
                <button type="button" @click="open = false; window.openModalCetakUniversal({ jenis: 'rapor_siswa', cakupan: 'siswa' })"
                        class="w-full text-left flex items-center gap-2.5 px-4 py-2.5 min-h-[44px] sm:min-h-0 sm:py-2 sm:px-3.5 text-xs font-semibold text-slate-700 hover:bg-emerald-50 hover:text-emerald-900 transition cursor-pointer">
                    <i class="fa-solid fa-user-graduate text-emerald-600 w-4 text-center"></i>
                    <span>Rapor Per Siswa</span>
                </button>
                <button type="button" @click="open = false; window.openModalCetakUniversal({ jenis: 'rapor_kelas', cakupan: 'kelas' })"
                        class="w-full text-left flex items-center gap-2.5 px-4 py-2.5 min-h-[44px] sm:min-h-0 sm:py-2 sm:px-3.5 text-xs font-semibold text-slate-700 hover:bg-emerald-50 hover:text-emerald-900 transition cursor-pointer">
                    <i class="fa-solid fa-users text-emerald-600 w-4 text-center"></i>
                    <span>Rapor Seluruh Kelas</span>
                </button>
                <button type="button" @click="open = false; window.openModalCetakUniversal({ jenis: 'rapor_leger', cakupan: 'kelas', orientation: 'landscape' })"
                        class="w-full text-left flex items-center gap-2.5 px-4 py-2.5 min-h-[44px] sm:min-h-0 sm:py-2 sm:px-3.5 text-xs font-semibold text-slate-700 hover:bg-emerald-50 hover:text-emerald-900 transition cursor-pointer">
                    <i class="fa-solid fa-book-open text-teal-600 w-4 text-center"></i>
                    <span>Leger Kelas</span>
                </button>
                <button type="button" @click="open = false; window.openModalCetakUniversal({ jenis: 'rapor_predikat_catatan', cakupan: 'kelas' })"
                        class="w-full text-left flex items-center gap-2.5 px-4 py-2.5 min-h-[44px] sm:min-h-0 sm:py-2 sm:px-3.5 text-xs font-semibold text-slate-700 hover:bg-emerald-50 hover:text-emerald-900 transition cursor-pointer">
                    <i class="fa-solid fa-clipboard-check text-emerald-700 w-4 text-center"></i>
                    <span>Rekap Predikat &amp; Catatan</span>
                </button>
            </div>
        </div>
    </div>
</div>
