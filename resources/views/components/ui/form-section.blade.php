{{--
    Satu blok dalam form panjang (mis. form tambah barang: identitas,
    lokasi, nomor seri, foto). Judul di kiri, isian di kanan pada layar
    lebar; menumpuk di HP.

    <x-ui.form-section heading="Lokasi" description="Tempat barang ini berada.">
        <flux:select wire:model="gedung_id" label="Gedung">...</flux:select>
        <flux:select wire:model="subgedung_id" label="Ruangan">...</flux:select>
    </x-ui.form-section>
--}}
@props([
    'heading',
    'description' => null,
])

<div {{ $attributes->class('grid gap-4 border-b border-zinc-200 py-6 first:pt-0 last:border-0 last:pb-0 md:grid-cols-3 md:gap-8 dark:border-zinc-700') }}>
    <div class="space-y-1">
        <flux:heading size="lg">{{ $heading }}</flux:heading>

        @if (filled($description))
            <flux:text size="sm">{{ $description }}</flux:text>
        @endif
    </div>

    <div class="space-y-4 md:col-span-2">
        {{ $slot }}
    </div>
</div>
