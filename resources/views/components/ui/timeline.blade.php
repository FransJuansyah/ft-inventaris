{{--
    Garis waktu untuk log yang hanya bertambah (append-only): riwayat
    perubahan kondisi (`barang_status`) dan riwayat perawatan (`history`).

    Keduanya urut terbaru dulu.

    <x-ui.timeline>
        @foreach ($riwayat as $item)
            <x-ui.timeline.item :time="$item->created_at->diffForHumans()" icon="wrench-screwdriver">
                ...
            </x-ui.timeline.item>
        @endforeach
    </x-ui.timeline>
--}}
<ol {{ $attributes }}>
    {{ $slot }}
</ol>
