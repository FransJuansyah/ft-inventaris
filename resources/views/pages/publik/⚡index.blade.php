<?php

use App\Enums\KondisiBarang;
use App\Models\Building;
use App\Models\InventoryItem;
use App\Models\ItemType;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Daftar Inventaris')] class extends Component {
    use WithPagination;

    #[Url]
    public string $cari = '';

    #[Url]
    public ?int $gedung = null;

    #[Url]
    public ?int $jenis = null;

    #[Url]
    public ?int $kondisi = null;

    #[Url]
    public string $urut = 'terbaru';

    #[Url]
    public string $arah = 'desc';

    #[Url]
    public int $perHalaman = 20;

    public function updatingCari(): void
    {
        $this->resetPage();
    }

    public function updatingGedung(): void
    {
        $this->resetPage();
    }

    public function updatingJenis(): void
    {
        $this->resetPage();
    }

    public function updatingKondisi(): void
    {
        $this->resetPage();
    }

    public function updatingPerHalaman(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        return [
            'barangList' => InventoryItem::query()
                ->publik()
                ->with([
                    'subbuilding.building',
                    'itemType',
                    'images' => fn ($q) => $q->orderBy('sort_order')->limit(1),
                    'currentCondition',
                ])
                ->filter($this->cari, $this->gedung, $this->jenis, $this->urut, $this->arah)
                ->when($this->kondisi !== null, fn ($q) => $q->whereHas(
                    'currentCondition',
                    fn ($q) => $q->where('condition_code', $this->kondisi)
                ))
                ->paginate($this->perHalaman),
            'gedungOptions' => Building::whereNull('archived_at')->orderBy('name')->get(),
            'jenisOptions' => ItemType::whereNull('archived_at')->orderBy('name')->get(),
            'kondisiOptions' => KondisiBarang::options(),
        ];
    }
}; ?>

<div class="space-y-6">
    <x-ui.page-header :title="__('Daftar Inventaris')" :subtitle="__('Data barang inventaris Fakultas Teknik, Universitas Sriwijaya')">
        <x-slot:actions>
            <flux:button :href="route('scan')" wire:navigate variant="primary" icon="qr-code">
                {{ __('Scan QR') }}
            </flux:button>
        </x-slot:actions>
    </x-ui.page-header>

    <flux:card class="space-y-4 !border-zinc-300 !shadow-md dark:!border-zinc-700">
        <x-ui.toolbar>
            <x-slot:search>
                <flux:input wire:model.live.debounce.300ms="cari" icon="magnifying-glass" :placeholder="__('Cari merk, kode NUP, atau no. seri...')" />
            </x-slot:search>

            <flux:select wire:model.live="gedung" :placeholder="__('Semua gedung')" class="max-w-48">
                @foreach ($gedungOptions as $g)
                    <flux:select.option :value="$g->id">{{ $g->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="jenis" :placeholder="__('Semua jenis')" class="max-w-48">
                @foreach ($jenisOptions as $j)
                    <flux:select.option :value="$j->id">{{ $j->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="kondisi" :placeholder="__('Semua kondisi')" class="max-w-48">
                @foreach ($kondisiOptions as $value => $label)
                    <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="perHalaman" class="max-w-28">
                <flux:select.option value="20">20</flux:select.option>
                <flux:select.option value="50">50</flux:select.option>
                <flux:select.option value="100">100</flux:select.option>
            </flux:select>
        </x-ui.toolbar>

        @if ($barangList->isEmpty())
            <x-ui.empty-state
                icon="cube"
                :heading="__('Tidak ditemukan')"
                :description="__('Coba kata kunci atau filter lain.')"
            />
        @else
            {{-- Kartu di HP, tabel di layar besar --}}
            <div class="hidden sm:block">
                <flux:table :paginate="$barangList">
                    <flux:table.columns>
                        <flux:table.column>{{ __('Barang') }}</flux:table.column>
                        <flux:table.column>{{ __('Jenis') }}</flux:table.column>
                        <flux:table.column>{{ __('Lokasi') }}</flux:table.column>
                        <flux:table.column>{{ __('Kode FT') }}</flux:table.column>
                        <flux:table.column align="end">{{ __('Kondisi') }}</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($barangList as $barang)
                            <flux:table.row :key="$barang->id">
                                <flux:table.cell variant="strong">
                                    <flux:link :accent="false" class="inline-flex items-center gap-2 !text-blue-600 dark:!text-blue-400" :href="route('inventaris.show', $barang->public_token)" wire:navigate>
                                        <flux:icon name="cube" class="size-4 shrink-0 text-zinc-400 dark:text-zinc-500" />
                                        {{ $barang->brand }}
                                    </flux:link>
                                </flux:table.cell>
                                <flux:table.cell>{{ $barang->itemType->name }}</flux:table.cell>
                                <flux:table.cell>
                                    <div class="text-sm">
                                        <div>{{ $barang->subbuilding->name }}</div>
                                        <div class="text-xs text-zinc-400 dark:text-zinc-500">{{ $barang->subbuilding->building->name }}</div>
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell class="tabular-nums">{{ $barang->faculty_code ?: '—' }}</flux:table.cell>
                                <flux:table.cell align="end">
                                    <x-ui.status-badge :kondisi="$barang->currentCondition?->condition_code" size="sm" />
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>

            <div class="grid grid-cols-2 gap-3 sm:hidden">
                @foreach ($barangList as $barang)
                    <a href="{{ route('inventaris.show', $barang->public_token) }}" wire:navigate wire:key="card-{{ $barang->id }}" class="block overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                        <div class="aspect-square bg-zinc-100 dark:bg-zinc-800">
                            @if ($barang->images->isNotEmpty())
                                <img src="{{ $barang->images->first()->url() }}" class="size-full object-cover" alt="{{ $barang->brand }}" />
                            @else
                                <div class="flex size-full items-center justify-center">
                                    <flux:icon name="cube" class="size-8 text-zinc-300 dark:text-zinc-600" />
                                </div>
                            @endif
                        </div>
                        <div class="space-y-1 p-2">
                            <div class="truncate text-sm font-medium">{{ $barang->brand }}</div>
                            <div class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $barang->subbuilding->name }}</div>
                            <x-ui.status-badge :kondisi="$barang->currentCondition?->condition_code" size="sm" />
                        </div>
                    </a>
                @endforeach

                <div class="col-span-2">
                    <flux:pagination :paginator="$barangList" class="pt-2" />
                </div>
            </div>
        @endif
    </flux:card>
</div>
