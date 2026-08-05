{{--
    Daftar pasangan label/nilai untuk halaman detail (barang, riwayat, pengguna).
    Menumpuk di HP, dua kolom di layar lebar.

    <x-ui.detail-list>
        <x-ui.detail-item label="Kode NUP" :value="$barang->kode_nup" />
        <x-ui.detail-item label="No. Seri Barang" :value="$barang->no_seri_barang" />
    </x-ui.detail-list>
--}}
<dl {{ $attributes->class('divide-y divide-zinc-200 dark:divide-zinc-700') }}>
    {{ $slot }}
</dl>
