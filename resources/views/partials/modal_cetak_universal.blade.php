@php
    $cetakSekolah = $sekolah ?? $kelas->sekolah ?? \App\Models\Sekolah::first() ?? new \App\Models\Sekolah;
    $cetakMapelList = $mapelList ?? $kelas->mataPelajaran();
    $cetakSiswaList = $kelasSiswaList ?? $kelas->kelasSiswa()->with('siswa')->get()->sortBy('siswa.nama', SORT_NATURAL | SORT_FLAG_CASE)->values();
    $cetakTahun = (int) ($tahun ?? now()->year);
    $cetakBulan = (int) ($bulan ?? now()->month);
    $cetakSemester = strtolower((string) ($semester ?? $kelas->tahunAjaran->semester ?? 'ganjil'));
    $cetakDefaultPaper = $cetakSekolah->getDefaultPaperSize();
    $cetakDefaultOrientation = $cetakSekolah->getDefaultPaperOrientation();
    $cetakDefaultMargin = $cetakSekolah->getDefaultMarginPreset();
    $cetakDefaultTanggal = $kelas->getTanggalRaport($cetakSemester === 'genap' ? 'sas' : 'sts') ?: now()->format('Y-m-d');
    $canCakupanSekolah = auth()->check() && (auth()->user()->isAdmin() || auth()->user()->isKepsek() || auth()->user()->kelasWali()->count() > 1);
@endphp

