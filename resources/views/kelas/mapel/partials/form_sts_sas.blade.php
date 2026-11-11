@php
    $isSts = $mode === 'sts';
    $judulForm = $isSts ? 'Form Input Nilai STS (Sumatif Tengah Semester)' : 'Form Input Nilai SAS (Sumatif Akhir Semester)';
    $bobotAktif = $isSts ? $bobotUts : $bobotUas;
    $routeAction = $isSts
        ? route('kelas.mapel.nilai.uts', ['kelas' => $kelas->id, 'mapel' => $mapel->id])
        : route('kelas.mapel.nilai.uas', ['kelas' => $kelas->id, 'mapel' => $mapel->id]);
    $routeCetak = $isSts
        ? route('kelas.mapel.nilai.cetak-uts-pdf', ['kelas' => $kelas->id, 'mapel' => $mapel->id, 'tahun' => $tahun, 'semester' => $semester])
        : route('kelas.mapel.nilai.cetak-uas-pdf', ['kelas' => $kelas->id, 'mapel' => $mapel->id, 'tahun' => $tahun, 'semester' => $semester]);
    $fieldName = $isSts ? 'sts' : 'sas';
    $rowPrefix = $isSts ? 'row_sts_' : 'row_sas_';
    $inputClass = $isSts ? 'input-nilai-sts' : 'input-nilai-sas';
    $accentBanner = $isSts ? 'bg-amber-50 border-amber-200 text-amber-950' : 'bg-blue-50 border-blue-200 text-blue-950';
    $accentRing = $isSts ? 'focus:ring-amber-400' : 'focus:ring-blue-400';
    $accentHeader = $isSts ? 'bg-amber-50/80 text-amber-950' : 'bg-blue-50/80 text-blue-950';
@endphp

