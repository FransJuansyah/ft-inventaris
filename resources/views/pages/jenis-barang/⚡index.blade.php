<?php

use App\Models\ItemType;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Jenis Barang')] class extends Component {
    use WithPagination;

    #[Url]
    public string $cari = '';

    #[Url]
    public string $status = 'aktif';

    public ?int $editId = null;

    public string $nama_jenis = '';

    public ?int $archiveId = null;

    public function updatingCari(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function tambah(): void
    {
        $this->reset('editId', 'nama_jenis');
        $this->resetValidation();
        Flux::modal('jenis-barang-form')->show();
    }

    public function edit(int $id): void
    {
        $jenisBarang = ItemType::findOrFail($id);

        $this->editId = $jenisBarang->id;
        $this->nama_jenis = $jenisBarang->name;
        $this->resetValidation();

        Flux::modal('jenis-barang-form')->show();
    }

    public function simpan(): void
    {
        $validated = $this->validate([
            'nama_jenis' => [
                'required',
                'string',
                'max:255',
                Rule::unique('item_types', 'normalized_name')
                    ->using(fn ($query) => $query->where('normalized_name', \App\Models\Building::normalize($this->nama_jenis)))
                    ->ignore($this->editId),
            ],
        ]);

        ItemType::updateOrCreate(['id' => $this->editId], ['name' => $validated['nama_jenis']]);

        Flux::modal('jenis-barang-form')->close();
        Flux::toast(variant: 'success', text: __('Jenis barang berhasil disimpan.'));

        $this->reset('editId', 'nama_jenis');
    }

    public function konfirmasiArsip(int $id): void
    {
        $this->archiveId = $id;
        Flux::modal('jenis-barang-arsip')->show();
    }

    public function arsipkan(): void
    {
        $jenisBarang = ItemType::findOrFail($this->archiveId);

        try {
            $jenisBarang->archive(auth()->user());
            Flux::toast(variant: 'success', text: __('Jenis barang berhasil diarsipkan.'));
        } catch (\DomainException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());
        }

        Flux::modal('jenis-barang-arsip')->close();
        $this->archiveId = null;
    }

    public function pulihkan(int $id): void
    {
        ItemType::findOrFail($id)->restore();
        Flux::toast(variant: 'success', text: __('Jenis barang berhasil dipulihkan.'));
    }

    public function with(): array
    {
        return [
            'jenisBarangList' => ItemType::query()
                ->when($this->cari, fn ($query) => $query->where('name', 'like', "%{$this->cari}%"))
                ->when($this->status === 'aktif', fn ($query) => $query->whereNull('archived_at'))
                ->when($this->status === 'arsip', fn ($query) => $query->whereNotNull('archived_at'))
                ->orderBy('name')
                ->paginate(10),
        ];
    }
}; ?>

