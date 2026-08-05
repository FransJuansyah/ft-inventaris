{{--
    SATU-SATUNYA definisi navigasi aplikasi.

    Sidebar desktop dan menu mobile sama-sama membaca berkas ini, jadi
    menambah modul cukup menambah satu baris di sini.

    Selama rebuild masih berjalan, item yang route-nya belum dibuat akan
    dilewati otomatis — menu ikut menyala sendiri begitu modulnya jadi.

    PENTING: menyembunyikan menu BUKAN otorisasi. Pembatasan hak akses
    tetap harus di route/komponen lewat middleware. Berkas ini hanya
    mengatur apa yang terlihat.
--}}
@php
    $isAdmin = auth()->user()?->isAdmin() ?? false;
    $isSuperAdmin = auth()->user()?->isSuperAdmin() ?? false;

    $groups = [
        __('Utama') => [
            ['route' => 'dashboard', 'icon' => 'home', 'label' => __('Dashboard')],
            ['route' => 'scan', 'icon' => 'qr-code', 'label' => __('Scan QR')],
        ],
        __('Inventaris') => [
            ['route' => 'barang.index', 'icon' => 'cube', 'label' => __('Barang')],
            ['route' => 'gedung.index', 'icon' => 'building-office-2', 'label' => __('Gedung'), 'admin' => true],
        ],
        __('Data Master') => [
            ['route' => 'jenis-barang.index', 'icon' => 'tag', 'label' => __('Jenis Barang'), 'admin' => true],
            // Manajemen pengguna khusus Super Admin — Admin biasa tidak
            // boleh membuat/menghapus akun (lihat routes/web.php).
            ['route' => 'pengguna.index', 'icon' => 'users', 'label' => __('Pengguna'), 'superAdmin' => true],
        ],
    ];
@endphp

@foreach ($groups as $heading => $items)
    @php
        // Menyembunyikan menu BUKAN otorisasi (lihat catatan di atas) — ini
        // cuma mengikuti route guard `admin`/`super-admin` yang sudah
        // dipasang server-side di routes/web.php, supaya pengguna yang
        // tidak berhak tidak melihat menu yang akan berujung 403.
        $items = array_filter(
            $items,
            fn (array $item) => Route::has($item['route'])
                && (! ($item['admin'] ?? false) || $isAdmin)
                && (! ($item['superAdmin'] ?? false) || $isSuperAdmin)
        );
    @endphp

    @if (filled($items))
        <flux:sidebar.group :heading="$heading" class="grid">
            @foreach ($items as $item)
                <flux:sidebar.item
                    :icon="$item['icon']"
                    :href="route($item['route'])"
                    :current="request()->routeIs(str($item['route'])->before('.')->value().'*')"
                    wire:navigate
                >
                    {{ $item['label'] }}
                </flux:sidebar.item>
            @endforeach
        </flux:sidebar.group>
    @endif
@endforeach
