{{--
    Satu entri garis waktu. Lihat <x-ui.timeline> untuk contoh pemakaian.

    `tone` menerima nilai yang sama dengan <x-ui.stat>, jadi entri
    perubahan kondisi bisa diwarnai sesuai kondisinya.
--}}
@props([
    'title' => null,
    'time' => null,
    'icon' => null,
    'tone' => 'neutral',
])

@php
    $toneClasses = match ($tone) {
        'brand' => 'bg-brand-100 text-brand-700 dark:bg-brand-900 dark:text-brand-300',
        'baik' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300',
        'rusak-ringan' => 'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300',
        'rusak-berat' => 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300',
        'penghapusan' => 'bg-zinc-200 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300',
        default => 'bg-zinc-200 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300',
    };
@endphp

{{--
    Ikon dan teks jadi kolom flex sejajar dengan `gap-3` eksplisit — bukan
    ikon absolute-positioned menimpa teks (bug lama: cincin `ring-4` di
    ikon melebihi lebar yang disediakan `ps-6`, jadi huruf pertama judul
    ketutup). Garis vertikal penghubung juga ikut jadi bagian kolom ikon
    (flex-1), disembunyikan di entri terakhir supaya tidak ada ekor
    menggantung tanpa entri berikutnya.
--}}
<li {{ $attributes->class('relative flex gap-3') }}>
    <div class="flex flex-col items-center self-stretch">
        <span class="flex size-6 shrink-0 items-center justify-center rounded-full {{ $toneClasses }}">
            @if ($icon)
                <flux:icon :name="$icon" class="size-3.5" />
            @endif
        </span>

        <span class="mt-1 w-px flex-1 bg-zinc-200 [li:last-child_&]:hidden dark:bg-zinc-700" aria-hidden="true"></span>
    </div>

    <div class="min-w-0 flex-1 space-y-1 pb-6">
        @if (filled($title) || filled($time))
            <div class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-0.5">
                @if (filled($title))
                    <flux:heading size="sm">{{ $title }}</flux:heading>
                @endif

                @if (filled($time))
                    <flux:text size="sm" class="!text-zinc-400 dark:!text-zinc-500">{{ $time }}</flux:text>
                @endif
            </div>
        @endif

        {{ $slot }}
    </div>
</li>