<div class="space-y-4">
    <!-- Banner Form Input Khusus STS / SAS -->
    <div class="{{ $accentBanner }} border p-4 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
        <div class="flex items-start space-x-3">
            <i class="fa-solid {{ $isSts ? 'fa-file-pen text-amber-600' : 'fa-graduation-cap text-blue-600' }} text-lg mt-0.5 shrink-0"></i>
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <p class="font-bold text-sm">{{ $judulForm }} — {{ $mapel->nama }}</p>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-white border border-slate-200 text-slate-700 font-mono">
                        Bobot {{ $bobotAktif }}%
                    </span>
                </div>
                <p class="mt-1 opacity-90">
                    Fokus hanya mengisi nilai <strong>{{ $isSts ? 'STS' : 'SAS' }} (0–100)</strong>. Data akan <strong>tersimpan otomatis</strong> dan mengalir satu arah ke <strong>Lembar Nilai Rapor (Read-Only)</strong>.
                    Tekan <kbd class="px-1.5 py-0.5 rounded bg-white border border-slate-300 font-mono text-[10px]">Enter</kbd> / <kbd class="px-1.5 py-0.5 rounded bg-white border border-slate-300 font-mono text-[10px]">↓</kbd> untuk pindah baris cepat.
                </p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2 self-start sm:self-auto shrink-0">
            <a href="{{ $routeCetak }}" target="_blank"
               class="px-3 py-2 rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 font-bold text-xs shadow-2xs transition flex items-center gap-1.5">
                <i class="fa-solid fa-print {{ $isSts ? 'text-amber-600' : 'text-blue-600' }}"></i>
                <span>Cetak {{ $isSts ? 'STS' : 'SAS' }}</span>
            </a>
            <a href="{{ route('kelas.mapel.nilai', ['kelas' => $kelas->id, 'mapel' => $mapel->id, 'tab' => 'mapel', 'tahun' => $tahun]) }}"
               class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-black text-white font-bold text-xs shadow-xs transition flex items-center gap-1.5">
                <i class="fa-solid fa-arrow-left text-[10px]"></i>
                <span>Kembali ke Lembar Nilai Rapor</span>
            </a>
        </div>
    </div>

    <form id="formSingleComponent"
          action="{{ $routeAction }}"
          method="POST"
          data-autosave-url="{{ route('kelas.mapel.nilai.autosave-komponen', ['kelas' => $kelas->id, 'mapel' => $mapel->id]) }}"
          data-autosave-mode="{{ $mode }}"
          data-tahun="{{ $tahun }}"
          data-semester="{{ $semester }}">
        @csrf
        <input type="hidden" name="tahun" value="{{ $tahun }}">
        <input type="hidden" name="semester" value="{{ $semester }}">

        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="overflow-x-auto max-h-[70vh]">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="sticky top-0 z-10 bg-slate-100/95 backdrop-blur border-b border-slate-200 text-xs font-bold text-slate-700 uppercase tracking-wider">
                        <tr>
                            <th class="py-3.5 px-3 w-12 text-center border-r border-slate-200">No</th>
                            <th class="py-3.5 px-4 min-w-[240px] border-r border-slate-200">Nama Peserta Didik</th>
                            <th class="py-3.5 px-3 w-36 text-center border-r border-slate-200 bg-slate-50">
                                <div>Rata Formatif</div>
                                <div class="text-[9px] text-slate-400 font-medium lowercase">referensi</div>
                            </th>
                            <th class="py-3.5 px-4 w-44 text-center border-r border-slate-200 {{ $accentHeader }} font-black">
                                <div>Nilai {{ $isSts ? 'STS' : 'SAS' }} ({{ $bobotAktif }}%)</div>
                                <div class="text-[9px] opacity-75 font-medium lowercase">input 0 - 100</div>
                            </th>
                            <th class="py-3.5 px-4 w-40 text-center">
                                <div>Ketuntasan</div>
                                <div class="text-[9px] text-slate-400 font-medium lowercase">kkm {{ $kkm }}</div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        @forelse($kelasSiswaList as $idx => $ks)
                            @php
                                $d = $siswaData[$ks->id] ?? null;
                                $valFmt = $d['rataFormatif'] ?? null;
                                $valScore = $isSts ? ($d['nilaiUts'] ?? null) : ($d['nilaiUas'] ?? null);
                                $isFocused = $focusKsId !== null && $focusKsId === $ks->id;
                                $isLocked = $d['isLocked'] ?? false;
                            @endphp
                            <tr id="{{ $rowPrefix . $ks->id }}"
                                class="transition {{ $isFocused ? 'bg-amber-50/90 ring-2 ring-inset ring-amber-400' : 'hover:bg-slate-50/80' }}">
                                <td class="py-3 px-3 text-center font-bold text-slate-400 border-r border-slate-200">
                                    {{ $idx + 1 }}
                                </td>
                                <td class="py-3 px-4 border-r border-slate-200">
                                    <div class="font-bold text-slate-900 text-sm">{{ $ks->siswa->nama }}</div>
                                    <div class="text-[11px] text-slate-400 font-mono mt-0.5">
                                        NIS: {{ $ks->siswa->nis ?? '-' }} &bull; NISN: {{ $ks->siswa->nisn ?? '-' }}
                                    </div>
                                </td>
                                <td class="py-3 px-3 text-center border-r border-slate-200 bg-slate-50/60 font-mono font-bold text-slate-700">
                                    {{ $valFmt !== null ? \App\Services\HitungNilaiService::formatAngka($valFmt) : '–' }}
                                </td>
                                <td class="py-3 px-4 text-center border-r border-slate-200">
                                    <input type="number"
                                           step="0.1"
                                           min="0"
                                           max="100"
                                           name="{{ $fieldName }}[{{ $ks->id }}]"
                                           id="input_single_{{ $ks->id }}"
                                           data-ks-id="{{ $ks->id }}"
                                           data-row="{{ $idx }}"
                                           data-kkm="{{ $kkm }}"
                                           data-status-target="status_single_{{ $ks->id }}"
                                           value="{{ old($fieldName . '.' . $ks->id, $valScore !== null ? \App\Services\HitungNilaiService::formatAngka($valScore) : '') }}"
                                           placeholder="–"
                                           {{ $isFocused ? 'autofocus' : '' }}
                                           {{ $isLocked ? 'disabled' : '' }}
                                           oninput="updateSingleComponentStatus(this)"
                                           class="{{ $inputClass }} w-28 text-center font-mono font-bold text-sm px-3 py-2 border rounded-xl focus:ring-2 {{ $accentRing }} focus:outline-none transition {{ $valScore !== null && $valScore < $kkm ? 'bg-rose-50 text-rose-700 border-rose-400' : 'border-slate-300 bg-white' }}">
                                </td>
                                <td class="py-3 px-4 text-center" id="status_single_{{ $ks->id }}">
                                    @if($valScore === null)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-slate-100 text-slate-500 border border-slate-200">
                                            Belum Diisi
                                        </span>
                                    @elseif($valScore >= $kkm)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <i class="fa-solid fa-check text-[10px]"></i> Tuntas
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                            <i class="fa-solid fa-triangle-exclamation text-[10px]"></i> Belum Tuntas
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-12 text-center text-slate-400">
                                    Belum ada data siswa terdaftar pada kelas ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-4 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="text-xs text-slate-500 flex items-center gap-1.5">
                    <i class="fa-solid fa-arrow-right-arrow-left text-emerald-600"></i>
                    <span>Nilai {{ $isSts ? 'STS' : 'SAS' }} otomatis digabungkan dengan Formatif di <strong>Lembar Nilai Rapor</strong>.</span>
                </div>
                @if(! $isAnyLocked)
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="submit" name="submit_mode" value="stay"
                                class="px-4 py-2.5 rounded-xl bg-white hover:bg-slate-100 text-slate-800 border border-slate-300 font-bold text-xs shadow-2xs transition flex items-center gap-1.5 cursor-pointer">
                            <i class="fa-solid fa-floppy-disk text-slate-600"></i>
                            <span>Simpan Nilai {{ $isSts ? 'STS' : 'SAS' }}</span>
                        </button>
                        <button type="submit" name="submit_mode" value="kembali"
                                class="px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-black text-white font-bold text-xs shadow-sm transition flex items-center gap-1.5 cursor-pointer">
                            <i class="fa-solid fa-check text-emerald-400"></i>
                            <span>Simpan &amp; Lihat Lembar Nilai Rapor</span>
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </form>
</div>

