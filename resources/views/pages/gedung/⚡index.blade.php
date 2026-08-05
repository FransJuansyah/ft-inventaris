<?php

use App\Models\Building;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Gedung')] class extends Component {
    use WithPagination;

    #[Url]
    public string $cari = '';

    #[Url]
    public string $status = 'aktif';

    public ?int $editId = null;

    public string $nama_gedung = '';

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
        $this->reset('editId', 'nama_gedung');
        $this->resetValidation();
        Flux::modal('gedung-form')->show();
    }

    public function edit(int $id): void
    {
        $gedung = Building::findOrFail($id);

        $this->editId = $gedung->id;
        $this->nama_gedung = $gedung->name;
        $this->resetValidation();

        Flux::modal('gedung-form')->show();
    }

    public function simpan(): void
    {
        $validated = $this->validate([
            'nama_gedung' => [
                'required',
                'string',
                'max:150',
                Rule::unique('buildings', 'normalized_name')
                    ->using(fn ($query) => $query->where('normalized_name', Building::normalize($this->nama_gedung)))
                    ->ignore($this->editId),
            ],
        ]);

        Building::updateOrCreate(['id' => $this->editId], ['name' => $validated['nama_gedung']]);

        Flux::modal('gedung-form')->close();
        Flux::toast(variant: 'success', text: __('Gedung berhasil disimpan.'));

        $this->reset('editId', 'nama_gedung');
    }

    public function konfirmasiArsip(int $id): void
    {
        $this->archiveId = $id;
        Flux::modal('gedung-arsip')->show();
    }

    public function arsipkan(): void
    {
        $gedung = Building::findOrFail($this->archiveId);

        try {
            $gedung->archive(auth()->user());
            Flux::toast(variant: 'success', text: __('Gedung berhasil diarsipkan.'));
        } catch (\DomainException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());
        }

        Flux::modal('gedung-arsip')->close();
        $this->archiveId = null;
    }

    public function pulihkan(int $id): void
    {
        Building::findOrFail($id)->restore();
        Flux::toast(variant: 'success', text: __('Gedung berhasil dipulihkan.'));
    }

    public function with(): array
    {
        return [
            'gedungList' => Building::query()
                ->withCount(['subbuildings' => fn ($q) => $q->whereNull('archived_at')])
                ->when($this->cari, fn ($query) => $query->where('name', 'like', "%{$this->cari}%"))
                ->when($this->status === 'aktif', fn ($query) => $query->whereNull('archived_at'))
                ->when($this->status === 'arsip', fn ($query) => $query->whereNotNull('archived_at'))
                ->orderBy('name')
                ->paginate(10),
        ];
    }
}; ?>

