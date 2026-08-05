<?php

use App\Models\Building;
use App\Models\InventoryItem;
use App\Models\ItemType;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Barang')] class extends Component {
    use WithPagination;

    #[Url]
    public string $cari = '';

    #[Url]
    public ?int $gedung = null;

    #[Url]
    public ?int $jenis = null;

    #[Url]
    public string $urut = 'terbaru';

    #[Url]
    public string $arah = 'desc';

    #[Url]
    public string $status = 'aktif';

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

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function gantiUrut(string $kolom): void
    {
        if ($this->urut === $kolom) {
            $this->arah = $this->arah === 'asc' ? 'desc' : 'asc';
        } else {
            $this->urut = $kolom;
            $this->arah = 'asc';
        }

        $this->resetPage();
    }

    public function with(): array
    {
        return [
            'barangList' => InventoryItem::query()
                ->with(['subbuilding.building', 'itemType', 'currentCondition'])
                ->filter($this->cari, $this->gedung, $this->jenis, $this->urut, $this->arah)
                ->when($this->status === 'aktif', fn ($q) => $q->whereNull('archived_at'))
                ->when($this->status === 'arsip', fn ($q) => $q->whereNotNull('archived_at'))
                ->paginate(10),
            'gedungOptions' => Building::orderBy('name')->get(),
            'jenisOptions' => ItemType::orderBy('name')->get(),
        ];
    }
}; ?>

<div class="space-y-6">
    <x-ui.page-header :title="__('Barang')" :subtitle="__('Daftar barang inventaris Fakultas Teknik')">
        <x-slot:actions>
            @if (auth()->user()->isAdmin())
                {{--
                    Query string diteruskan apa adanya ke rute export, jadi
                    Excel/label yang dihasilkan menghormati filter yang sedang
                    aktif di layar — bukan selalu semua data.
                --}}
                <flux:dropdown>
                    <flux:button icon="arrow-down-tray" icon-trailing="chevron-down">
                        {{ __('Export') }}
                    </flux:button>

                    <flux:menu>
                        <flux:menu.item :href="route('barang.export.excel', request()->query())" icon="table-cells">
                            {{ __('Excel') }}
                        </flux:menu.item>
                        <flux:menu.item :href="route('barang.export.label', request()->query())" icon="qr-code" target="_blank">
                            {{ __('Cetak Label QR') }}
                        </flux:menu.item>
                    </flux:menu>
                </flux:dropdown>

                <flux:button :href="route('barang.create')" wire:navigate variant="primary" icon="plus">
                    {{ __('Tambah Barang') }}
                </flux:button>
            @endif
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

            <flux:select wire:model.live="status">
                <flux:select.option value="aktif">{{ __('Aktif') }}</flux:select.option>
                <flux:select.option value="arsip">{{ __('Arsip') }}</flux:select.option>
                <flux:select.option value="semua">{{ __('Semua') }}</flux:select.option>
            </flux:select>
        </x-ui.toolbar>

        @if ($barangList->isEmpty())
            <x-ui.empty-state
                icon="cube"
                :heading="($cari || $gedung || $jenis) ? __('Tidak ditemukan') : __('Belum ada barang')"
                :description="($cari || $gedung || $jenis) ? __('Coba kata kunci atau filter lain.') : __('Tambahkan barang pertama untuk mulai mendata inventaris.')"
            >
                @if (! $cari && ! $gedung && ! $jenis)
                    <flux:button :href="route('barang.create')" wire:navigate variant="primary" icon="plus">
                        {{ __('Tambah Barang') }}
                    </flux:button>
                @endif
            </x-ui.empty-state>
        @else
            <div class="hidden sm:block">
                <flux:table :paginate="$barangList">
                    <flux:table.columns>
                        <flux:table.column sortable :sorted="$urut === 'nama'" :direction="$arah" wire:click="gantiUrut('nama')">{{ __('Nama') }}</flux:table.column>
                        <flux:table.column sortable :sorted="$urut === 'kode_nup'" :direction="$arah" wire:click="gantiUrut('kode_nup')">{{ __('Kode NUP') }}</flux:table.column>
                        <flux:table.column>{{ __('Lokasi') }}</flux:table.column>
                        <flux:table.column>{{ __('Status') }}</flux:table.column>
                        <flux:table.column align="end">{{ __('Kondisi') }}</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($barangList as $barang)
                            <flux:table.row :key="$barang->id">
                                <flux:table.cell variant="strong">
                                    <flux:link :accent="false" class="inline-flex items-center gap-2 !text-blue-600 dark:!text-blue-400" :href="route('barang.show.internal', $barang)" wire:navigate>
                                        <flux:icon name="cube" class="size-4 shrink-0 text-zinc-400 dark:text-zinc-500" />
                                        {{ $barang->brand }}
                                    </flux:link>
                                </flux:table.cell>
                                <flux:table.cell class="tabular-nums">{{ $barang->nup_code ?: '—' }}</flux:table.cell>
                                <flux:table.cell>
                                    <div class="text-sm">
                                        <div>{{ $barang->subbuilding->name }}</div>
                                        <div class="text-xs text-zinc-400 dark:text-zinc-500">{{ $barang->subbuilding->building->name }}</div>
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell>
                                    @if ($barang->archived_at)
                                        <flux:badge size="sm" color="zinc">{{ __('Arsip') }}</flux:badge>
                                    @else
                                        <flux:badge size="sm" color="green">{{ __('Aktif') }}</flux:badge>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell align="end">
                                    <x-ui.status-badge :kondisi="$barang->currentCondition?->condition_code" size="sm" />
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>

            <div class="space-y-2 sm:hidden">
                @foreach ($barangList as $barang)
                    <x-ui.list-card
                        wire:key="card-{{ $barang->id }}"
                        :href="route('barang.show.internal', $barang)"
                        :title="$barang->brand"
                        icon="cube"
                    >
                        <x-slot:meta>
                            {{ $barang->subbuilding->name }} · {{ $barang->subbuilding->building->name }}
                            @if ($barang->nup_code)
                                <span class="tabular-nums">· {{ $barang->nup_code }}</span>
                            @endif
                            @if ($barang->archived_at)
                                · {{ __('Arsip') }}
                            @endif
                        </x-slot:meta>
                        <x-slot:trailing>
                            <x-ui.status-badge :kondisi="$barang->currentCondition?->condition_code" size="sm" />
                        </x-slot:trailing>
                    </x-ui.list-card>
                @endforeach

                <flux:pagination :paginator="$barangList" class="pt-2" />
            </div>
        @endif
    </flux:card>
</div>
