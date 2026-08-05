<?php

use App\Enums\KondisiBarang;
use App\Models\InventoryItem;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Detail Barang')] class extends Component {
    public string $token;

    public ?InventoryItem $barang = null;

    public function mount(string $token): void
    {
        $this->token = $token;

        $this->barang = InventoryItem::where('public_token', $token)
            ->with(['subbuilding.building', 'itemType', 'images', 'conditions'])
            ->first();
    }
}; ?>

<div class="mx-auto max-w-3xl space-y-6">
    <x-ui.page-header :title="__('Detail Barang')">
        <x-slot:actions>
            <flux:button :href="route('inventaris.index')" wire:navigate icon="arrow-left">
                {{ __('Kembali ke daftar') }}
            </flux:button>
            <flux:button :href="route('scan')" wire:navigate variant="primary" icon="qr-code">
                {{ __('Scan QR lain') }}
            </flux:button>
        </x-slot:actions>
    </x-ui.page-header>

    @if (! $barang)
        <flux:card class="!border-zinc-300 !shadow-md dark:!border-zinc-700">
            <x-ui.empty-state
                icon="exclamation-triangle"
                :heading="__('Barang tidak ditemukan')"
                :description="__('Kode QR yang dipindai tidak cocok dengan data barang manapun.')"
            />
        </flux:card>
    @elseif ($barang->archived_at)
        <flux:card class="!border-zinc-300 !shadow-md dark:!border-zinc-700">
            <x-ui.empty-state
                icon="archive-box"
                :heading="__('Barang Tidak Aktif')"
                :description="__('Barang ini sudah diarsipkan dan tidak lagi ditampilkan di portal publik.')"
            />
        </flux:card>
    @else
        <flux:card class="!border-zinc-300 !shadow-md dark:!border-zinc-700">
            <x-ui.photo-gallery :photos="$barang->imageUrls()" :alt="$barang->brand" />
        </flux:card>

        <flux:card class="space-y-4 !border-zinc-300 !shadow-md dark:!border-zinc-700">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">{{ $barang->brand }}</flux:heading>
                <x-ui.status-badge :kondisi="$barang->currentCondition?->condition_code" size="sm" />
            </div>

            <x-ui.detail-list>
                <x-ui.detail-item :label="__('Jenis Barang')" :value="$barang->itemType->name" />
                <x-ui.detail-item :label="__('Spesifikasi')" :value="$barang->specification" />
                <x-ui.detail-item :label="__('Lokasi')" :value="$barang->subbuilding->name.' — '.$barang->subbuilding->building->name" />
                <x-ui.detail-item :label="__('Asal Barang')" :value="$barang->acquisition_source" />
                <x-ui.detail-item :label="__('Tanggal Masuk')" :value="$barang->entry_date?->translatedFormat('d F Y')" />
                <x-ui.detail-item :label="__('Kode NUP')" :value="$barang->nup_code" />
                <x-ui.detail-item :label="__('Kode FT')" :value="$barang->faculty_code" />
                <x-ui.detail-item :label="__('No. Seri Barang')" :value="$barang->serial_number" />
                <x-ui.detail-item :label="__('Terakhir diperbarui')" :value="$barang->updated_at?->translatedFormat('d F Y, H:i')" />
            </x-ui.detail-list>
        </flux:card>

        <flux:card class="space-y-4 !border-zinc-300 !shadow-md dark:!border-zinc-700">
            <flux:heading size="lg">{{ __('Riwayat Kondisi') }}</flux:heading>

            @if ($barang->conditions->isEmpty())
                <x-ui.empty-state
                    icon="exclamation-triangle"
                    :heading="__('Belum ada riwayat kondisi')"
                />
            @else
                <x-ui.timeline>
                    @foreach ($barang->conditions as $riwayatStatus)
                        <x-ui.timeline.item
                            :title="$riwayatStatus->condition_code->label()"
                            :time="$riwayatStatus->recorded_at->translatedFormat('d M Y, H:i')"
                            :icon="$riwayatStatus->condition_code->icon()"
                            :tone="match ($riwayatStatus->condition_code) {
                                KondisiBarang::Baik => 'baik',
                                KondisiBarang::RusakRingan => 'rusak-ringan',
                                KondisiBarang::RusakBerat => 'rusak-berat',
                                KondisiBarang::Penghapusan => 'penghapusan',
                            }"
                        >
                            @if ($riwayatStatus->notes)
                                <flux:text size="sm">{{ $riwayatStatus->notes }}</flux:text>
                            @endif
                        </x-ui.timeline.item>
                    @endforeach
                </x-ui.timeline>
            @endif
        </flux:card>
    @endif
</div>
