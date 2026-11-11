@php
    $logoKiriPath = $sekolah->logo ? public_path('storage/' . $sekolah->logo) : null;
    if (!$logoKiriPath || !file_exists($logoKiriPath)) {
        $logoKiriPath = $sekolah->logo ? storage_path('app/public/' . $sekolah->logo) : null;
    }
    $logoKananPath = $sekolah->logo_kanan ? public_path('storage/' . $sekolah->logo_kanan) : null;
    if (!$logoKananPath || !file_exists($logoKananPath)) {
        $logoKananPath = $sekolah->logo_kanan ? storage_path('app/public/' . $sekolah->logo_kanan) : null;
    }
    $ttdPath = $sekolah->ttd ? public_path('storage/' . $sekolah->ttd) : null;
    if (!$ttdPath || !file_exists($ttdPath)) {
        $ttdPath = $sekolah->ttd ? storage_path('app/public/' . $sekolah->ttd) : null;
    }
@endphp
<table style="width: 100%; border-collapse: collapse; border-bottom: 3px double #000; padding-bottom: 4px; margin-bottom: 8px;">
    <tr>
        <td style="width: 12%; text-align: left; vertical-align: middle;">
            @if($logoKiriPath && file_exists($logoKiriPath))
                <img src="{{ $logoKiriPath }}" style="max-height: 55px; max-width: 55px;" alt="Logo Pemda">
            @endif
        </td>
        <td style="width: 76%; text-align: center; vertical-align: middle;">
            <div style="font-size: 9pt; font-weight: bold; text-transform: uppercase;">{{ $sekolah->pemda ?? 'PEMERINTAH KABUPATEN BANYUWANGI' }}</div>
            <div style="font-size: 9pt; font-weight: bold; text-transform: uppercase;">{{ $sekolah->dinas ?? 'DINAS PENDIDIKAN' }}</div>
            <div style="font-size: 12pt; font-weight: bold; text-transform: uppercase; margin: 1px 0;">{{ $sekolah->nama ?? 'SD NEGERI 2 KARANGSARI' }}</div>
            <div style="font-size: 7.5pt; font-style: italic; color: #333;">{{ $sekolah->alamat ?? 'Jl. Karangsari No. 12, Sempu, Banyuwangi' }}</div>
            @if($sekolah->kontak)
                <div style="font-size: 7pt; color: #444;">{{ $sekolah->kontak }}</div>
            @endif
        </td>
        <td style="width: 12%; text-align: right; vertical-align: middle;">
            @if($logoKananPath && file_exists($logoKananPath))
                <img src="{{ $logoKananPath }}" style="max-height: 55px; max-width: 55px;" alt="Logo Sekolah">
            @endif
        </td>
    </tr>
</table>

