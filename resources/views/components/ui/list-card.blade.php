{{--
    Baris daftar untuk tampilan HP — pengganti baris tabel yang butuh geser
    horizontal di layar sempit. Dipakai berpasangan dengan flux:table:
    tabel disembunyikan di HP (`hidden sm:block`), kartu ini disembunyikan
    di desktop (`sm:hidden`).

    <div class="space-y-2 sm:hidden">
        @foreach ($gedungList as $gedung)
            <x-ui.list-card :href="route('subgedung.index', $gedung)" :title="$gedung->nama_gedung">
                <x-slot:meta>{{ $gedung->subgedung_count }} ruangan</x-slot:meta>
                <x-slot:trailing>
                    <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="edit({{ $gedung->id }})" />
                </x-slot:trailing>
            </x-ui.list-card>
        @endforeach
    </div>

    Kalau `href` diisi, seluruh kartu jadi target tap (lewat tautan lapis
    penuh) KECUALI area `trailing` — tombol di sana tetap bisa ditekan
    sendiri berkat z-index lebih tinggi.
--}}
@props([
    'href' => null,
    'title' => null,
    'icon' => null,
])

<div
    {{ $attributes->class([
        'relative flex items-center gap-3 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-white/5',
        'transition active:bg-zinc-50 dark:active:bg-white/10' => filled($href),
    ]) }}
>
    @if ($icon)
        <flux:icon :name="$icon" class="size-5 shrink-0 text-zinc-400 dark:text-zinc-500" />
    @endif

    <div class="min-w-0 flex-1">
        {{--
            Judul TIDAK di-truncate satu baris — beberapa nama (mis. gedung
            berawalan sama "Gedung Teknik...") jadi tidak terbedakan kalau
            dipotong ellipsis di layar sempit. Biarkan wrap sampai 2 baris.
        --}}
        @isset($title)
            <div class="line-clamp-2 font-medium text-zinc-900 dark:text-white">{{ $title }}</div>
        @endisset

        @isset($meta)
            <div class="mt-0.5 line-clamp-2 text-sm text-zinc-500 dark:text-zinc-400">{{ $meta }}</div>
        @endisset

        {{ $slot }}
    </div>

    @isset($trailing)
        <div class="relative z-10 flex shrink-0 items-center gap-1">{{ $trailing }}</div>
    @endisset

    @if (filled($href))
        <flux:icon name="chevron-right" class="size-4 shrink-0 text-zinc-300 dark:text-zinc-600" />
        <a href="{{ $href }}" wire:navigate class="absolute inset-0" aria-label="{{ $title }}"></a>
    @endif
</div>
