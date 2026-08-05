{{--
    Kepala halaman: judul + subjudul + tombol aksi.
    Dipakai di SETIAP halaman modul supaya tinggi & jarak antar halaman seragam.

    <x-ui.page-header title="Barang" subtitle="1.204 barang terdaftar">
        <x-slot:breadcrumbs>
            <flux:breadcrumbs>...</flux:breadcrumbs>
        </x-slot:breadcrumbs>
        <x-slot:actions>
            <flux:button variant="primary" icon="plus">Tambah</flux:button>
        </x-slot:actions>
    </x-ui.page-header>
--}}
@props([
    'title',
    'subtitle' => null,
])

<div {{ $attributes->class('flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between') }}>
    <div class="min-w-0 space-y-1">
        @isset($breadcrumbs)
            <div class="mb-2">{{ $breadcrumbs }}</div>
        @endisset

        <flux:heading size="xl" level="1">{{ $title }}</flux:heading>

        @if (filled($subtitle))
            <flux:subheading>{{ $subtitle }}</flux:subheading>
        @endif
    </div>

    @isset($actions)
        {{-- Di HP tombol aksi melebar penuh supaya mudah ditekan. --}}
        <div class="flex shrink-0 flex-wrap items-center gap-2 max-sm:*:flex-1">
            {{ $actions }}
        </div>
    @endisset
</div>
