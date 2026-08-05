<?php

use App\Models\Building;
use App\Models\Subbuilding;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Ruangan')] class extends Component {
    use WithPagination;

    public Building $gedung;

    #[Url]
    public string $cari = '';

    #[Url]
    public string $status = 'aktif';

    public ?int $editId = null;

    public string $nama_subgedung = '';

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
        $this->reset('editId', 'nama_subgedung');
        $this->resetValidation();
        Flux::modal('subgedung-form')->show();
    }

    public function edit(int $id): void
    {
        $subgedung = $this->gedung->subbuildings()->findOrFail($id);

        $this->editId = $subgedung->id;
        $this->nama_subgedung = $subgedung->name;
        $this->resetValidation();

        Flux::modal('subgedung-form')->show();
    }

    public function simpan(): void
    {
        $validated = $this->validate([
            'nama_subgedung' => [
                'required',
                'string',
                'max:255',
                Rule::unique('subbuildings', 'normalized_name')
                    ->where('building_id', $this->gedung->id)
                    ->using(fn ($query) => $query->where('normalized_name', Building::normalize($this->nama_subgedung)))
                    ->ignore($this->editId),
            ],
        ]);

        $this->gedung->subbuildings()->updateOrCreate(['id' => $this->editId], ['name' => $validated['nama_subgedung']]);

        Flux::modal('subgedung-form')->close();
        Flux::toast(variant: 'success', text: __('Ruangan berhasil disimpan.'));

        $this->reset('editId', 'nama_subgedung');
    }

    public function konfirmasiArsip(int $id): void
    {
        $this->archiveId = $id;
        Flux::modal('subgedung-arsip')->show();
    }

    public function arsipkan(): void
    {
        $subgedung = $this->gedung->subbuildings()->findOrFail($this->archiveId);

        try {
            $subgedung->archive(auth()->user());
            Flux::toast(variant: 'success', text: __('Ruangan berhasil diarsipkan.'));
        } catch (\DomainException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());
        }

        Flux::modal('subgedung-arsip')->close();
        $this->archiveId = null;
    }

    public function pulihkan(int $id): void
    {
        try {
            $this->gedung->subbuildings()->findOrFail($id)->restore();
            Flux::toast(variant: 'success', text: __('Ruangan berhasil dipulihkan.'));
        } catch (\DomainException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());
        }
    }

    public function with(): array
    {
        return [
            'subgedungList' => $this->gedung->subbuildings()
                ->when($this->cari, fn ($query) => $query->where('name', 'like', "%{$this->cari}%"))
                ->when($this->status === 'aktif', fn ($query) => $query->whereNull('archived_at'))
                ->when($this->status === 'arsip', fn ($query) => $query->whereNotNull('archived_at'))
                ->orderBy('name')
                ->paginate(10),
        ];
    }
}; ?>

