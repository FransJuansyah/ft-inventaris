{{--
    Satu baris label/nilai. Nilai kosong ditampilkan sebagai "—", bukan
    dibiarkan blank, supaya jelas bedanya "kosong" dan "salah render".

    Isi bebas bisa lewat slot:
    <x-ui.detail-item label="Kondisi"><x-ui.status-badge :kondisi="0" /></x-ui.detail-item>
--}}
@props([
    'label',
    'value' => null,
])

<div {{ $attributes->class('grid gap-1 py-3 sm:grid-cols-3 sm:gap-4') }}>
    <dt class="text-sm text-zinc-500 dark:text-zinc-400">{{ $label }}</dt>

    <dd class="text-sm text-zinc-900 sm:col-span-2 dark:text-white">
        @if (filled(trim($slot)))
            {{ $slot }}
        @elseif (filled($value))
            {{ $value }}
        @else
            <span class="text-zinc-400 dark:text-zinc-500">&mdash;</span>
        @endif
    </dd>
</div>