<!-- MODAL DIALOG CETAK UNIVERSAL (A4/F4, ORIENTASI, MARGIN, CAKUPAN, FILTER MAPEL, CEK KELENGKAPAN) -->
<div id="modalCetakUniversal" class="fixed inset-0 z-50 hidden bg-slate-900/75 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
    <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 max-w-2xl w-full overflow-hidden flex flex-col max-h-[92vh]">

        <!-- Header -->
        <div class="px-5 py-4 border-b border-slate-100 bg-slate-50 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-blue-600 text-white flex items-center justify-center shadow-sm">
                    <i class="fa-solid fa-print text-sm"></i>
                </div>
                <div>
                    <h3 class="text-sm font-black text-slate-900">Dialog Cetak Dokumen</h3>
                    <p class="text-[11px] text-slate-500">Pilih cakupan, filter mapel opsional, ukuran kertas A4/F4 &amp; periksa kelengkapan</p>
                </div>
            </div>
            <button type="button" onclick="window.closeModalCetakUniversal()" class="w-8 h-8 rounded-lg hover:bg-slate-200/70 text-slate-500 flex items-center justify-center transition cursor-pointer">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Body -->
        <div class="p-5 overflow-y-auto space-y-4 text-xs">
            <form id="formCetakUniversal" onsubmit="return false;" class="space-y-4">
                <!-- 1. Jenis Dokumen & Periode -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="cu_jenis" class="block font-bold text-slate-700 mb-1 uppercase tracking-wider text-[10px]">
                            Jenis Dokumen *
                        </label>
                        <select id="cu_jenis" onchange="window.syncModalCetakFields()"
                                class="w-full px-3 py-2 border border-slate-300 rounded-xl font-semibold text-slate-800 bg-white focus:ring-2 focus:ring-blue-500">
                            <optgroup label="1. Cetak Formatif">
                                <option value="formatif_bulanan">Formatif Bulanan (Semua Mapel)</option>
                                <option value="formatif_semester">Formatif 1 Semester (Semua Mapel)</option>
                            </optgroup>
                            <optgroup label="2. Cetak STS">
                                <option value="sts_daftar">Daftar Nilai STS (Semua Mapel)</option>
                                <option value="sts_lembar">Lembar Rapor STS</option>
                            </optgroup>
                            <optgroup label="3. Cetak SAS">
                                <option value="sas_daftar">Daftar Nilai SAS (Semua Mapel)</option>
                                <option value="sas_lembar">Lembar Rapor SAS</option>
                            </optgroup>
                            <optgroup label="4. Cetak Rapor">
                                <option value="rapor_siswa">Rapor Per Siswa (Nilai, Predikat, Catatan)</option>
                                <option value="rapor_kelas">Rapor Seluruh Kelas (Lengkap)</option>
                                <option value="rapor_leger">Leger Kelas (Semua Mapel)</option>
                                <option value="rapor_predikat_catatan">Rekap Predikat &amp; Catatan Guru</option>
                            </optgroup>
                        </select>
                    </div>

                    <!-- Cakupan Terpadu: Siswa / Kelas / Sekolah -->
                    <div>
                        <label for="cu_cakupan" class="block font-bold text-slate-700 mb-1 uppercase tracking-wider text-[10px]">
                            Cakupan Cetak (Siswa / Kelas / Sekolah) *
                        </label>
                        <select id="cu_cakupan" onchange="window.syncModalCetakFields()"
                                class="w-full px-3 py-2 border border-slate-300 rounded-xl font-semibold text-slate-800 bg-white focus:ring-2 focus:ring-blue-500">
                            <option value="kelas">Per Kelas ({{ $kelas->nama }})</option>
                            <option value="siswa">Per Siswa (1 Siswa Terpilih)</option>
                            @if($canCakupanSekolah)
                                <option value="sekolah">Sekolah (Semua Kelas Aktif)</option>
                            @endif
                        </select>
                    </div>
                </div>

                <!-- 2. Pilihan Siswa (Jika Cakupan = Siswa) & Filter Mapel Opsional -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div id="cu_wrap_siswa" class="hidden">
                        <label for="cu_siswa" class="block font-bold text-slate-700 mb-1 uppercase tracking-wider text-[10px]">
                            Pilih Peserta Didik *
                        </label>
                        <select id="cu_siswa" onchange="window.resetWarningCetak()"
                                class="w-full px-3 py-2 border border-slate-300 rounded-xl font-semibold text-slate-800 bg-white focus:ring-2 focus:ring-blue-500">
                            @foreach($cetakSiswaList as $ksOpt)
                                <option value="{{ $ksOpt->id }}">{{ $ksOpt->siswa->nama }} ({{ $ksOpt->siswa->nis }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div id="cu_wrap_mapel">
                        <label for="cu_mapel" class="block font-bold text-slate-700 mb-1 uppercase tracking-wider text-[10px]">
                            Filter Mata Pelajaran (Opsional)
                        </label>
                        <select id="cu_mapel" onchange="window.resetWarningCetak()"
                                class="w-full px-3 py-2 border border-slate-300 rounded-xl font-semibold text-slate-800 bg-white focus:ring-2 focus:ring-blue-500">
                            <option value="">Semua Mata Pelajaran (1 Dokumen Kolektif)</option>
                            @foreach($cetakMapelList as $mOpt)
                                <option value="{{ $mOpt->id }}">Hanya {{ $mOpt->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div id="cu_wrap_periode">
                        <label for="cu_periode" class="block font-bold text-slate-700 mb-1 uppercase tracking-wider text-[10px]">
                            Periode Asesmen
                        </label>
                        <select id="cu_periode" onchange="window.resetWarningCetak()"
                                class="w-full px-3 py-2 border border-slate-300 rounded-xl font-semibold text-slate-800 bg-white focus:ring-2 focus:ring-blue-500">
                            <option value="sas" selected>SAS / Akhir Semester (Nilai Lengkap)</option>
                            <option value="sts">STS / Tengah Semester</option>
                        </select>
                    </div>
                </div>

                <!-- 3. Bulan & Tahun (Khusus Formatif) -->
                <div id="cu_wrap_bulan" class="hidden grid grid-cols-2 gap-3 bg-purple-50/60 border border-purple-200 p-3 rounded-xl">
                    <div>
                        <label for="cu_bulan" class="block font-bold text-purple-900 mb-1 uppercase tracking-wider text-[10px]">Bulan Formatif</label>
                        <select id="cu_bulan" onchange="window.resetWarningCetak()"
                                class="w-full px-3 py-1.5 border border-purple-300 rounded-lg font-semibold bg-white text-slate-800">
                            @foreach(\App\Models\NilaiFormatifBulanan::NAMA_BULAN as $bNum => $bNama)
                                <option value="{{ $bNum }}" {{ $cetakBulan == $bNum ? 'selected' : '' }}>{{ $bNama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="cu_tahun" class="block font-bold text-purple-900 mb-1 uppercase tracking-wider text-[10px]">Tahun</label>
                        <input type="number" id="cu_tahun" value="{{ $cetakTahun }}" min="2020" max="2035"
                               class="w-full px-3 py-1.5 border border-purple-300 rounded-lg font-semibold bg-white text-slate-800">
                    </div>
                </div>

                <!-- 4. Ukuran Kertas A4/F4, Orientasi, Margin, & Tanggal -->
                <div class="bg-slate-50 border border-slate-200 p-3.5 rounded-xl space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-slate-800 uppercase tracking-wider text-[10px] flex items-center gap-1.5">
                            <i class="fa-solid fa-ruler-combined text-blue-600"></i> Tata Letak Kertas &amp; Margin
                        </span>
                        <span class="text-[10px] text-slate-500">Default Pengaturan: {{ strtoupper($cetakDefaultPaper) }} / {{ ucfirst($cetakDefaultOrientation) }}</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5">
                        <div>
                            <label for="cu_paper" class="block text-[10px] font-bold text-slate-600 mb-1">Ukuran Kertas</label>
                            <select id="cu_paper" class="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg font-bold bg-white text-slate-800">
                                <option value="a4" {{ $cetakDefaultPaper === 'a4' ? 'selected' : '' }}>A4 (210 × 297 mm)</option>
                                <option value="f4" {{ $cetakDefaultPaper === 'f4' ? 'selected' : '' }}>F4 / Folio (215 × 330 mm)</option>
                            </select>
                        </div>

                        <div>
                            <label for="cu_orientation" class="block text-[10px] font-bold text-slate-600 mb-1">Orientasi</label>
                            <select id="cu_orientation" class="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg font-semibold bg-white text-slate-800">
                                <option value="portrait" {{ $cetakDefaultOrientation === 'portrait' ? 'selected' : '' }}>Portrait (Tegak)</option>
                                <option value="landscape" {{ $cetakDefaultOrientation === 'landscape' ? 'selected' : '' }}>Landscape (Mendatar)</option>
                            </select>
                        </div>

                        <div>
                            <label for="cu_margin" class="block text-[10px] font-bold text-slate-600 mb-1">Margin</label>
                            <select id="cu_margin" class="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg font-semibold bg-white text-slate-800">
                                <option value="sempit" {{ $cetakDefaultMargin === 'sempit' ? 'selected' : '' }}>Sempit (6 × 8 mm)</option>
                                <option value="normal" {{ $cetakDefaultMargin === 'normal' ? 'selected' : '' }}>Normal (10 × 12 mm)</option>
                                <option value="lebar" {{ $cetakDefaultMargin === 'lebar' ? 'selected' : '' }}>Lebar (15 × 18 mm)</option>
                            </select>
                        </div>

                        <div>
                            <label for="cu_tanggal" class="block text-[10px] font-bold text-slate-600 mb-1">Tanggal Cetak</label>
                            <input type="date" id="cu_tanggal" value="{{ $cetakDefaultTanggal }}"
                                   class="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg font-semibold bg-white text-slate-800">
                        </div>
                    </div>
                </div>
            </form>

            <!-- PANEL PERINGATAN KELENGKAPAN OTOMATIS -->
            <div id="cu_warning_panel" class="hidden bg-amber-50 border border-amber-300 rounded-2xl p-4 space-y-3">
                <div class="flex items-start gap-2.5">
                    <i class="fa-solid fa-triangle-exclamation text-amber-600 text-lg mt-0.5 shrink-0"></i>
                    <div class="flex-1">
                        <h4 class="text-xs font-black text-amber-950">Data Nilai / Predikat / Catatan Guru Belum Lengkap</h4>
                        <p class="text-[11px] text-amber-800 mt-0.5">
                            Sistem mendeteksi masih ada item yang kosong sebelum cetak. Klik baris di bawah untuk langsung menuju form input terkait, atau pilih tindakan di bawah:
                        </p>
                        <div id="cu_warning_summary" class="flex flex-wrap gap-1.5 mt-2"></div>
                    </div>
                </div>

                <!-- Daftar Item Tidak Lengkap yang Bisa Diklik -->
                <div id="cu_warning_list" class="max-h-48 overflow-y-auto bg-white border border-amber-200 rounded-xl divide-y divide-slate-100"></div>

                <!-- Tombol Pilihan: Lengkapi dulu vs Tetap cetak -->
                <div class="flex flex-wrap items-center justify-end gap-2 pt-1">
                    <a id="cu_btn_lengkapi" href="#"
                       class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-xl text-xs shadow-xs transition inline-flex items-center gap-1.5">
                        <i class="fa-solid fa-pen-to-square"></i>
                        <span>Lengkapi dulu</span>
                    </a>
                    <button type="button" onclick="window.proceedForceCetak()"
                            class="px-4 py-2 bg-slate-900 hover:bg-black text-white font-bold rounded-xl text-xs shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                        <i class="fa-solid fa-print text-amber-300"></i>
                        <span>Tetap cetak</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="px-5 py-3.5 border-t border-slate-100 bg-slate-50 flex items-center justify-between shrink-0">
            <button type="button" onclick="window.closeModalCetakUniversal()"
                    class="px-4 py-2 rounded-xl border border-slate-300 bg-white hover:bg-slate-100 text-slate-700 font-bold text-xs transition cursor-pointer">
                Tutup
            </button>
            <button id="cu_btn_submit" type="button" onclick="window.submitModalCetakUniversal()"
                    class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-md transition flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-file-pdf"></i>
                <span id="cu_btn_submit_text">Periksa &amp; Cetak PDF</span>
            </button>
        </div>
    </div>
</div>

<script>
    (function () {
        const urlCekKelengkapan = @json(route('kelas.cetak.cek-kelengkapan', $kelas->id));
        const urlCetakDokumen = @json(route('kelas.cetak.dokumen', $kelas->id));
        const defaultPaper = @json($cetakDefaultPaper);
        const defaultOrientation = @json($cetakDefaultOrientation);
        const defaultMargin = @json($cetakDefaultMargin);
        const defaultSemester = @json($cetakSemester);

        window.resetWarningCetak = function () {
            const panel = document.getElementById('cu_warning_panel');
            if (panel) {
                panel.classList.add('hidden');
            }
        };

        window.syncModalCetakFields = function () {
            const jenis = document.getElementById('cu_jenis').value;
            const cakupanEl = document.getElementById('cu_cakupan');
            if (jenis === 'rapor_siswa' && cakupanEl.value !== 'siswa') {
                cakupanEl.value = 'siswa';
            }

            const cakupan = cakupanEl.value;
            const wrapSiswa = document.getElementById('cu_wrap_siswa');
            const wrapBulan = document.getElementById('cu_wrap_bulan');
            const wrapPeriode = document.getElementById('cu_wrap_periode');

            wrapSiswa.classList.toggle('hidden', cakupan !== 'siswa');
            wrapBulan.classList.toggle('hidden', jenis !== 'formatif_bulanan' && jenis !== 'formatif_semester');
            wrapPeriode.classList.toggle('hidden', !jenis.startsWith('rapor_'));

            window.resetWarningCetak();
        };

        window.openModalCetakUniversal = function (opts = {}) {
            const modal = document.getElementById('modalCetakUniversal');
            if (!modal) {
                return;
            }

            document.getElementById('cu_jenis').value = opts.jenis || 'rapor_kelas';
            document.getElementById('cu_cakupan').value = opts.cakupan || (opts.jenis === 'rapor_siswa' ? 'siswa' : 'kelas');
            document.getElementById('cu_mapel').value = opts.mapel_id ? String(opts.mapel_id) : '';
            document.getElementById('cu_paper').value = opts.paper || defaultPaper;
            document.getElementById('cu_orientation').value = opts.orientation || defaultOrientation;
            document.getElementById('cu_margin').value = opts.margin || defaultMargin;

            if (opts.kelas_siswa_id) {
                document.getElementById('cu_siswa').value = String(opts.kelas_siswa_id);
            }
            if (opts.bulan) {
                document.getElementById('cu_bulan').value = String(opts.bulan);
            }
            if (opts.tahun) {
                document.getElementById('cu_tahun').value = String(opts.tahun);
            }
            if (opts.periode) {
                document.getElementById('cu_periode').value = opts.periode;
            }

            window.syncModalCetakFields();
            window.resetWarningCetak();
            modal.classList.remove('hidden');
        };

        window.closeModalCetakUniversal = function () {
            const modal = document.getElementById('modalCetakUniversal');
            if (modal) {
                modal.classList.add('hidden');
            }
        };

        function buildCetakParams() {
            const jenis = document.getElementById('cu_jenis').value;
            const cakupan = document.getElementById('cu_cakupan').value;
            const params = new URLSearchParams({
                jenis: jenis,
                cakupan: cakupan,
                mapel_id: document.getElementById('cu_mapel').value || '',
                kelas_siswa_id: cakupan === 'siswa' ? (document.getElementById('cu_siswa').value || '') : '',
                bulan: document.getElementById('cu_bulan').value || '',
                tahun: document.getElementById('cu_tahun').value || '',
                semester: defaultSemester,
                periode: jenis.startsWith('sts_') ? 'sts' : (jenis.startsWith('sas_') ? 'sas' : (document.getElementById('cu_periode').value || 'sas')),
                paper: document.getElementById('cu_paper').value || defaultPaper,
                orientation: document.getElementById('cu_orientation').value || defaultOrientation,
                margin: document.getElementById('cu_margin').value || defaultMargin,
                tanggal: document.getElementById('cu_tanggal').value || '',
            });
            return params;
        }

        window.proceedForceCetak = function () {
            const params = buildCetakParams();
            window.open(urlCetakDokumen + '?' + params.toString(), '_blank');
            window.closeModalCetakUniversal();
        };

        window.submitModalCetakUniversal = async function () {
            const btn = document.getElementById('cu_btn_submit');
            const btnText = document.getElementById('cu_btn_submit_text');
            const params = buildCetakParams();

            btn.disabled = true;
            btnText.textContent = 'Memeriksa kelengkapan...';

            try {
                const res = await fetch(urlCekKelengkapan + '?' + params.toString(), {
                    headers: { 'Accept': 'application/json' },
                });
                const data = await res.json();

                if (data.is_complete) {
                    window.proceedForceCetak();
                    return;
                }

                // Tampilkan panel peringatan dengan daftar item yang bisa diklik
                const summaryEl = document.getElementById('cu_warning_summary');
                const listEl = document.getElementById('cu_warning_list');
                const btnLengkapi = document.getElementById('cu_btn_lengkapi');

                const s = data.summary || {};
                summaryEl.innerHTML = '';
                const badges = [
                    { label: 'Nilai Kosong', count: s.nilai_kosong || 0, cls: 'bg-rose-100 text-rose-800 border-rose-200' },
                    { label: 'Predikat Kosong', count: s.predikat_kosong || 0, cls: 'bg-amber-100 text-amber-800 border-amber-200' },
                    { label: 'Catatan Guru Kosong', count: s.catatan_kosong || 0, cls: 'bg-blue-100 text-blue-800 border-blue-200' },
                ];
                badges.forEach(b => {
                    if (b.count > 0) {
                        const span = document.createElement('span');
                        span.className = 'px-2 py-0.5 rounded-full text-[10px] font-bold border ' + b.cls;
                        span.textContent = b.label + ': ' + b.count;
                        summaryEl.appendChild(span);
                    }
                });

                listEl.innerHTML = '';
                const items = data.items || [];
                items.forEach(item => {
                    const a = document.createElement('a');
                    a.href = item.url;
                    a.className = 'flex items-center justify-between gap-2 px-3 py-2 hover:bg-amber-50/80 transition text-[11px]';

                    const left = document.createElement('div');
                    left.className = 'min-w-0';
                    left.innerHTML = `<div class="font-bold text-slate-900 truncate">${escapeHtml(item.siswa_nama)} <span class="font-normal text-slate-400">(${escapeHtml(item.kelas_nama)})</span></div>
                        <div class="text-[10px] text-slate-600">${escapeHtml(item.mapel_nama)} &bull; <span class="text-rose-700 font-semibold">${escapeHtml((item.masalah || []).join(', '))}</span></div>`;

                    const right = document.createElement('span');
                    right.className = 'shrink-0 px-2 py-0.5 rounded-lg bg-slate-100 text-slate-700 font-bold text-[10px]';
                    right.innerHTML = 'Isi <i class="fa-solid fa-arrow-up-right-from-square text-[9px] ml-0.5"></i>';

                    a.appendChild(left);
                    a.appendChild(right);
                    listEl.appendChild(a);
                });

                btnLengkapi.href = items.length > 0 ? items[0].url : '#';
                document.getElementById('cu_warning_panel').classList.remove('hidden');
            } catch (err) {
                window.proceedForceCetak();
            } finally {
                btn.disabled = false;
                btnText.textContent = 'Periksa & Cetak PDF';
            }
        };

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text == null ? '' : String(text);
            return div.innerHTML;
        }
    })();
</script>