<div class="space-y-6">
    <x-ui.page-header :title="$gedung->name" :subtitle="__('Daftar ruangan di gedung ini')">
        <x-slot:breadcrumbs>
            <flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('gedung.index')" wire:navigate>{{ __('Gedung') }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ $gedung->name }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <flux:button variant="primary" icon="plus" wire:click="tambah">
                {{ __('Tambah Ruangan') }}
            </flux:button>
        </x-slot:actions>
    </x-ui.page-header>

    <flux:card class="space-y-4 !border-zinc-300 !shadow-md dark:!border-zinc-700">
        <x-ui.toolbar>
            <x-slot:search>
                <flux:input wire:model.live.debounce.300ms="cari" icon="magnifying-glass" :placeholder="__('Cari nama ruangan...')" />
            </x-slot:search>

            <flux:select wire:model.live="status">
                <flux:select.option value="aktif">{{ __('Aktif') }}</flux:select.option>
                <flux:select.option value="arsip">{{ __('Arsip') }}</flux:select.option>
                <flux:select.option value="semua">{{ __('Semua') }}</flux:select.option>
            </flux:select>
        </x-ui.toolbar>

        @if ($subgedungList->isEmpty())
            <x-ui.empty-state
                icon="map-pin"
                :heading="$cari ? __('Tidak ditemukan') : __('Belum ada ruangan')"
                :description="$cari ? __('Coba kata kunci lain atau hapus pencarian.') : __('Tambahkan ruangan pertama di gedung ini untuk mulai mendata barang.')"
            >
                @unless ($cari)
                    <flux:button variant="primary" icon="plus" wire:click="tambah">
                        {{ __('Tambah Ruangan') }}
                    </flux:button>
                @endunless
            </x-ui.empty-state>
        @else
            <div class="hidden sm:block">
                <flux:table :paginate="$subgedungList">
                    <flux:table.columns>
                        <flux:table.column>{{ __('Nama Ruangan') }}</flux:table.column>
                        <flux:table.column>{{ __('Status') }}</flux:table.column>
                        <flux:table.column align="end">{{ __('Aksi') }}</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($subgedungList as $subgedung)
                            <flux:table.row :key="$subgedung->id">
                                <flux:table.cell variant="strong">
                                    <span class="inline-flex items-center gap-2">
                                        <flux:icon name="map-pin" class="size-4 shrink-0 text-zinc-400 dark:text-zinc-500" />
                                        {{ $subgedung->name }}
                                    </span>
                                </flux:table.cell>
                                <flux:table.cell>
                                    @if ($subgedung->archived_at)
                                        <flux:badge size="sm" color="zinc">{{ __('Arsip') }}</flux:badge>
                                    @else
                                        <flux:badge size="sm" color="green">{{ __('Aktif') }}</flux:badge>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell align="end">
                                    <div class="flex justify-end gap-1">
                                        @if ($subgedung->archived_at)
                                            <flux:button size="sm" variant="ghost" icon="arrow-uturn-left" wire:click="pulihkan({{ $subgedung->id }})">{{ __('Pulihkan') }}</flux:button>
                                        @else
                                            <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="edit({{ $subgedung->id }})" :aria-label="__('Ubah')" />
                                            <flux:button size="sm" variant="ghost" icon="archive-box" wire:click="konfirmasiArsip({{ $subgedung->id }})" :aria-label="__('Arsipkan')" />
                                        @endif
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>

            <div class="space-y-2 sm:hidden">
                @foreach ($subgedungList as $subgedung)
                    <x-ui.list-card wire:key="card-{{ $subgedung->id }}" :title="$subgedung->name" icon="map-pin">
                        <x-slot:meta>{{ $subgedung->archived_at ? __('Arsip') : __('Aktif') }}</x-slot:meta>
                        <x-slot:trailing>
                            @if ($subgedung->archived_at)
                                <flux:button size="sm" variant="ghost" icon="arrow-uturn-left" wire:click="pulihkan({{ $subgedung->id }})" :aria-label="__('Pulihkan')" />
                            @else
                                <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="edit({{ $subgedung->id }})" :aria-label="__('Ubah')" />
                                <flux:button size="sm" variant="ghost" icon="archive-box" wire:click="konfirmasiArsip({{ $subgedung->id }})" :aria-label="__('Arsipkan')" />
                            @endif
                        </x-slot:trailing>
                    </x-ui.list-card>
                @endforeach

                <flux:pagination :paginator="$subgedungList" class="pt-2" />
            </div>
        @endif
    </flux:card>

    <flux:modal name="subgedung-form" class="w-full max-w-md" @close="$set('editId', null)">
        <form wire:submit="simpan" class="space-y-6">
            <flux:heading size="lg">
                {{ $editId ? __('Ubah Ruangan') : __('Tambah Ruangan') }}
            </flux:heading>

            <flux:input wire:model="nama_subgedung" :label="__('Nama Ruangan')" :placeholder="__('mis. Ruang 201')" autofocus />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Batal') }}</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary">{{ __('Simpan') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="subgedung-arsip" class="w-full max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Arsipkan ruangan ini?') }}</flux:heading>
                <flux:subheading>
                    {{ __('Ruangan yang masih punya barang aktif tidak bisa diarsipkan. Data tidak dihapus dan bisa dipulihkan kapan saja.') }}
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
