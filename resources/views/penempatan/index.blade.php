@extends('layouts.app')

@section('title', 'Penempatan Siswa - ' . $kelas->nama)

@section('content')
<div class="space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('kelas.index') }}" class="hover:underline">Kelas</a>
                <span>&bull;</span>
                <a href="{{ route('kelas.show', $kelas->id) }}" class="hover:underline">{{ $kelas->nama }}</a>
                <span>&bull;</span>
                <span class="text-slate-800 font-bold">Penempatan Siswa</span>
            </div>
            <h1 class="text-xl font-bold text-slate-900 flex items-center">
                <i class="fa-solid fa-users-rectangle text-emerald-600 mr-2"></i> Penempatan Siswa: {{ $kelas->nama }}
            </h1>
            <p class="text-xs text-slate-500">Tahun Ajaran: {{ $kelas->tahunAjaran->nama_lengkap ?? '-' }} &bull; Fase: {{ $kelas->fase ?? 'A' }}</p>
        </div>

        <a href="{{ route('kelas.show', $kelas->id) }}" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl text-xs transition flex items-center">
            <i class="fa-solid fa-arrow-left mr-1.5"></i> Kembali ke Kelas
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Kolom Kiri: Siswa Sudah Terdaftar -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden flex flex-col justify-between">
            <div>
                <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                        <i class="fa-solid fa-circle-check text-emerald-600 mr-2"></i> Siswa di Kelas Ini ({{ $kelas->kelasSiswa->count() }})
                    </h2>
                    <span class="text-xs text-slate-400">Terdaftar di {{ $kelas->nama }}</span>
                </div>

                <div class="divide-y divide-slate-100 max-h-[500px] overflow-y-auto">
                    @forelse($kelas->kelasSiswa as $index => $ks)
                        <div class="p-3.5 flex items-center justify-between hover:bg-slate-50 transition">
                            <div class="flex items-center space-x-3">
                                <span class="text-xs font-bold text-slate-400 w-5">{{ $index + 1 }}</span>
                                <div>
                                    <p class="font-bold text-sm text-slate-900">{{ $ks->siswa->nama }}</p>
                                    <p class="text-xs text-slate-400 font-mono">NIS: {{ $ks->siswa->nis }}</p>
                                </div>
                            </div>

                            <form action="{{ route('penempatan.remove', ['kelas' => $kelas->id, 'kelasSiswa' => $ks->id]) }}" method="POST" onsubmit="return confirm('Keluarkan siswa ini dari kelas?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 transition" title="Keluarkan dari kelas">
                                    <i class="fa-solid fa-user-minus"></i>
                                </button>
                            </form>
                        </div>
                    @empty
                        <div class="p-8 text-center text-slate-400 text-xs">
                            Belum ada siswa yang ditempatkan di kelas ini.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Tambahkan Siswa Tersedia -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                    <i class="fa-solid fa-user-plus text-blue-600 mr-2"></i> Siswa Tersedia ({{ $siswaTersedia->count() }})
                </h2>
                <span class="text-xs text-slate-400">Pilih untuk masukkan ke kelas</span>
            </div>

            <form action="{{ route('penempatan.assign', $kelas->id) }}" method="POST">
                @csrf

                <div class="p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50 text-xs">
                    <label class="flex items-center cursor-pointer font-bold text-slate-700">
                        <input type="checkbox" id="checkAll" class="rounded border-slate-300 text-emerald-600 mr-2" onchange="toggleAll(this)">
                        Pilih Semua Siswa
                    </label>
                    <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-sm transition">
                        <i class="fa-solid fa-plus mr-1"></i> Tempatkan Siswa Terpilih
                    </button>
                </div>

                <div class="divide-y divide-slate-100 max-h-[440px] overflow-y-auto">
                    @forelse($siswaTersedia as $s)
                        <label class="p-3.5 flex items-center space-x-3 hover:bg-slate-50 cursor-pointer transition block">
                            <input type="checkbox" name="siswa_ids[]" value="{{ $s->id }}" class="siswa-checkbox rounded border-slate-300 text-emerald-600">
                            <div>
                                <p class="font-bold text-sm text-slate-900">{{ $s->nama }}</p>
                                <p class="text-xs text-slate-400 font-mono">NIS: {{ $s->nis }} &bull; NISN: {{ $s->nisn ?? '-' }}</p>
                            </div>
                        </label>
                    @empty
                        <div class="p-8 text-center text-slate-400 text-xs">
                            Tidak ada siswa aktif yang belum memiliki kelas.
                        </div>
                    @endforelse
                </div>
            </form>
        </div>

    </div>

</div>

@push('scripts')
<script>
    function toggleAll(master) {
        document.querySelectorAll('.siswa-checkbox').forEach(cb => {
            cb.checked = master.checked;
        });
    }
</script>
@endpush
@endsection
