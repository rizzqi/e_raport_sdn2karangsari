@if($tab === 'ekstrakurikuler')
    @php
        $ekskulRouteParams = fn (string $targetPeriode) => array_filter([
            'kelas' => $kelas->id,
            'mapel' => $mapel->id ?? null,
            'tab' => 'ekstrakurikuler',
            'periode' => $targetPeriode,
            'tahun' => $tahun,
        ], fn ($value) => $value !== null);
    @endphp

    <div class="space-y-4">
        {{-- Header & Switcher Periode --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="space-y-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <i class="fa-solid fa-campground text-teal-600"></i>
                        <span>Nilai Ekstrakurikuler Siswa ({{ strtoupper($periodeEkskul) }})</span>
                    </h2>
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-teal-50 text-teal-700 border border-teal-200">
                        {{ $countEkskulSiswa }}/{{ $kelasSiswaList->count() }} Siswa Memiliki Ekskul
                    </span>
                    <span id="ekskulAutosaveBadge" class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                        Siap simpan otomatis
                    </span>
                </div>
                <p class="text-xs text-slate-500">
                    Pilih predikat hanya pada kegiatan ekstrakurikuler yang diikuti siswa. Biarkan <strong>- Tidak Ikut -</strong> jika siswa tidak mengikuti kegiatan tersebut agar tidak muncul di raport siswa.
                </p>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Periode Raport:</span>
                <div class="inline-flex rounded-xl bg-slate-100 p-1 border border-slate-200">
                    <a href="{{ route('kelas.mapel.nilai', $ekskulRouteParams('sts')) }}"
                       class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $periodeEkskul === 'sts' ? 'bg-teal-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                        STS (Tengah)
                    </a>
                    <a href="{{ route('kelas.mapel.nilai', $ekskulRouteParams('sas')) }}"
                       class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $periodeEkskul === 'sas' ? 'bg-teal-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                        SAS (Akhir)
                    </a>
                </div>
            </div>
        </div>

        @if($ekskulList->isEmpty())
            <div class="bg-white rounded-2xl border border-dashed border-slate-300 p-10 text-center space-y-2">
                <div class="w-12 h-12 mx-auto rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-campground"></i>
                </div>
                <h3 class="text-sm font-bold text-slate-800">Belum Ada Kegiatan Ekstrakurikuler Aktif</h3>
                <p class="text-xs text-slate-500 max-w-md mx-auto">
                    Minta Admin Sekolah untuk menambahkan atau mengaktifkan kegiatan ekstrakurikuler pada menu <strong>Pengaturan &rarr; Mapel, Pembiasaan &amp; Ekskul</strong>.
                </p>
            </div>
        @else
            <form action="{{ route('kelas.ekstrakurikuler.simpan', $kelas->id) }}" method="POST" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                @csrf
                <input type="hidden" name="periode" value="{{ $periodeEkskul }}">
                <input type="hidden" name="tahun" value="{{ $tahun }}">

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold uppercase tracking-wider text-slate-600">
                                <th class="px-4 py-3 w-12 text-center sticky left-0 bg-slate-50 z-10 border-r border-slate-200">No</th>
                                <th class="px-4 py-3 min-w-[210px] sticky left-12 bg-slate-50 z-10 border-r border-slate-200">Nama Siswa</th>
                                @foreach($ekskulList as $eks)
                                    <th class="px-4 py-3 min-w-[230px] border-r border-slate-200 last:border-r-0 align-top">
                                        <div class="flex items-start justify-between gap-2">
                                            <span class="text-slate-800 normal-case font-bold text-xs leading-snug">{{ $eks->nama }}</span>
                                            <button type="button"
                                                    onclick="fillAllEkskulBaik({{ $eks->id }})"
                                                    class="px-2 py-0.5 rounded-md bg-teal-50 hover:bg-teal-100 text-teal-700 border border-teal-200 text-[10px] font-bold whitespace-nowrap transition"
                                                    title="Isi predikat Baik untuk siswa yang masih kosong pada kegiatan ini">
                                                Isi Kosong: Baik
                                            </button>
                                        </div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs">
                            @forelse($kelasSiswaList as $idx => $ks)
                                <tr class="hover:bg-slate-50/70 transition">
                                    <td class="px-4 py-3 text-center font-semibold text-slate-500 sticky left-0 bg-white z-10 border-r border-slate-100">
                                        {{ $idx + 1 }}
                                    </td>
                                    <td class="px-4 py-3 sticky left-12 bg-white z-10 border-r border-slate-100">
                                        <p class="font-bold text-slate-900">{{ $ks->siswa->nama }}</p>
                                        <p class="text-[11px] text-slate-400">NISN: {{ $ks->siswa->nisn ?: '-' }}</p>
                                    </td>

                                    @foreach($ekskulList as $eks)
                                        @php
                                            $existing = $nilaiEkskulMap->get($ks->id.'_'.$eks->id);
                                            $selectedPredikat = $existing?->predikat ?? '';
                                            $customKeterangan = $existing?->keterangan ?? '';
                                        @endphp
                                        <td class="px-3 py-2.5 border-r border-slate-100 last:border-r-0 align-top">
                                            <div class="space-y-1.5"
                                                 data-ekskul-cell
                                                 data-ks-id="{{ $ks->id }}"
                                                 data-ekskul-id="{{ $eks->id }}"
                                                 data-periode="{{ $periodeEkskul }}">
                                                <select name="ekskul[{{ $ks->id }}][{{ $eks->id }}][predikat]"
                                                        onchange="onEkskulPredikatChange(this)"
                                                        class="ekskul-predikat-select w-full px-2.5 py-1.5 rounded-lg border text-xs font-semibold transition focus:ring-2 focus:ring-teal-500 {{ $selectedPredikat !== '' ? 'border-teal-300 bg-teal-50/40 text-teal-900' : 'border-slate-200 bg-white text-slate-500' }}">
                                                    <option value="">- Tidak Ikut -</option>
                                                    @foreach(\App\Models\NilaiEkstrakurikuler::PREDIKAT_LIST as $pred)
                                                        <option value="{{ $pred }}" @selected($selectedPredikat === $pred)>{{ $pred }}</option>
                                                    @endforeach
                                                </select>

                                                <input type="text"
                                                       name="ekskul[{{ $ks->id }}][{{ $eks->id }}][keterangan]"
                                                       value="{{ $customKeterangan }}"
                                                       maxlength="255"
                                                       placeholder="{{ $selectedPredikat !== '' ? 'Catatan khusus (opsional)' : 'Pilih predikat dulu' }}"
                                                       @disabled($selectedPredikat === '')
                                                       oninput="onEkskulKeteranganInput(this)"
                                                       class="ekskul-keterangan-input w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-[11px] text-slate-700 placeholder:text-slate-400 focus:ring-2 focus:ring-teal-500 disabled:bg-slate-100 disabled:text-slate-400 transition">
                                            </div>
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ 2 + $ekskulList->count() }}" class="px-6 py-8 text-center text-slate-400">
                                        Belum ada siswa yang terdaftar pada rombel kelas ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($kelasSiswaList->isNotEmpty())
                    <div class="p-4 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <p class="text-[11px] text-slate-500">
                            <i class="fa-solid fa-wand-magic-sparkles text-teal-600 mr-1"></i>
                            Perubahan otomatis tersimpan saat predikat dipilih atau catatan diketik. Keterangan yang dikosongkan akan dibuatkan narasi otomatis di raport.
                        </p>
                        <button type="submit"
                                class="px-5 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold shadow-sm transition inline-flex items-center justify-center gap-2 shrink-0">
                            <i class="fa-solid fa-floppy-disk"></i>
                            <span>Simpan Semua Ekstrakurikuler ({{ strtoupper($periodeEkskul) }})</span>
                        </button>
                    </div>
                @endif
            </form>
        @endif
    </div>

    <script>
        (function () {
            const autosaveUrl = @json(route('kelas.mapel.nilai.autosave-ekstrakurikuler', $kelas->id));
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || @json(csrf_token());
            const statusBadge = document.getElementById('ekskulAutosaveBadge');
            const timers = new WeakMap();

            function setStatus(text, tone) {
                if (!statusBadge) {
                    return;
                }
                const styles = {
                    idle: 'px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600 border border-slate-200',
                    saving: 'px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-800 border border-amber-200',
                    saved: 'px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200',
                    error: 'px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-rose-50 text-rose-700 border border-rose-200',
                };
                statusBadge.className = styles[tone] || styles.idle;
                statusBadge.textContent = text;
            }

            async function saveEkskulCell(cellEl) {
                const selectEl = cellEl.querySelector('.ekskul-predikat-select');
                const noteEl = cellEl.querySelector('.ekskul-keterangan-input');
                if (!selectEl || !noteEl) {
                    return;
                }

                setStatus('Menyimpan otomatis...', 'saving');
                selectEl.classList.remove('ring-2', 'ring-rose-500');

                try {
                    const response = await fetch(autosaveUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({
                            kelas_siswa_id: parseInt(cellEl.dataset.ksId, 10),
                            ekstrakurikuler_id: parseInt(cellEl.dataset.ekskulId, 10),
                            periode: cellEl.dataset.periode,
                            predikat: selectEl.value || null,
                            keterangan: noteEl.value || null,
                        }),
                    });

                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }

                    const data = await response.json();
                    if (data.status === 'deleted') {
                        setStatus('Kepesertaan dikosongkan (' + (data.saved_at || 'ok') + ')', 'saved');
                    } else {
                        if (data.generated_keterangan && !noteEl.value) {
                            noteEl.title = 'Otomatis: ' + data.generated_keterangan;
                        }
                        setStatus('Tersimpan otomatis (' + (data.saved_at || 'ok') + ')', 'saved');
                    }
                } catch (err) {
                    selectEl.classList.add('ring-2', 'ring-rose-500');
                    setStatus('Gagal simpan otomatis, coba lagi', 'error');
                }
            }

            function syncCellVisualState(cellEl) {
                const selectEl = cellEl.querySelector('.ekskul-predikat-select');
                const noteEl = cellEl.querySelector('.ekskul-keterangan-input');
                if (!selectEl || !noteEl) {
                    return;
                }

                const hasValue = selectEl.value !== '';
                if (hasValue) {
                    selectEl.classList.remove('border-slate-200', 'bg-white', 'text-slate-500');
                    selectEl.classList.add('border-teal-300', 'bg-teal-50/40', 'text-teal-900');
                    noteEl.disabled = false;
                    noteEl.placeholder = 'Catatan khusus (opsional)';
                } else {
                    selectEl.classList.remove('border-teal-300', 'bg-teal-50/40', 'text-teal-900');
                    selectEl.classList.add('border-slate-200', 'bg-white', 'text-slate-500');
                    noteEl.value = '';
                    noteEl.disabled = true;
                    noteEl.placeholder = 'Pilih predikat dulu';
                }
            }

            window.onEkskulPredikatChange = function (selectEl) {
                const cellEl = selectEl.closest('[data-ekskul-cell]');
                if (!cellEl) {
                    return;
                }
                syncCellVisualState(cellEl);
                saveEkskulCell(cellEl);
            };

            window.onEkskulKeteranganInput = function (inputEl) {
                const cellEl = inputEl.closest('[data-ekskul-cell]');
                if (!cellEl) {
                    return;
                }
                const prevTimer = timers.get(cellEl);
                if (prevTimer) {
                    clearTimeout(prevTimer);
                }
                setStatus('Mengetik catatan...', 'saving');
                timers.set(cellEl, setTimeout(() => saveEkskulCell(cellEl), 550));
            };

            window.fillAllEkskulBaik = async function (ekskulId) {
                const cells = Array.from(document.querySelectorAll('[data-ekskul-cell][data-ekskul-id="' + ekskulId + '"]'));
                const targetCells = cells.filter(cell => {
                    const sel = cell.querySelector('.ekskul-predikat-select');
                    return sel && sel.value === '';
                });

                if (targetCells.length === 0) {
                    setStatus('Semua siswa sudah terisi untuk kegiatan ini', 'saved');
                    return;
                }

                for (const cell of targetCells) {
                    const sel = cell.querySelector('.ekskul-predikat-select');
                    sel.value = 'Baik';
                    syncCellVisualState(cell);
                }

                for (const cell of targetCells) {
                    await saveEkskulCell(cell);
                }
            };
        })();
    </script>
@endif

