@extends('layouts.app')

@section('title', 'Riwayat Cetak Raport')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900 flex items-center">
                <i class="fa-solid fa-clock-rotate-left text-emerald-600 mr-2"></i> Riwayat Pencetakan & Unduhan Raport
            </h1>
            <p class="text-xs text-slate-500">Log pencatatan siapa yang mencetak raport siswa dan kapan dicetak</p>
        </div>
        @if($riwayatList->total() > 0)
            <div>
                <button type="button" onclick="document.getElementById('modalKosongkanRiwayat').classList.remove('hidden')"
                    class="inline-flex items-center px-3.5 py-2 text-xs font-bold rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 transition shadow-2xs">
                    <i class="fa-solid fa-trash-can mr-2 text-rose-600"></i> Kosongkan Riwayat
                </button>
            </div>
        @endif
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <!-- Tampilan Kartu Mobile Khusus HP -->
        <div class="block md:hidden divide-y divide-slate-100">
            @forelse($riwayatList as $index => $r)
                <div class="p-3.5 space-y-2 bg-white">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <h4 class="font-bold text-xs text-slate-900 truncate">{{ $r->kelasSiswa->siswa->nama ?? 'Siswa dihapus' }}</h4>
                            <p class="text-[10px] text-slate-500 mt-0.5">
                                {{ $r->kelasSiswa->kelas->nama ?? '-' }} &bull; {{ $r->created_at->format('d/m/Y H:i') }}
                            </p>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase shrink-0 {{ strtolower($r->periode) === 'sts' ? 'bg-emerald-100 text-emerald-800' : 'bg-blue-100 text-blue-800' }}">
                            {{ strtoupper($r->periode) }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between text-xs pt-1 border-t border-slate-50">
                        <span class="text-[10px] text-slate-400">
                            Oleh: <strong class="text-slate-600">{{ $r->pembuat->name ?? 'Sistem' }}</strong>
                        </span>
                        <div class="flex items-center gap-1.5">
                            @if($r->kelas_siswa_id)
                                <a href="{{ route('raport.preview', ['kelasSiswa' => $r->kelas_siswa_id, 'periode' => $r->periode]) }}"
                                   class="inline-flex items-center px-2.5 py-1 text-xs font-bold rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 transition">
                                    <i class="fa-solid fa-file-lines mr-1 text-emerald-600"></i> Buka Raport
                                </a>
                            @endif
                            <form action="{{ route('raport.riwayat.hapus', $r->id) }}" method="POST" class="inline" onsubmit="return confirm('Hapus catatan riwayat cetak ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="inline-flex items-center px-2 py-1 text-xs font-bold rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 transition"
                                        title="Hapus Catatan Riwayat">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-slate-400 text-xs">
                    Belum ada riwayat pencetakan raport.
                </div>
            @endforelse
        </div>

        <!-- Tabel untuk Layar Desktop & Tablet -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs font-bold uppercase text-slate-600">
                    <tr>
                        <th class="py-3 px-4 w-12 text-center">No</th>
                        <th class="py-3 px-4">Nama Siswa</th>
                        <th class="py-3 px-4">Kelas</th>
                        <th class="py-3 px-4 text-center">Periode</th>
                        <th class="py-3 px-4">Tanggal Unduh</th>
                        <th class="py-3 px-4">Dicetak Oleh</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($riwayatList as $index => $r)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3.5 px-4 text-center text-slate-500 font-medium">
                                {{ $riwayatList->firstItem() + $index }}
                            </td>
                            <td class="py-3.5 px-4 font-bold text-slate-900">
                                {{ $r->kelasSiswa->siswa->nama ?? 'Siswa dihapus' }}
                            </td>
                            <td class="py-3.5 px-4 text-xs font-semibold text-slate-700">
                                {{ $r->kelasSiswa->kelas->nama ?? '-' }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                @if(strtolower($r->periode) === 'sts')
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        STS
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-blue-100 text-blue-800 border border-blue-200">
                                        SAS
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-xs text-slate-600 font-mono">
                                {{ $r->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="py-3.5 px-4 text-xs">
                                <span class="font-bold text-slate-800">{{ $r->pembuat->name ?? 'Sistem' }}</span>
                                <span class="text-slate-400 block text-[10px]">{{ $r->pembuat->email ?? '-' }}</span>
                            </td>
                            <td class="py-3.5 px-4 text-right space-x-1.5 whitespace-nowrap">
                                @if($r->kelas_siswa_id)
                                    <a href="{{ route('raport.preview', ['kelasSiswa' => $r->kelas_siswa_id, 'periode' => $r->periode]) }}"
                                       class="inline-flex items-center px-3 py-1.5 text-xs font-bold rounded-xl bg-white hover:bg-emerald-50 text-emerald-700 border border-emerald-200 transition shadow-2xs">
                                       <i class="fa-solid fa-file-lines mr-1.5 text-emerald-600"></i> Buka Raport
                                    </a>
                                @endif
                                <form action="{{ route('raport.riwayat.hapus', $r->id) }}" method="POST" class="inline" onsubmit="return confirm('Hapus catatan riwayat cetak untuk {{ $r->kelasSiswa->siswa->nama ?? 'siswa ini' }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="inline-flex items-center px-2.5 py-1.5 text-xs font-bold rounded-xl bg-white hover:bg-rose-50 text-slate-400 hover:text-rose-600 border border-slate-200 hover:border-rose-200 transition shadow-2xs"
                                            title="Hapus Catatan Riwayat">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                <i class="fa-solid fa-clock-rotate-left text-3xl mb-2 block text-slate-300"></i>
                                <p class="font-bold text-slate-700">Belum ada riwayat pencetakan raport.</p>
                                <p class="text-xs text-slate-400 mt-1">Riwayat akan otomatis tercatat setiap kali ada raport yang diunduh atau dicetak.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($riwayatList->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $riwayatList->links() }}
            </div>
        @endif
    </div>

    <!-- Modal Konfirmasi Kosongkan Riwayat -->
    <div id="modalKosongkanRiwayat" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-3xl max-w-md w-full shadow-2xl border border-slate-100 overflow-hidden animate-in fade-in duration-200">
            <!-- Header Modal -->
            <div class="p-5 border-b border-rose-100 bg-rose-50/70 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-2xl bg-rose-100 text-rose-700 flex items-center justify-center text-lg font-bold shadow-xs">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-black text-rose-950">
                            Kosongkan Riwayat Cetak
                        </h3>
                        <p class="text-xs text-rose-700 mt-0.5">
                            Konfirmasi Penghapusan Log Cetak & Unduh
                        </p>
                    </div>
                </div>
                <button type="button" onclick="document.getElementById('modalKosongkanRiwayat').classList.add('hidden')"
                    class="w-8 h-8 rounded-full bg-white/80 hover:bg-white text-slate-500 hover:text-slate-700 flex items-center justify-center transition shadow-2xs">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form action="{{ route('raport.riwayat.kosongkan') }}" method="POST" class="p-6 space-y-4">
                @csrf
                @method('DELETE')

                <div class="p-3.5 bg-amber-50 border border-amber-200/80 rounded-2xl text-xs text-amber-950 flex items-start space-x-2.5">
                    <i class="fa-solid fa-circle-info text-amber-600 text-sm mt-0.5 shrink-0"></i>
                    <div>
                        <p class="font-bold">Perhatian Penghapusan</p>
                        <p class="text-[11px] text-amber-900 mt-0.5 leading-relaxed">
                            Apakah Anda yakin ingin mengosongkan riwayat cetak raport? Semua catatan log pencetakan dan unduhan raport yang tersimpan akan dihapus secara permanen.
                        </p>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Pilihan Periode Riwayat yang Dikosongkan
                    </label>
                    <div class="grid grid-cols-3 gap-2">
                        <label class="border border-slate-200 rounded-xl p-2.5 text-center cursor-pointer hover:bg-slate-50 transition has-[:checked]:border-rose-500 has-[:checked]:bg-rose-50/50 has-[:checked]:text-rose-900">
                            <input type="radio" name="periode" value="semua" class="sr-only" checked>
                            <span class="block text-xs font-bold">Semua</span>
                            <span class="block text-[10px] text-slate-500">STS & SAS</span>
                        </label>
                        <label class="border border-slate-200 rounded-xl p-2.5 text-center cursor-pointer hover:bg-slate-50 transition has-[:checked]:border-rose-500 has-[:checked]:bg-rose-50/50 has-[:checked]:text-rose-900">
                            <input type="radio" name="periode" value="sts" class="sr-only">
                            <span class="block text-xs font-bold">STS Saja</span>
                            <span class="block text-[10px] text-slate-500">Tengah Semester</span>
                        </label>
                        <label class="border border-slate-200 rounded-xl p-2.5 text-center cursor-pointer hover:bg-slate-50 transition has-[:checked]:border-rose-500 has-[:checked]:bg-rose-50/50 has-[:checked]:text-rose-900">
                            <input type="radio" name="periode" value="sas" class="sr-only">
                            <span class="block text-xs font-bold">SAS Saja</span>
                            <span class="block text-[10px] text-slate-500">Akhir Semester</span>
                        </label>
                    </div>
                </div>

                <div class="pt-2 flex items-center justify-end gap-2 border-t border-slate-100">
                    <button type="button" onclick="document.getElementById('modalKosongkanRiwayat').classList.add('hidden')"
                        class="px-4 py-2 text-xs font-bold text-slate-600 hover:text-slate-800 bg-slate-100 hover:bg-slate-200 rounded-xl transition">
                        Batal
                    </button>
                    <button type="submit"
                        class="px-4 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-xl transition shadow-sm flex items-center gap-1.5">
                        <i class="fa-solid fa-trash-can"></i> Ya, Kosongkan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('modalKosongkanRiwayat')?.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
            }
        });
    </script>

</div>
@endsection
