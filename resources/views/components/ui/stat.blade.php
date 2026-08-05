{{--
    Kartu statistik untuk dashboard.
    flux:chart adalah komponen Pro, jadi dashboard memakai kartu angka —
    bukan grafik.

    Catatan: flux:card selalu merender <div> dan tidak menerima href, jadi
    kartu ini menyusun markup-nya sendiri. Saat `href` diisi, seluruh kartu
    menjadi target klik lewat tautan bertumpuk (bukan tag dinamis).

    <x-ui.stat label="Total barang" value="1.204" icon="cube" />
    <x-ui.stat label="Rusak berat" value="18" tone="rusak-berat" :href="route('barang.index', ['kondisi' => 2])" />
--}}
@props([
    'label',
    'value',
    'icon' => null,
    'description' => null,
    'tone' => 'neutral',
    'href' => null,
])

@php
    // Kelas ditulis literal (bukan interpolasi) supaya terbaca pemindai Tailwind.
    $toneClasses = match ($tone) {
        'brand' => 'bg-brand-50 text-brand-700 dark:bg-brand-950 dark:text-brand-300',
        'baik' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
        'rusak-ringan' => 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
        'rusak-berat' => 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300',
        'penghapusan' => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300',
        default => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300',
    };
@endphp

<div
    {{ $attributes->class([
        'relative flex items-start gap-4 rounded-xl border border-zinc-200 bg-white p-5 dark:border-white/10 dark:bg-white/10',
        'transition focus-within:border-accent hover:border-zinc-300 dark:hover:border-white/20' => filled($href),
    ]) }}
>
    @if ($icon)
        <div class="flex size-10 shrink-0 items-center justify-center rounded-lg {{ $toneClasses }}">
            <flux:icon :name="$icon" class="size-5" />
        </div>
    @endif

    <div class="min-w-0 space-y-0.5">
        <flux:text size="sm">{{ $label }}</flux:text>

        <div class="text-2xl font-semibold tabular-nums text-zinc-900 dark:text-white">
            {{ $value }}
        </div>

        @if (filled($description))
            <flux:text size="sm" class="!text-zinc-400 dark:!text-zinc-500">{{ $description }}</flux:text>
        @endif
    </div>

    @if (filled($href))
        <a href="{{ $href }}" wire:navigate class="absolute inset-0 rounded-xl" aria-label="{{ $label }}"></a>
    @endif
</div>
