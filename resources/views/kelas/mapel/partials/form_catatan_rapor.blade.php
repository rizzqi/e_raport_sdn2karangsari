<div class="space-y-4">
    <div class="bg-indigo-50 border border-indigo-200 p-4 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-indigo-950">
        <div class="flex items-start space-x-3">
            <i class="fa-solid fa-comment-dots text-indigo-600 text-lg mt-0.5 shrink-0"></i>
            <div>
                <p class="font-bold text-sm">Form Catatan Guru — {{ $mapel->nama }}</p>
                <p class="text-indigo-800 mt-1">
                    Kelola catatan capaian kompetensi peserta didik. Klik <strong>Gunakan Saran</strong> untuk menerapkan kalimat otomatis sesuai nilai akhir rapor dan fase kelas.
                </p>
            </div>
        </div>
        <a href="{{ route('kelas.mapel.nilai', ['kelas' => $kelas->id, 'mapel' => $mapel->id, 'tab' => 'mapel', 'tahun' => $tahun]) }}"
           class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-black text-white font-bold text-xs shadow-xs transition flex items-center gap-1.5 self-start sm:self-auto shrink-0">
            <i class="fa-solid fa-arrow-left text-[10px]"></i>
            <span>Kembali ke Lembar Nilai Rapor</span>
        </a>
    </div>

    <form id="formCatatanRapor" action="{{ route('kelas.mapel.nilai.rapor', ['kelas' => $kelas->id, 'mapel' => $mapel->id]) }}" method="POST">
        @csrf
        <input type="hidden" name="tahun" value="{{ $tahun }}">
        <input type="hidden" name="semester" value="{{ $semester }}">

        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="overflow-x-auto max-h-[70vh]">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="sticky top-0 z-10 bg-slate-100/95 backdrop-blur border-b border-slate-200 text-xs font-bold text-slate-700 uppercase tracking-wider">
                        <tr>
                            <th class="py-3.5 px-3 w-12 text-center border-r border-slate-200">No</th>
                            <th class="py-3.5 px-4 min-w-[200px] border-r border-slate-200">Nama Peserta Didik</th>
                            <th class="py-3.5 px-3 w-28 text-center border-r border-slate-200">Nilai / Pred</th>
                            <th class="py-3.5 px-4">Catatan Capaian Kompetensi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        @forelse($kelasSiswaList as $idx => $ks)
                            @php
                                $d = $siswaData[$ks->id] ?? null;
                                $valRap = $d['nilaiRapor'] ?? null;
                                $pred = $d['predikat'] ?? null;
                                $catatan = $d['catatan'] ?? '';
                                $saranDef = $d['saranDefault'] ?? '';
                                $isLocked = $d['isLocked'] ?? false;
                            @endphp
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-3 px-3 text-center font-bold text-slate-400 border-r border-slate-200 align-top">
                                    {{ $idx + 1 }}
                                </td>
                                <td class="py-3 px-4 border-r border-slate-200 align-top">
                                    <div class="font-bold text-slate-900 text-sm">{{ $ks->siswa->nama }}</div>
                                    <div class="text-[11px] text-slate-400 font-mono mt-0.5">NIS: {{ $ks->siswa->nis ?? '-' }}</div>
                                </td>
                                <td class="py-3 px-3 text-center border-r border-slate-200 align-top">
                                    @if($valRap !== null)
                                        <div class="font-mono font-black text-sm text-slate-900">{{ \App\Services\HitungNilaiService::formatAngka($valRap) }}</div>
                                        <span class="inline-block mt-1 px-2 py-0.5 rounded bg-indigo-50 text-indigo-800 border border-indigo-200 font-mono font-black text-[11px]">{{ $pred ?? '–' }}</span>
                                    @else
                                        <span class="font-mono font-bold text-slate-400">–</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 space-y-1.5">
                                    <textarea name="catatan[{{ $ks->id }}]"
                                              id="textarea_catatan_{{ $ks->id }}"
                                              rows="2"
                                              oninput="setDirty(true)"
                                              {{ $isLocked ? 'disabled' : '' }}
                                              placeholder="Tuliskan catatan deskripsi capaian kompetensi..."
                                              class="w-full text-xs px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none leading-relaxed">{{ old('catatan.' . $ks->id, $catatan) }}</textarea>
                                    @if(! $isLocked && $saranDef)
                                        <div class="flex items-center justify-between gap-2 text-[11px] text-slate-500">
                                            <span class="truncate italic">{{ $saranDef }}</span>
                                            <button type="button"
                                                    onclick="document.getElementById('textarea_catatan_{{ $ks->id }}').value = {{ json_encode($saranDef) }}; setDirty(true);"
                                                    class="shrink-0 px-2.5 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold border border-indigo-200 transition cursor-pointer">
                                                <i class="fa-solid fa-wand-sparkles text-amber-500 mr-1"></i> Gunakan Saran
                                            </button>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-12 text-center text-slate-400">
                                    Belum ada data siswa terdaftar pada kelas ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between gap-3">
                <div class="text-xs text-slate-500">
                    Catatan tersimpan tampil di Lembar Nilai Rapor dan dokumen cetak rapor siswa.
                </div>
                @if(! $isAnyLocked)
                    <button type="submit"
                            class="px-5 py-2.5 bg-slate-900 hover:bg-black text-white font-bold rounded-xl text-xs shadow-sm transition flex items-center gap-1.5 cursor-pointer">
                        <i class="fa-solid fa-floppy-disk text-amber-400"></i>
                        <span>Simpan Catatan Guru</span>
                    </button>
                @endif
            </div>
        </div>
    </form>
</div>

