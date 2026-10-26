@php
    $itemsMapel = isset($mapelList) ? $mapelList : (isset($mapels) ? $mapels : \App\Models\MataPelajaran::aktif()->get());
@endphp

@if(auth()->check() && (auth()->user()->isAdmin() || (auth()->user()->isWali() && auth()->user()->id === $kelas->wali_id)))
    <div id="modalEditKkmKelas" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-2xl max-w-2xl w-full shadow-2xl p-6 border border-slate-100 max-h-[90vh] overflow-y-auto">
            <!-- Modal Header -->
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center font-bold text-base shadow-xs">
                        <i class="fa-solid fa-sliders"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
                            Atur KKM / KKTP Mandiri &bull; {{ $kelas->nama }}
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">Tentukan nilai KKM khusus untuk kelas ini. Hanya berlaku untuk {{ $kelas->nama }}.</p>
                    </div>
                </div>
                <button type="button" onclick="document.getElementById('modalEditKkmKelas').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-2xl leading-none">&times;</button>
            </div>

            <form action="{{ route('kelas.kkm.update', $kelas->id) }}" method="POST" class="space-y-4 mt-4">
                @csrf
                @method('PUT')

                <!-- Kotak Penjelasan Isolasi KKM -->
                <div class="p-3.5 bg-purple-50/70 rounded-xl border border-purple-200/80 text-xs text-purple-900 flex items-start gap-2.5">
                    <i class="fa-solid fa-shield-halved text-purple-600 text-sm mt-0.5 shrink-0"></i>
                    <div>
                        <strong class="font-bold">KKM Terisolasi Khusus {{ $kelas->nama }}:</strong>
                        <p class="mt-0.5 text-purple-800 leading-relaxed">
                            Nilai KKM yang disimpan di sini <strong>hanya berlaku untuk {{ $kelas->nama }}</strong> dan <strong>tidak akan mempengaruhi kelas lainnya</strong>. Setiap Wali Kelas bebas menentukan batas KKM/KKTP masing-masing.
                        </p>
                    </div>
                </div>

                <!-- Quick Presets -->
                <div class="flex flex-wrap items-center justify-between gap-2 p-3 bg-slate-50 rounded-xl border border-slate-200">
                    <span class="text-xs font-bold text-slate-700 flex items-center">
                        <i class="fa-solid fa-wand-magic-sparkles mr-1.5 text-purple-600"></i> Set Cepat Semua Mapel:
                    </span>
                    <div class="flex flex-wrap items-center gap-1.5">
                        <button type="button" onclick="setSemuaKkm(70)" class="px-2.5 py-1 rounded-lg text-xs font-bold bg-white hover:bg-purple-100 text-purple-800 border border-purple-200 transition shadow-2xs">
                            Semua 70
                        </button>
                        <button type="button" onclick="setSemuaKkm(75)" class="px-2.5 py-1 rounded-lg text-xs font-bold bg-white hover:bg-purple-100 text-purple-800 border border-purple-200 transition shadow-2xs">
                            Semua 75
                        </button>
                        <button type="button" onclick="resetSemuaKkmDefault()" class="px-2.5 py-1 rounded-lg text-xs font-bold bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 transition shadow-2xs">
                            Reset ke Standar Master
                        </button>
                    </div>
                </div>

                <!-- Tabel Input KKM -->
                <div class="overflow-x-auto border border-slate-200/80 rounded-xl">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase tracking-wider">
                            <tr>
                                <th class="py-2.5 px-3 w-10 text-center">No</th>
                                <th class="py-2.5 px-3">Mata Pelajaran</th>
                                <th class="py-2.5 px-3 w-28 text-center">Standar Master</th>
                                <th class="py-2.5 px-3 w-36 text-center">Status</th>
                                <th class="py-2.5 px-3 w-36 text-center">KKM Kelas Ini</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($itemsMapel as $index => $m)
                                @php
                                    $kkmKelas = $kelas->getKkmForMapel($m);
                                    $defaultKkm = (int) ($m->kkm ?? 70);
                                    $customRecord = $kelas->relationLoaded('mapelKkm')
                                        ? $kelas->mapelKkm->firstWhere('mapel_id', $m->id)
                                        : $kelas->mapelKkm()->where('mapel_id', $m->id)->first();
                                    $isCustom = $customRecord && $customRecord->kkm !== null;
                                @endphp
                                <tr class="hover:bg-slate-50/60 transition">
                                    <td class="py-2.5 px-3 text-center text-slate-400 font-medium">{{ $index + 1 }}</td>
                                    <td class="py-2.5 px-3 font-bold text-slate-800">
                                        {{ $m->nama }}
                                        <span class="block text-[10px] text-slate-400 font-normal">Kode: {{ $m->kode ?? '-' }}</span>
                                    </td>
                                    <td class="py-2.5 px-3 text-center text-slate-500 font-mono font-medium">
                                        <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-600">{{ $defaultKkm }}</span>
                                    </td>
                                    <td class="py-2.5 px-3 text-center">
                                        @if($isCustom)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-purple-100 text-purple-800 border border-purple-200">
                                                <i class="fa-solid fa-user-check text-[9px]"></i> Khusus Rombel
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-500 border border-slate-200">
                                                Ikut Master
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 px-3 text-center">
                                        <input type="number" name="kkm[{{ $m->id }}]" min="0" max="100" step="1" required
                                            value="{{ $kkmKelas }}"
                                            data-default="{{ $defaultKkm }}"
                                            class="input-kkm-kelas w-24 text-center px-2 py-1.5 border border-slate-300 rounded-lg font-black text-sm text-purple-700 focus:ring-2 focus:ring-purple-500 bg-white">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="pt-3 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <span class="text-[11px] text-slate-400 italic">
                        KKM ini akan otomatis dijadikan acuan kriteria ketuntasan rapor, predikat, dan cetak PDF/Word.
                    </span>
                    <div class="flex items-center space-x-2 shrink-0">
                        <button type="button" onclick="document.getElementById('modalEditKkmKelas').classList.add('hidden')"
                            class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                            Batal
                        </button>
                        <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-purple-600 text-white hover:bg-purple-700 shadow-sm flex items-center transition">
                            <i class="fa-solid fa-check mr-1.5"></i> Simpan KKM {{ $kelas->nama }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @pushOnce('scripts')
    <script>
        function setSemuaKkm(val) {
            document.querySelectorAll('.input-kkm-kelas').forEach(el => {
                el.value = val;
            });
        }

        function resetSemuaKkmDefault() {
            document.querySelectorAll('.input-kkm-kelas').forEach(el => {
                el.value = el.getAttribute('data-default') || 70;
            });
        }
    </script>
    @endPushOnce
@endif