<div class="space-y-6">
    <x-ui.page-header :title="__('Gedung')" :subtitle="__('Daftar gedung Fakultas Teknik')">
        <x-slot:actions>
            <flux:button variant="primary" icon="plus" wire:click="tambah">
                {{ __('Tambah Gedung') }}
            </flux:button>
        </x-slot:actions>
    </x-ui.page-header>

    <flux:card class="space-y-4 !border-zinc-300 !shadow-md dark:!border-zinc-700">
        <x-ui.toolbar>
            <x-slot:search>
                <flux:input wire:model.live.debounce.300ms="cari" icon="magnifying-glass" :placeholder="__('Cari nama gedung...')" />
            </x-slot:search>

            <flux:select wire:model.live="status">
                <flux:select.option value="aktif">{{ __('Aktif') }}</flux:select.option>
                <flux:select.option value="arsip">{{ __('Arsip') }}</flux:select.option>
                <flux:select.option value="semua">{{ __('Semua') }}</flux:select.option>
            </flux:select>
        </x-ui.toolbar>

        @if ($gedungList->isEmpty())
            <x-ui.empty-state
                icon="building-office-2"
                :heading="$cari ? __('Tidak ditemukan') : __('Belum ada gedung')"
                :description="$cari ? __('Coba kata kunci lain atau hapus pencarian.') : __('Tambahkan gedung pertama untuk mulai mendata lokasi barang.')"
            >
                @unless ($cari)
                    <flux:button variant="primary" icon="plus" wire:click="tambah">
                        {{ __('Tambah Gedung') }}
                    </flux:button>
                @endunless
            </x-ui.empty-state>
        @else
            {{-- Desktop: tabel. HP: tumpukan kartu (list responsif). --}}
            <div class="hidden sm:block">
                <flux:table :paginate="$gedungList">
                    <flux:table.columns>
                        <flux:table.column>{{ __('Nama Gedung') }}</flux:table.column>
                        <flux:table.column align="end">{{ __('Jumlah Subgedung Aktif') }}</flux:table.column>
                        <flux:table.column>{{ __('Status') }}</flux:table.column>
                        <flux:table.column align="end">{{ __('Aksi') }}</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($gedungList as $gedung)
                            <flux:table.row :key="$gedung->id">
                                <flux:table.cell variant="strong">
                                    <flux:link :accent="false" class="inline-flex items-center gap-2 !text-blue-600 dark:!text-blue-400" :href="route('subgedung.index', $gedung)" wire:navigate>
                                        <flux:icon name="building-office-2" class="size-4 shrink-0 text-zinc-400 dark:text-zinc-500" />
                                        {{ $gedung->name }}
                                    </flux:link>
                                </flux:table.cell>
                                <flux:table.cell align="end">
                                    <flux:badge size="sm" color="zinc">{{ $gedung->subbuildings_count }} {{ __('subgedung') }}</flux:badge>
                                </flux:table.cell>
                                <flux:table.cell>
                                    @if ($gedung->archived_at)
                                        <flux:badge size="sm" color="zinc">{{ __('Arsip') }}</flux:badge>
                                    @else
                                        <flux:badge size="sm" color="green">{{ __('Aktif') }}</flux:badge>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell align="end">
                                    <div class="flex justify-end gap-1">
                                        @if ($gedung->archived_at)
                                            <flux:button size="sm" variant="ghost" icon="arrow-uturn-left" wire:click="pulihkan({{ $gedung->id }})">{{ __('Pulihkan') }}</flux:button>
                                        @else
                                            <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="edit({{ $gedung->id }})" :aria-label="__('Ubah')" />
                                            <flux:button size="sm" variant="ghost" icon="archive-box" wire:click="konfirmasiArsip({{ $gedung->id }})" :aria-label="__('Arsipkan')" />
                                        @endif
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>

            <div class="space-y-2 sm:hidden">
                @foreach ($gedungList as $gedung)
                    <x-ui.list-card
                        wire:key="card-{{ $gedung->id }}"
                        :href="route('subgedung.index', $gedung)"
                        :title="$gedung->name"
                        icon="building-office-2"
                    >
                        <x-slot:meta>{{ $gedung->subbuildings_count }} {{ __('subgedung') }} · {{ $gedung->archived_at ? __('Arsip') : __('Aktif') }}</x-slot:meta>
                        <x-slot:trailing>
                            @if ($gedung->archived_at)
                                <flux:button size="sm" variant="ghost" icon="arrow-uturn-left" wire:click="pulihkan({{ $gedung->id }})" :aria-label="__('Pulihkan')" />
                            @else
                                <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="edit({{ $gedung->id }})" :aria-label="__('Ubah')" />
                                <flux:button size="sm" variant="ghost" icon="archive-box" wire:click="konfirmasiArsip({{ $gedung->id }})" :aria-label="__('Arsipkan')" />
                            @endif
                        </x-slot:trailing>
                    </x-ui.list-card>
                @endforeach

                <flux:pagination :paginator="$gedungList" class="pt-2" />
            </div>
        @endif
    </flux:card>

    <flux:modal name="gedung-form" class="w-full max-w-md" @close="$set('editId', null)">
        <form wire:submit="simpan" class="space-y-6">
            <flux:heading size="lg">
                {{ $editId ? __('Ubah Gedung') : __('Tambah Gedung') }}
            </flux:heading>

            <flux:input wire:model="nama_gedung" :label="__('Nama Gedung')" :placeholder="__('mis. Gedung Dekanat')" autofocus />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Batal') }}</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary">{{ __('Simpan') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="gedung-arsip" class="w-full max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Arsipkan gedung ini?') }}</flux:heading>
                <flux:subheading>
                    {{ __('Gedung yang masih punya subgedung aktif tidak bisa diarsipkan. Data tidak dihapus dan bisa dipulihkan kapan saja.') }}
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