<div class="space-y-6">
    <x-ui.page-header :title="__('Jenis Barang')" :subtitle="__('Kategori/jenis barang inventaris')">
        <x-slot:actions>
            <flux:button variant="primary" icon="plus" wire:click="tambah">
                {{ __('Tambah Jenis Barang') }}
            </flux:button>
        </x-slot:actions>
    </x-ui.page-header>

    <flux:card class="space-y-4 !border-zinc-300 !shadow-md dark:!border-zinc-700">
        <x-ui.toolbar>
            <x-slot:search>
                <flux:input wire:model.live.debounce.300ms="cari" icon="magnifying-glass" :placeholder="__('Cari jenis barang...')" />
            </x-slot:search>

            <flux:select wire:model.live="status">
                <flux:select.option value="aktif">{{ __('Aktif') }}</flux:select.option>
                <flux:select.option value="arsip">{{ __('Arsip') }}</flux:select.option>
                <flux:select.option value="semua">{{ __('Semua') }}</flux:select.option>
            </flux:select>
        </x-ui.toolbar>

        @if ($jenisBarangList->isEmpty())
            <x-ui.empty-state
                icon="tag"
                :heading="$cari ? __('Tidak ditemukan') : __('Belum ada jenis barang')"
                :description="$cari ? __('Coba kata kunci lain atau hapus pencarian.') : __('Tambahkan jenis barang pertama, mis. Elektronik atau Furnitur.')"
            >
                @unless ($cari)
                    <flux:button variant="primary" icon="plus" wire:click="tambah">
                        {{ __('Tambah Jenis Barang') }}
                    </flux:button>
                @endunless
            </x-ui.empty-state>
        @else
            <div class="hidden sm:block">
                <flux:table :paginate="$jenisBarangList">
                    <flux:table.columns>
                        <flux:table.column>{{ __('Nama Jenis') }}</flux:table.column>
                        <flux:table.column>{{ __('Status') }}</flux:table.column>
                        <flux:table.column align="end">{{ __('Aksi') }}</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($jenisBarangList as $jenisBarang)
                            <flux:table.row :key="$jenisBarang->id">
                                <flux:table.cell variant="strong">
                                    <span class="inline-flex items-center gap-2">
                                        <flux:icon name="tag" class="size-4 shrink-0 text-zinc-400 dark:text-zinc-500" />
                                        {{ $jenisBarang->name }}
                                    </span>
                                </flux:table.cell>
                                <flux:table.cell>
                                    @if ($jenisBarang->archived_at)
                                        <flux:badge size="sm" color="zinc">{{ __('Arsip') }}</flux:badge>
                                    @else
                                        <flux:badge size="sm" color="green">{{ __('Aktif') }}</flux:badge>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell align="end">
                                    <div class="flex justify-end gap-1">
                                        @if ($jenisBarang->archived_at)
                                            <flux:button size="sm" variant="ghost" icon="arrow-uturn-left" wire:click="pulihkan({{ $jenisBarang->id }})">{{ __('Pulihkan') }}</flux:button>
                                        @else
                                            <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="edit({{ $jenisBarang->id }})" :aria-label="__('Ubah')" />
                                            <flux:button size="sm" variant="ghost" icon="archive-box" wire:click="konfirmasiArsip({{ $jenisBarang->id }})" :aria-label="__('Arsipkan')" />
                                        @endif
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>

            <div class="space-y-2 sm:hidden">
                @foreach ($jenisBarangList as $jenisBarang)
                    <x-ui.list-card wire:key="card-{{ $jenisBarang->id }}" :title="$jenisBarang->name" icon="tag">
                        <x-slot:meta>{{ $jenisBarang->archived_at ? __('Arsip') : __('Aktif') }}</x-slot:meta>
                        <x-slot:trailing>
                            @if ($jenisBarang->archived_at)
                                <flux:button size="sm" variant="ghost" icon="arrow-uturn-left" wire:click="pulihkan({{ $jenisBarang->id }})" :aria-label="__('Pulihkan')" />
                            @else
                                <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="edit({{ $jenisBarang->id }})" :aria-label="__('Ubah')" />
                                <flux:button size="sm" variant="ghost" icon="archive-box" wire:click="konfirmasiArsip({{ $jenisBarang->id }})" :aria-label="__('Arsipkan')" />
                            @endif
                        </x-slot:trailing>
                    </x-ui.list-card>
                @endforeach

                <flux:pagination :paginator="$jenisBarangList" class="pt-2" />
            </div>
        @endif
    </flux:card>

    <flux:modal name="jenis-barang-form" class="w-full max-w-md" @close="$set('editId', null)">
        <form wire:submit="simpan" class="space-y-6">
            <flux:heading size="lg">
                {{ $editId ? __('Ubah Jenis Barang') : __('Tambah Jenis Barang') }}
            </flux:heading>

            <flux:input wire:model="nama_jenis" :label="__('Nama Jenis')" :placeholder="__('mis. Elektronik')" autofocus />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Batal') }}</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary">{{ __('Simpan') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="jenis-barang-arsip" class="w-full max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Arsipkan jenis barang ini?') }}</flux:heading>
                <flux:subheading>
                    {{ __('Jenis barang yang masih dipakai barang aktif tidak bisa diarsipkan. Data tidak dihapus dan bisa dipulihkan kapan saja.') }}
                </flux:subheading>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Batal') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="arsipkan">{{ __('Arsipkan') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
