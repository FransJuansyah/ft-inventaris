<?php

use App\Models\Building;
use App\Models\InventoryItem;
use App\Models\ItemType;
use App\Models\Subbuilding;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Form Barang')] class extends Component {
    use WithFileUploads;

    public ?InventoryItem $barang = null;

    public ?int $formGedung = null;

    #[Validate('required|exists:subbuildings,id')]
    public ?int $id_subgedung = null;

    #[Validate('required|exists:item_types,id')]
    public ?int $id_jenis_barang = null;

    #[Validate('required|string|max:255')]
    public string $merk = '';

    #[Validate('nullable|string')]
    public string $spesifikasi = '';

    #[Validate('nullable|string|max:255')]
    public string $asal_barang = '';

    #[Validate('nullable|date')]
    public string $tgl_masuk = '';

    #[Validate('nullable|string|max:255')]
    public string $kode_nup = '';

    #[Validate('nullable|string|max:255')]
    public string $no_seri_ft = '';

    #[Validate('nullable|string|max:255')]
    public string $no_seri_barang = '';

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    #[Validate(['fotoBaru.*' => 'image|max:5120'])]
    public array $fotoBaru = [];

    public function mount(?InventoryItem $barang = null): void
    {
        if ($barang?->exists) {
            $this->barang = $barang->load('images');
            $this->id_subgedung = $barang->subbuilding_id;
            $this->formGedung = $barang->subbuilding->building_id;
            $this->id_jenis_barang = $barang->item_type_id;
            $this->merk = $barang->brand;
            $this->spesifikasi = (string) $barang->specification;
            $this->asal_barang = (string) $barang->acquisition_source;
            $this->tgl_masuk = $barang->entry_date?->format('Y-m-d') ?? '';
            $this->kode_nup = (string) $barang->nup_code;
            $this->no_seri_ft = (string) $barang->faculty_code;
            $this->no_seri_barang = (string) $barang->serial_number;
        }
    }

    #[Computed]
    public function subgedungOptions()
    {
        if (! $this->formGedung) {
            return collect();
        }

        return Subbuilding::where('building_id', $this->formGedung)->whereNull('archived_at')->orderBy('name')->get();
    }

    public function updatedFormGedung(): void
    {
        $this->id_subgedung = null;
    }

    public function hapusFoto(int $imageId): void
    {
        if (! $this->barang) {
            return;
        }

        $image = $this->barang->images()->findOrFail($imageId);
        Storage::disk($image->storage_disk)->delete($image->path);
        $image->delete();

        $this->barang->load('images');
    }

    public function simpan(): void
    {
        $rules = [
            'id_subgedung' => ['required', 'exists:subbuildings,id'],
            'id_jenis_barang' => ['required', 'exists:item_types,id'],
            'merk' => ['required', 'string', 'max:255'],
            'spesifikasi' => ['nullable', 'string'],
            'asal_barang' => ['nullable', 'string', 'max:255'],
            'tgl_masuk' => ['nullable', 'date'],
            'kode_nup' => ['nullable', 'string', 'max:255'],
            'no_seri_ft' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('inventory_items', 'faculty_code_normalized')
                    ->using(fn ($query) => $query->where('faculty_code_normalized', InventoryItem::normalizeCode($this->no_seri_ft)))
                    ->ignore($this->barang?->id),
            ],
            'no_seri_barang' => ['nullable', 'string', 'max:255'],
            'fotoBaru.*' => ['image', 'max:5120'],
        ];

        $validated = $this->validate($rules);
        unset($validated['fotoBaru']);

        $existingCount = $this->barang ? $this->barang->images()->count() : 0;

        if ($existingCount + count($this->fotoBaru) > 5) {
            Flux::toast(variant: 'danger', text: __('Maksimal 5 foto per barang.'));

            return;
        }

        $attributes = [
            'subbuilding_id' => $validated['id_subgedung'],
            'item_type_id' => $validated['id_jenis_barang'],
            'brand' => $validated['merk'],
            'specification' => $validated['spesifikasi'] ?: null,
            'acquisition_source' => $validated['asal_barang'] ?: null,
            'entry_date' => $validated['tgl_masuk'] ?: null,
            'nup_code' => $validated['kode_nup'] ?: null,
            // TODO: peringatan duplikasi NUP/nomor seri belum diimplementasi
            'faculty_code' => $validated['no_seri_ft'] ?: null,
            'serial_number' => $validated['no_seri_barang'] ?: null,
        ];

        if ($this->barang) {
            $this->barang->update($attributes + ['updated_by' => Auth::id()]);
            $item = $this->barang;
        } else {
            $item = InventoryItem::buatDenganKondisiAwal($attributes, Auth::user());
        }

        $nextSortOrder = $item->images()->max('sort_order') + 1;

        foreach ($this->fotoBaru as $file) {
            $path = $file->store('barang', 'public');

            $item->images()->create([
                'storage_disk' => 'public',
                'path' => $path,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
                'sort_order' => $nextSortOrder++,
                'uploaded_by' => Auth::id(),
            ]);
        }

        $this->redirect(route('barang.show.internal', $item), navigate: true);
    }

    public function batal(): void
    {
        $this->redirect($this->barang ? route('barang.show.internal', $this->barang) : route('barang.index'), navigate: true);
    }
}; ?>

