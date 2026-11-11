<div class="space-y-4">
    <div class="bg-emerald-50 border border-emerald-200 p-4 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-emerald-950">
        <div class="flex items-start space-x-3">
            <i class="fa-solid fa-wand-magic-sparkles text-emerald-600 text-lg mt-0.5 shrink-0"></i>
            <div>
                <p class="font-bold text-sm">Catatan Guru Otomatis — {{ $mapel->nama }}</p>
                <p class="text-emerald-800 mt-1">
                    Catatan capaian kompetensi <strong>terisi dan tersimpan otomatis 100%</strong> mengikuti <strong>Nilai Akhir &amp; Predikat (A/B/C/D)</strong> setiap siswa. Tidak perlu mengisi atau menyimpan secara manual.
                </p>
            </div>
        </div>
        <a href="{{ route('kelas.mapel.nilai', ['kelas' => $kelas->id, 'mapel' => $mapel->id, 'tab' => 'mapel', 'tahun' => $tahun]) }}"
           class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-black text-white font-bold text-xs shadow-xs transition flex items-center gap-1.5 self-start sm:self-auto shrink-0">
            <i class="fa-solid fa-arrow-left text-[10px]"></i>
            <span>Kembali ke Lembar Nilai Rapor</span>
        </a>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto max-h-[70vh]">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="sticky top-0 z-10 bg-slate-100/95 backdrop-blur border-b border-slate-200 text-xs font-bold text-slate-700 uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 px-3 w-12 text-center border-r border-slate-200">No</th>
                        <th class="py-3.5 px-4 min-w-[200px] border-r border-slate-200">Nama Peserta Didik</th>
                        <th class="py-3.5 px-3 w-28 text-center border-r border-slate-200">Nilai / Pred</th>
                        <th class="py-3.5 px-4">Catatan Capaian Kompetensi (Otomatis)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($kelasSiswaList as $idx => $ks)
                        @php
                            $d = $siswaData[$ks->id] ?? null;
                            $valRap = $d['nilaiRapor'] ?? null;
                            $pred = $d['predikat'] ?? null;
                            $catatan = trim((string) ($d['catatan'] ?? ''));
                            $predColors = [
                                'A' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                                'B' => 'bg-blue-100 text-blue-800 border-blue-300',
                                'C' => 'bg-amber-100 text-amber-800 border-amber-300',
                                'D' => 'bg-rose-100 text-rose-800 border-rose-300',
                            ];
                            $predCls = ($valRap !== null && $pred) ? ($predColors[$pred] ?? 'bg-indigo-50 text-indigo-800 border-indigo-200') : '';
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
                                    <span class="inline-block mt-1 px-2 py-0.5 rounded border font-mono font-black text-[11px] {{ $predCls }}">{{ $pred ?? '–' }}</span>
                                @else
                                    <span class="font-mono font-bold text-slate-400">–</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 space-y-1.5">
                                @if($valRap !== null && $catatan !== '')
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <i class="fa-solid fa-wand-magic-sparkles text-[9px]"></i>
                                            Otomatis (Predikat {{ $pred }})
                                        </span>
                                    </div>
                                    <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200/80 text-xs text-slate-800 leading-relaxed">
                                        {{ $catatan }}
                                    </div>
                                @else
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200">
                                            <i class="fa-regular fa-clock text-[9px]"></i>
                                            Menunggu Nilai Lengkap
                                        </span>
                                    </div>
                                    <p class="text-xs italic text-slate-400">
                                        Catatan guru akan dibuat dan disimpan otomatis setelah nilai Formatif, STS, dan SAS terisi lengkap.
                                    </p>
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

        <div class="p-4 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="text-xs text-slate-500 flex items-center gap-1.5">
                <i class="fa-solid fa-circle-check text-emerald-600"></i>
                <span>Catatan ini diperbarui otomatis setiap ada perubahan nilai dan langsung tampil di cetak rapor siswa.</span>
            </div>
            <a href="{{ route('kelas.mapel.nilai', ['kelas' => $kelas->id, 'mapel' => $mapel->id, 'tab' => 'mapel', 'tahun' => $tahun]) }}"
               class="px-4 py-2 bg-slate-900 hover:bg-black text-white font-bold rounded-xl text-xs shadow-xs transition inline-flex items-center gap-1.5 self-start sm:self-auto shrink-0">
                <i class="fa-solid fa-eye text-emerald-400"></i>
                <span>Lihat Lembar Nilai Rapor</span>
            </a>
        </div>
    </div>
</div>
