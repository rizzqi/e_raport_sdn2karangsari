@php
    $ekskulItems = $ekskulListSiswa ?? [];
    $isCompact = (bool) ($compact ?? false);
@endphp

@if($isCompact)
    <div class="mb-2.5">
        <table class="w-full border-collapse border border-black text-[8.5pt]">
            <thead>
                <tr class="bg-slate-200 font-bold text-slate-900">
                    <th class="border border-black py-1 px-2 text-center w-[6%]">No</th>
                    <th class="border border-black py-1 px-2.5 text-left w-[28%]">Kegiatan Ekstrakurikuler</th>
                    <th class="border border-black py-1 px-2 text-center w-[15%]">Predikat</th>
                    <th class="border border-black py-1 px-2.5 text-left w-[51%]">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ekskulItems as $eksIdx => $eks)
                    <tr>
                        <td class="border border-black py-1 px-2 text-center font-semibold">{{ $eksIdx + 1 }}</td>
                        <td class="border border-black py-1 px-2.5 font-semibold text-slate-900">{{ $eks['nama'] }}</td>
                        <td class="border border-black py-1 px-2 text-center font-bold text-slate-900">{{ $eks['predikat'] }}</td>
                        <td class="border border-black py-1 px-2.5 text-slate-700 leading-snug">{{ $eks['keterangan'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="border border-black py-1 px-2 text-center text-slate-500">-</td>
                        <td class="border border-black py-1 px-2.5 text-slate-500 italic">Belum ada kegiatan ekstrakurikuler yang diikuti</td>
                        <td class="border border-black py-1 px-2 text-center text-slate-500">-</td>
                        <td class="border border-black py-1 px-2.5 text-slate-500">-</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@else
    <div class="mt-4 overflow-x-auto">
        <table class="w-full text-xs sm:text-sm border-collapse border border-slate-900">
            <thead>
                <tr class="bg-slate-200 text-slate-900 font-bold">
                    <th class="border border-slate-900 py-2 px-2 text-center w-10">No</th>
                    <th class="border border-slate-900 py-2 px-3 text-left w-[28%]">Kegiatan Ekstrakurikuler</th>
                    <th class="border border-slate-900 py-2 px-3 text-center w-[15%]">Predikat</th>
                    <th class="border border-slate-900 py-2 px-3 text-left">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ekskulItems as $eksIdx => $eks)
                    <tr>
                        <td class="border border-slate-900 py-2 px-2 text-center font-semibold">{{ $eksIdx + 1 }}</td>
                        <td class="border border-slate-900 py-2 px-3 font-bold text-slate-900">{{ $eks['nama'] }}</td>
                        <td class="border border-slate-900 py-2 px-3 text-center font-bold text-slate-900">{{ $eks['predikat'] }}</td>
                        <td class="border border-slate-900 py-2 px-3 text-xs text-slate-700 leading-relaxed">{{ $eks['keterangan'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="border border-slate-900 py-2 px-2 text-center text-slate-500">-</td>
                        <td class="border border-slate-900 py-2 px-3 text-slate-500 italic">Belum ada kegiatan ekstrakurikuler yang diikuti</td>
                        <td class="border border-slate-900 py-2 px-3 text-center text-slate-500">-</td>
                        <td class="border border-slate-900 py-2 px-3 text-xs text-slate-500">-</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endif

