@php
    $ekskulItems = $ekskulListSiswa ?? [];
@endphp

<!-- TABEL EKSTRAKURIKULER -->
<table class="data" style="margin-top: 8px; margin-bottom: 10px;">
    <thead>
        <tr>
            <th style="width: 6%; text-align: center;">No</th>
            <th style="width: 28%; text-align: left;">Kegiatan Ekstrakurikuler</th>
            <th style="width: 14%; text-align: center;">Predikat</th>
            <th style="width: 52%; text-align: left;">Keterangan</th>
        </tr>
    </thead>
    <tbody>
        @forelse($ekskulItems as $eksIdx => $eks)
            <tr>
                <td style="text-align: center;">{{ $eksIdx + 1 }}</td>
                <td><b>{{ $eks['nama'] }}</b></td>
                <td style="text-align: center;"><b>{{ $eks['predikat'] }}</b></td>
                <td style="font-size: 7.5pt; color: #333; line-height: 1.35;">{{ $eks['keterangan'] }}</td>
            </tr>
        @empty
            <tr>
                <td style="text-align: center; color: #666;">-</td>
                <td style="color: #666; font-style: italic;">Belum ada kegiatan ekstrakurikuler yang diikuti</td>
                <td style="text-align: center; color: #666;">-</td>
                <td style="text-align: center; color: #666;">-</td>
            </tr>
        @endforelse
    </tbody>
</table>

