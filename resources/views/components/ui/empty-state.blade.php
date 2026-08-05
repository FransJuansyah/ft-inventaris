{{--
    Keadaan kosong. WAJIB dipakai di setiap daftar — tabel kosong tanpa
    penjelasan adalah keluhan paling umum di sistem lama.

    Bedakan dua kasus dan tulis pesan yang sesuai:
      - belum ada data sama sekali  -> ajak menambah data (pakai slot aksi)
      - hasil filter/pencarian nihil -> ajak mengubah kata kunci

    <x-ui.empty-state
        icon="cube"
        heading="Belum ada barang"
        description="Tambahkan barang pertama untuk ruangan ini."
    >
        <flux:button variant="primary" icon="plus">Tambah barang</flux:button>
    </x-ui.empty-state>
--}}
@props([
    'icon' => 'inbox',
    'heading',
    'description' => null,
])

<div {{ $attributes->class('flex flex-col items-center justify-center gap-3 px-6 py-12 text-center') }}>
    <div class="flex size-12 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
        <flux:icon :name="$icon" class="size-6 text-zinc-400 dark:text-zinc-500" />
    </div>

    <div class="space-y-1">
        <flux:heading size="lg">{{ $heading }}</flux:heading>

        @if (filled($description))
            <flux:text class="mx-auto max-w-sm">{{ $description }}</flux:text>
        @endif
    </div>

    @if (filled(trim($slot)))
        <div class="mt-2 flex flex-wrap items-center justify-center gap-2">
            {{ $slot }}
        </div>
    @endif
</div>