<div class="space-y-6">
    <x-ui.page-header
        :title="$barang ? __('Ubah Barang') : __('Tambah Barang')"
        :subtitle="$barang ? $barang->brand : __('Lengkapi data barang baru')"
    />

    <form wire:submit="simpan">
        <flux:card class="!border-zinc-300 !shadow-md dark:!border-zinc-700">
            <x-ui.form-section :heading="__('Lokasi')" :description="__('Tempat barang ini berada.')">
                <flux:select wire:model.live="formGedung" :label="__('Gedung')" :placeholder="__('Pilih gedung')">
                    @foreach (Building::whereNull('archived_at')->orderBy('name')->get() as $g)
                        <flux:select.option :value="$g->id">{{ $g->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="id_subgedung" :label="__('Ruangan')" :placeholder="__('Pilih ruangan')" :disabled="! $formGedung">
                    @foreach ($this->subgedungOptions as $s)
                        <flux:select.option :value="$s->id">{{ $s->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </x-ui.form-section>

            <x-ui.form-section :heading="__('Identitas')" :description="__('Data pokok barang.')">
                <flux:select wire:model="id_jenis_barang" :label="__('Jenis Barang')" :placeholder="__('Pilih jenis barang')">
                    @foreach (ItemType::whereNull('archived_at')->orderBy('name')->get() as $j)
                        <flux:select.option :value="$j->id">{{ $j->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="merk" :label="__('Merk')" :placeholder="__('mis. Epson EB-X51')" />
                <flux:textarea wire:model="spesifikasi" :label="__('Spesifikasi')" rows="3" />
                <flux:input wire:model="asal_barang" :label="__('Asal Barang')" :placeholder="__('mis. APBN, Hibah')" />
                <flux:input wire:model="tgl_masuk" :label="__('Tanggal Masuk')" type="date" />
            </x-ui.form-section>

            <x-ui.form-section :heading="__('Nomor Seri')" :description="__('Kode identifikasi barang. Tiap field independen — jangan disamakan.')">
                <flux:input wire:model="kode_nup" :label="__('Kode NUP')" />
                <flux:input wire:model="no_seri_ft" :label="__('Kode FT')" />
                <flux:input wire:model="no_seri_barang" :label="__('No. Seri Barang')" />
            </x-ui.form-section>

            <x-ui.form-section :heading="__('Foto')" :description="__('Maksimal 5 foto.')">
                @if ($barang && $barang->images->isNotEmpty())
                    <div class="grid grid-cols-3 gap-2 sm:grid-cols-4">
                        @foreach ($barang->images as $image)
                            <div class="group relative aspect-square overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                                <img src="{{ $image->url() }}" class="size-full object-cover" alt="" />
                                <button
                                    type="button"
                                    wire:click="hapusFoto({{ $image->id }})"
                                    class="absolute top-1 end-1 rounded-full bg-black/60 p-1 text-white opacity-0 transition group-hover:opacity-100"
                                >
                                    <flux:icon name="x-mark" class="size-3.5" />
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif

                <flux:input type="file" wire:model="fotoBaru" multiple accept="image/*" :label="__('Tambah foto baru')" />

                @if ($fotoBaru)
                    <div class="grid grid-cols-3 gap-2 sm:grid-cols-4">
                        @foreach ($fotoBaru as $file)
                            <div class="aspect-square overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                                <img src="{{ $file->temporaryUrl() }}" class="size-full object-cover" alt="" />
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-ui.form-section>

            <div class="flex justify-end gap-2 border-t border-zinc-200 pt-6 dark:border-zinc-700">
                <flux:button variant="ghost" wire:click="batal">{{ __('Batal') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('Simpan') }}</flux:button>
            </div>
        </flux:card>
    </form>
</div>
