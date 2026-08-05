<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Label QR Barang</title>
    <style>
        {{-- dompdf: CSS-nya harus sederhana (tabel, bukan flex/grid modern). --}}
        body { font-family: sans-serif; font-size: 9px; color: #18181b; margin: 0; }
        table.grid { width: 100%; border-collapse: collapse; }
        table.grid td { width: 33.33%; padding: 4px; vertical-align: top; }
        .label {
            border: 1px solid #a1a1aa;
            border-radius: 4px;
            padding: 6px;
            text-align: center;
        }
        .label img { width: 70px; height: 70px; }
        .label .nama { font-weight: bold; font-size: 10px; margin-top: 4px; }
        .label .lokasi { color: #52525b; margin-top: 2px; }
        .label .seri { font-family: monospace; margin-top: 2px; }
    </style>
</head>
<body>
    <table class="grid">
        <?php $kolom = 0; ?>
        @foreach ($barangList as $barang)
            @if ($kolom === 0)
                <tr>
            @endif

            <td>
                <div class="label">
                    <img src="{{ $barang->qrCodeDataUri() }}" alt="QR">
                    <div class="nama">{{ $barang->brand }}</div>
                    <div class="lokasi">{{ $barang->subbuilding->name }}, {{ $barang->subbuilding->building->name }}</div>
                    <div class="seri">{{ $barang->nup_code ?: ($barang->faculty_code ?: '-') }}</div>
                </div>
            </td>

            <?php $kolom = ($kolom + 1) % 3; ?>
            @if ($kolom === 0)
                </tr>
            @endif
        @endforeach

        @if ($kolom !== 0)
            @for ($i = $kolom; $i < 3; $i++)
                <td></td>
            @endfor
            </tr>
        @endif
    </table>
</body>
</html>
