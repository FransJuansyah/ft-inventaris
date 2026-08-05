{{--
    Baris pencarian + filter di atas daftar. Menumpuk vertikal di HP,
    sebaris di layar lebar.

    Semua isian di dalamnya sebaiknya terikat ke properti Livewire ber-#[Url]
    supaya keadaan filter tersimpan di query string.

    <x-ui.toolbar>
        <x-slot:search>
            <flux:input wire:model.live.debounce.300ms="cari" icon="magnifying-glass" placeholder="Cari barang..." />
        </x-slot:search>

        <flux:select wire:model.live="gedung" placeholder="Semua gedung">...</flux:select>
    </x-ui.toolbar>
--}}
<div {{ $attributes->class('flex flex-col gap-3 md:flex-row md:items-center') }}>
    @isset($search)
        <div class="md:w-full md:max-w-xs">{{ $search }}</div>
    @endisset

    @if (filled(trim($slot)))
        {{-- Filter bisa lebih banyak dari lebar layar: biarkan menggulung horizontal di HP. --}}
        <div class="flex flex-wrap items-center gap-2 md:ms-auto">
            {{ $slot }}
        </div>
    @endif
</div>
