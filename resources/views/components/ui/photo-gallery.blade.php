{{--
    Pengganti flux:carousel (komponen Pro).

    Galeri foto barang / foto bukti perawatan: kisi thumbnail yang dibuka
    jadi tampilan besar (lightbox) saat diklik. Navigasi lewat panah kiri/
    kanan, tutup dengan Escape. Semua di Alpine — tidak ada round-trip ke
    server saat berpindah foto.

    <x-ui.photo-gallery :photos="$barang->foto_urls" alt="Foto barang" />

    $photos boleh berisi string URL, atau array ['url' => ..., 'caption' => ...].
--}}
@props([
    'photos' => [],
    'alt' => 'Foto',
])

@php
    $items = collect($photos)
        ->map(fn ($photo) => is_array($photo)
            ? ['url' => $photo['url'] ?? '', 'caption' => $photo['caption'] ?? null]
            : ['url' => $photo, 'caption' => null])
        ->filter(fn ($photo) => filled($photo['url']))
        ->values();
@endphp

@if ($items->isEmpty())
    <div {{ $attributes->class('flex flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-zinc-300 py-10 dark:border-zinc-700') }}>
        <flux:icon name="photo" class="size-8 text-zinc-300 dark:text-zinc-600" />
        <flux:text size="sm">{{ __('Belum ada foto') }}</flux:text>
    </div>
@else
    <div
        {{ $attributes }}
        x-data="{
            photos: @js($items),
            open: false,
            index: 0,
            show(i) { this.index = i; this.open = true },
            next() { this.index = (this.index + 1) % this.photos.length },
            prev() { this.index = (this.index - 1 + this.photos.length) % this.photos.length },
        }"
        @keydown.escape.window="open = false"
        @keydown.arrow-right.window="open && next()"
        @keydown.arrow-left.window="open && prev()"
    >
        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4">
            <template x-for="(photo, i) in photos" :key="i">
                <button
                    type="button"
                    @click="show(i)"
                    class="group aspect-square overflow-hidden rounded-lg border border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800"
                >
                    <img
                        :src="photo.url"
                        :alt="@js($alt) + ' ' + (i + 1)"
                        loading="lazy"
                        class="size-full object-cover transition group-hover:scale-105"
                    />
                </button>
            </template>
        </div>

        {{-- Lightbox --}}
        <div
            x-show="open"
            x-cloak
            x-transition.opacity
            @click.self="open = false"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
            role="dialog"
            aria-modal="true"
        >
            <button
                type="button"
                @click="open = false"
                class="absolute end-4 top-4 rounded-full bg-white/10 p-2 text-white hover:bg-white/20"
                aria-label="{{ __('Tutup') }}"
            >
                <flux:icon name="x-mark" class="size-6" />
            </button>

            <template x-if="photos.length > 1">
                <button
                    type="button"
                    @click="prev()"
                    class="absolute start-2 rounded-full bg-white/10 p-2 text-white hover:bg-white/20 sm:start-6"
                    aria-label="{{ __('Foto sebelumnya') }}"
                >
                    <flux:icon name="chevron-left" class="size-6" />
                </button>
            </template>

            <figure class="max-h-full max-w-3xl text-center">
                <img :src="photos[index]?.url" :alt="@js($alt)" class="mx-auto max-h-[75vh] rounded-lg object-contain" />

                <figcaption class="mt-3 text-sm text-white/70">
                    <span x-text="photos[index]?.caption"></span>
                    <span class="ms-2 tabular-nums" x-text="`${index + 1} / ${photos.length}`"></span>
                </figcaption>
            </figure>

            <template x-if="photos.length > 1">
                <button
                    type="button"
                    @click="next()"
                    class="absolute end-2 rounded-full bg-white/10 p-2 text-white hover:bg-white/20 sm:end-6"
                    aria-label="{{ __('Foto berikutnya') }}"
                >
                    <flux:icon name="chevron-right" class="size-6" />
                </button>
            </template>
        </div>
    </div>
@endif
