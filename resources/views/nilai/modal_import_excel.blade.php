<!-- Modal Import Nilai dari File Excel, PDF, atau Clipboard -->
<div id="modalImportNilai" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-3xl max-w-xl w-full shadow-2xl border border-slate-100 overflow-hidden animate-in fade-in duration-200">

        <!-- Header Modal -->
        <div class="p-5 border-b border-slate-100 bg-slate-50/70 flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-slate-900 flex items-center">
                    <i class="fa-solid fa-file-arrow-up text-emerald-600 mr-2 text-lg"></i>
                    Import Nilai & Data &bull; {{ $kelas->nama }}
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">Unggah file spreadsheet (Excel / CSV), dokumen PDF (.pdf), atau salin-tempel dari clipboard</p>
            </div>
            <button type="button" onclick="document.getElementById('modalImportNilai').classList.add('hidden')"
                class="w-8 h-8 rounded-full bg-slate-200/60 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form action="{{ route('nilai.import.excel', $kelas->id) }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
            @csrf

            <!-- Pilihan Periode & Target -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Periode Penilaian *</label>
                    <select name="periode" id="import_periode" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs font-bold text-slate-800 focus:ring-2 focus:ring-emerald-500">
                        <option value="sts" {{ (isset($periode) && $periode === 'sts') ? 'selected' : '' }}>STS (Sumatif Tengah Semester)</option>
                        <option value="sas" {{ (isset($periode) && $periode === 'sas') ? 'selected' : '' }}>SAS (Sumatif Akhir Semester)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Target Mata Pelajaran</label>
                    <select name="mapel_id" id="import_mapel_id" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs font-bold text-slate-800 focus:ring-2 focus:ring-emerald-500">
                        <option value="">Otomatis Deteksi Semua Mapel (Sesuai Kolom Header)</option>
                        @foreach(\App\Models\MataPelajaran::aktif()->get() as $m)
                            <option value="{{ $m->id }}" {{ (isset($activeMapel) && $activeMapel->id === $m->id) ? 'selected' : '' }}>
                                Khusus: {{ $m->nama }} (KKM: {{ $m->kkm ?? 70 }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Download Template Box -->
            <div class="p-3.5 bg-emerald-50/70 border border-emerald-200 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-start space-x-2">
                    <i class="fa-solid fa-file-excel text-emerald-600 text-base mt-0.5 shrink-0"></i>
                    <div class="text-xs text-emerald-950">
                        <p class="font-bold">Unduh Template Spreadsheet Resmi (.xlsx)</p>
                        <p class="text-[11px] text-emerald-800">Template telah terisi nomor urut, NIS, dan nama siswa di kelas ini.</p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-1.5 shrink-0">
                    <a id="btnDownloadTemplateSemua"
                       href="{{ route('nilai.template.excel', ['kelas' => $kelas->id, 'mode' => 'semua', 'periode' => isset($periode) ? $periode : 'sts']) }}"
                       class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-[11px] font-bold transition flex items-center shadow-xs"
                       title="Unduh template spreadsheet .xlsx untuk seluruh mata pelajaran sekaligus">
                        <i class="fa-solid fa-download mr-1"></i> Semua Mapel
                    </a>
                    @if(isset($activeMapel) && $activeMapel)
                        <a id="btnDownloadTemplateMapel"
                           href="{{ route('nilai.template.excel', ['kelas' => $kelas->id, 'mode' => 'mapel', 'mapel_id' => $activeMapel->id, 'periode' => isset($periode) ? $periode : 'sts']) }}"
                           class="px-2.5 py-1.5 bg-white hover:bg-emerald-100 text-emerald-800 border border-emerald-300 rounded-lg text-[11px] font-bold transition flex items-center shadow-2xs"
                           title="Unduh template spreadsheet .xlsx khusus mata pelajaran {{ $activeMapel->nama }}">
                            <i class="fa-solid fa-download mr-1 text-emerald-600"></i> Mapel Ini
                        </a>
                    @endif
                    <a href="{{ route('kelas.sia-pembiasaan.template', ['kelas' => $kelas->id, 'format' => 'xlsx', 'periode' => isset($periode) ? $periode : 'sts']) }}"
                       class="px-2.5 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 rounded-lg text-[11px] font-bold transition flex items-center shadow-2xs"
                       title="Unduh template spreadsheet untuk Kehadiran (SIA) & Pembiasaan">
                        <i class="fa-solid fa-clipboard-user mr-1 text-amber-600"></i> SIA & Pembiasaan
                    </a>
                </div>
            </div>

            <!-- Tabs: Upload File vs Copy Paste -->
            <div>
                <div class="flex border-b border-slate-200 text-xs font-bold mb-3">
                    <button type="button" onclick="switchImportTab('file')" id="tabBtnFile"
                        class="pb-2 px-3 border-b-2 border-emerald-600 text-emerald-700 flex items-center">
                        <i class="fa-solid fa-cloud-arrow-up mr-1.5"></i> Unggah File (Excel / PDF)
                    </button>
                    <button type="button" onclick="switchImportTab('paste')" id="tabBtnPaste"
                        class="pb-2 px-3 border-b-2 border-transparent text-slate-400 hover:text-slate-600 flex items-center">
                        <i class="fa-solid fa-paste mr-1.5"></i> Salin-Tempel (Clipboard)
                    </button>
                </div>

                <!-- Tab File -->
                <div id="tabContentFile" class="space-y-2">
                    <label class="block text-xs font-semibold text-slate-700">Pilih file spreadsheet (.xlsx, .csv, .txt) atau dokumen PDF (.pdf)</label>
                    <div class="border-2 border-dashed border-slate-300 rounded-2xl p-4 text-center hover:border-emerald-500 transition bg-slate-50/50">
                        <div class="flex justify-center items-center space-x-3 mb-2">
                            <i class="fa-solid fa-file-excel text-2xl text-emerald-600"></i>
                            <i class="fa-solid fa-file-pdf text-2xl text-rose-600"></i>
                        </div>
                        <input type="file" name="file_excel" id="file_excel_input" accept=".xlsx,.csv,.txt,.pdf"
                            class="block w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-100 file:text-emerald-800 hover:file:bg-emerald-200 cursor-pointer">
                        <p class="text-[10px] text-slate-400 mt-2">Mendukung Microsoft Excel (.xlsx), CSV, dokumen teks, dan file cetak PDF (.pdf). Juga mendukung impor kehadiran (SIA) dan pembiasaan otomatis jika tercantum di kolom tabel.</p>
                    </div>
                </div>

                <!-- Tab Paste -->
                <div id="tabContentPaste" class="space-y-2 hidden">
                    <label class="block text-xs font-semibold text-slate-700">Tempelkan tabel yang disalin dari Excel / Spreadsheet</label>
                    <textarea name="data_teks" rows="6"
                        placeholder="Bisa langsung tanpa NIS! Contoh:&#10;No	Nama Siswa	Pendidikan Agama	Pancasila	Bahasa Indonesia	Sakit	Izin	Alpa&#10;1	Ahmad Fauzi	85	80	90	0	1	0&#10;2	Siti Rahmawati	90	88	92	0	0	0"
                        class="w-full p-3 font-mono text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500"></textarea>
                    <p class="text-[10px] text-slate-400 italic">Buka Excel Anda &rarr; blok kolom nama dan nilai siswa &rarr; tekan Ctrl+C &rarr; klik di sini dan tekan Ctrl+V.</p>
                </div>
            </div>

            <!-- Tombol Aksi -->
            <div class="flex justify-end space-x-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('modalImportNilai').classList.add('hidden')"
                    class="px-4 py-2.5 rounded-xl text-xs font-semibold bg-slate-100 text-slate-600 hover:bg-slate-200 transition">
                    Batal
                </button>
                <button type="submit"
                    class="px-5 py-2.5 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-md transition flex items-center">
                    <i class="fa-solid fa-check-circle mr-1.5"></i> Mulai Impor Data
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function switchImportTab(type) {
        const tabFile = document.getElementById('tabContentFile');
        const tabPaste = document.getElementById('tabContentPaste');
        const btnFile = document.getElementById('tabBtnFile');
        const btnPaste = document.getElementById('tabBtnPaste');

        if (type === 'file') {
            tabFile.classList.remove('hidden');
            tabPaste.classList.add('hidden');
            btnFile.className = 'pb-2 px-3 border-b-2 border-emerald-600 text-emerald-700 flex items-center';
            btnPaste.className = 'pb-2 px-3 border-b-2 border-transparent text-slate-400 hover:text-slate-600 flex items-center';
        } else {
            tabFile.classList.add('hidden');
            tabPaste.classList.remove('hidden');
            btnPaste.className = 'pb-2 px-3 border-b-2 border-emerald-600 text-emerald-700 flex items-center';
            btnFile.className = 'pb-2 px-3 border-b-2 border-transparent text-slate-400 hover:text-slate-600 flex items-center';
        }
    }

    // Perbarui link download template saat pilihan periode berubah
    document.addEventListener('DOMContentLoaded', function() {
        const periodeSelect = document.getElementById('import_periode');
        const btnDownloadSemua = document.getElementById('btnDownloadTemplateSemua');
        const btnDownloadMapel = document.getElementById('btnDownloadTemplateMapel');
        if (periodeSelect) {
            periodeSelect.addEventListener('change', function() {
                if (btnDownloadSemua) {
                    const url = new URL(btnDownloadSemua.href);
                    url.searchParams.set('periode', this.value);
                    btnDownloadSemua.href = url.toString();
                }
                if (btnDownloadMapel) {
                    const url = new URL(btnDownloadMapel.href);
                    url.searchParams.set('periode', this.value);
                    btnDownloadMapel.href = url.toString();
                }
            });
        }
    });
</script>
