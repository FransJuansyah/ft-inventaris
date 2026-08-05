<?php

use App\Enums\KondisiBarang;
use App\Models\InventoryItem;
use App\Models\MaintenanceRecord;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component {
    #[Computed]
    public function barangDenganStatus()
    {
        return InventoryItem::with('currentCondition')->get();
    }

    #[Computed]
    public function totalBarang(): int
    {
        return $this->barangDenganStatus->count();
    }

    #[Computed]
    public function jumlahPerKondisi(): array
    {
        $counts = $this->barangDenganStatus->countBy(
            fn (InventoryItem $barang) => $barang->currentCondition?->condition_code?->value
        );

        return [
            'baik' => $counts->get(KondisiBarang::Baik->value, 0),
            'rusak_ringan' => $counts->get(KondisiBarang::RusakRingan->value, 0),
            'rusak_berat' => $counts->get(KondisiBarang::RusakBerat->value, 0),
        ];
    }

    #[Computed]
    public function barangTerbaru()
    {
        // created_at bisa kembar detik-nya (mis. data seeding) — id sebagai
        // penentu kedua supaya urutan "terbaru" konsisten dan benar.
        return InventoryItem::with(['subbuilding.building'])->latest()->latest('id')->take(5)->get();
    }

    #[Computed]
    public function riwayatTerbaru()
    {
        return MaintenanceRecord::with('inventoryItem')->latest('recorded_at')->latest('id')->take(5)->get();
    }
}; ?>

<div class="space-y-6">
    <x-ui.page-header
        :title="__('Dashboard')"
        :subtitle="__('Ringkasan inventaris Fakultas Teknik')"
    />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat :label="__('Total barang')" :value="Number::format($this->totalBarang, locale: 'id')" icon="cube" tone="brand" :href="route('barang.index')" />
        <x-ui.stat :label="__('Kondisi baik')" :value="Number::format($this->jumlahPerKondisi['baik'], locale: 'id')" icon="check-circle" tone="baik" />
        <x-ui.stat :label="__('Rusak ringan')" :value="Number::format($this->jumlahPerKondisi['rusak_ringan'], locale: 'id')" icon="exclamation-triangle" tone="rusak-ringan" />
        <x-ui.stat :label="__('Rusak berat')" :value="Number::format($this->jumlahPerKondisi['rusak_berat'], locale: 'id')" icon="x-circle" tone="rusak-berat" />
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <flux:card class="space-y-4 !border-zinc-300 !shadow-md dark:!border-zinc-700">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">{{ __('Barang terbaru') }}</flux:heading>
                <flux:link :accent="false" class="text-sm !text-blue-600 dark:!text-blue-400" :href="route('barang.index')" wire:navigate>
                    {{ __('Lihat semua') }}
                </flux:link>
            </div>

            @if ($this->barangTerbaru->isEmpty())
                <x-ui.empty-state
                    icon="cube"
                    :heading="__('Belum ada data')"
                    :description="__('Daftar barang yang baru ditambahkan akan muncul di sini.')"
                />
            @else
                <x-ui.timeline>
                    @foreach ($this->barangTerbaru as $barang)
                        <x-ui.timeline.item
                            :title="$barang->brand"
                            :time="$barang->created_at->diffForHumans()"
                            icon="cube"
                            tone="brand"
                        >
                            <flux:link :accent="false" class="text-sm !text-blue-600 dark:!text-blue-400" :href="route('barang.show.internal', $barang)" wire:navigate>
                                {{ $barang->subbuilding->name }} · {{ $barang->subbuilding->building->name }}
                            </flux:link>
                        </x-ui.timeline.item>
                    @endforeach
                </x-ui.timeline>
            @endif
        </flux:card>

        <flux:card class="space-y-4 !border-zinc-300 !shadow-md dark:!border-zinc-700">
            <flux:heading size="lg">{{ __('Riwayat perawatan terbaru') }}</flux:heading>

            @if ($this->riwayatTerbaru->isEmpty())
                <x-ui.empty-state
                    icon="wrench-screwdriver"
                    :heading="__('Belum ada riwayat')"
                    :description="__('Riwayat perawatan barang akan muncul di sini.')"
                />
            @else
                <x-ui.timeline>
                    @foreach ($this->riwayatTerbaru as $item)
                        <x-ui.timeline.item
                            :title="$item->inventoryItem->brand"
                            :time="$item->recorded_at->diffForHumans()"
                            icon="wrench-screwdriver"
                            tone="brand"
                        >
                            <flux:text size="sm">
                                {{ Str::limit($item->description, 60) }}
                                @if ($item->cost_amount)
                                    · {{ Number::currency((float) $item->cost_amount, 'IDR', 'id', precision: 0) }}
                                @endif
                            </flux:text>
                        </x-ui.timeline.item>
                    @endforeach
                </x-ui.timeline>
            @endif
        </flux:card>
    </div>
</div>
