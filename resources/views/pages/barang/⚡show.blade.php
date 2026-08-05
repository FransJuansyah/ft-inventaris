<?php

use App\Enums\KondisiBarang;
use App\Models\InventoryItem;
use App\Models\MaintenanceRecord;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Number;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public InventoryItem $barang;

    #[Url]
    public string $tab = 'detail';

    // --- Form: update status ---
    #[Validate('required|integer')]
    public ?int $statusBaru = null;

    #[Validate('nullable|string')]
    public string $keteranganStatus = '';

    // --- Form: tambah riwayat perawatan ---
    #[Validate('required|string')]
    public string $deskripsiRiwayat = '';

    #[Validate('nullable|numeric|min:0')]
    public ?string $biayaRiwayat = null;

    #[Validate('required|date')]
    public string $tglInputRiwayat = '';

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    #[Validate(['fotoRiwayat.*' => 'image|max:5120'])]
    public array $fotoRiwayat = [];

    // --- Form: arsip ---
    #[Validate('required|string')]
    public string $alasanArsip = '';

    #[Title('Detail Barang')]
    public function mount(InventoryItem $barang): void
    {
        $this->barang = $barang->load([
            'subbuilding.building',
            'itemType',
            'createdBy',
            'currentCondition',
            'conditions',
            'maintenanceRecords.recordedBy',
            'maintenanceRecords.images',
            'images',
        ]);
    }

    public function bukaFormStatus(): void
    {
        $this->statusBaru = $this->barang->currentCondition?->condition_code->value;
        $this->keteranganStatus = '';
        $this->resetValidation();

        Flux::modal('update-status')->show();
    }

    public function simpanStatus(): void
    {
        $validated = $this->validate();

        try {
            $this->barang->tambahKondisi(
                kondisi: KondisiBarang::from($validated['statusBaru']),
                keterangan: $validated['keteranganStatus'] ?? '',
                pencatat: Auth::user(),
            );
        } catch (\DomainException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }

        $this->barang->load(['currentCondition', 'conditions']);

        Flux::modal('update-status')->close();
        Flux::toast(variant: 'success', text: __('Status barang berhasil diperbarui.'));

        $this->tab = 'status';
    }

    public function bukaFormRiwayat(): void
    {
        $this->reset('deskripsiRiwayat', 'biayaRiwayat', 'fotoRiwayat');
        $this->tglInputRiwayat = now()->format('Y-m-d');
        $this->resetValidation();

        Flux::modal('tambah-riwayat')->show();
    }

    public function simpanRiwayat(): void
    {
        $validated = $this->validate();

        if (empty($this->fotoRiwayat)) {
            $this->addError('fotoRiwayat', __('Minimal satu foto bukti wajib diunggah.'));

            return;
        }

        if (count($this->fotoRiwayat) > 5) {
            $this->addError('fotoRiwayat', __('Maksimal 5 foto bukti.'));

            return;
        }

        $record = MaintenanceRecord::create([
            'inventory_item_id' => $this->barang->id,
            'description' => $validated['deskripsiRiwayat'],
            'cost_amount' => $validated['biayaRiwayat'] ? (int) round((float) $validated['biayaRiwayat']) : 0,
            'recorded_by' => Auth::id(),
            'record_source' => 'user',
            'recorded_at' => $validated['tglInputRiwayat'],
        ]);

        $sortOrder = 0;
        foreach ($this->fotoRiwayat as $file) {
            $path = $file->store('riwayat', 'public');

            $record->images()->create([
                'storage_disk' => 'public',
                'path' => $path,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
                'sort_order' => $sortOrder++,
                'uploaded_by' => Auth::id(),
            ]);
        }

        $this->barang->load('maintenanceRecords.recordedBy', 'maintenanceRecords.images');

        Flux::modal('tambah-riwayat')->close();
        Flux::toast(variant: 'success', text: __('Riwayat perawatan berhasil dicatat.'));

        $this->tab = 'riwayat';
    }

    public function konfirmasiArsip(): void
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        $this->alasanArsip = '';
        $this->resetValidation();
        Flux::modal('barang-arsip')->show();
    }

    public function arsipkan(): void
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        $validated = $this->validate(['alasanArsip' => ['required', 'string']]);

        try {
            $this->barang->archive($validated['alasanArsip'], Auth::user());
            Flux::toast(variant: 'success', text: __('Barang berhasil diarsipkan.'));
        } catch (\DomainException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }

        Flux::modal('barang-arsip')->close();
        $this->barang->refresh();
    }

    public function pulihkan(): void
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        try {
            $this->barang->restore(Auth::user());
            Flux::toast(variant: 'success', text: __('Barang berhasil dipulihkan.'));
        } catch (\DomainException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());
        }

        $this->barang->refresh();
    }
}; ?>

<div class="space-y-6">
    <x-ui.page-header :title="$barang->brand" :subtitle="$barang->itemType->name">
        <x-slot:breadcrumbs>
            <flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('barang.index')" wire:navigate>{{ __('Barang') }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ $barang->brand }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </x-slot:breadcrumbs>

        <x-slot:actions>
            @if ($barang->archived_at)
                <flux:badge color="zinc" size="lg">{{ __('Arsip') }}</flux:badge>
                @if (auth()->user()->isAdmin())
                    <flux:button variant="primary" icon="arrow-uturn-left" wire:click="pulihkan">
                        {{ __('Pulihkan') }}
                    </flux:button>
                @endif
            @else
                <flux:button icon="wrench-screwdriver" wire:click="bukaFormRiwayat">
                    {{ __('Tambah Riwayat') }}
                </flux:button>

                <flux:button icon="arrow-path" wire:click="bukaFormStatus">
                    {{ __('Update Status') }}
                </flux:button>

                @if (auth()->user()->isAdmin())
                    <flux:button :href="route('barang.edit', $barang)" wire:navigate icon="pencil-square">
                        {{ __('Ubah') }}
                    </flux:button>

                    <flux:button variant="danger" icon="archive-box" wire:click="konfirmasiArsip">
                        {{ __('Arsipkan') }}
                    </flux:button>
                @endif
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <flux:card class="!border-zinc-300 !shadow-md dark:!border-zinc-700">
                <x-ui.photo-gallery :photos="$barang->imageUrls()" :alt="$barang->brand" />
            </flux:card>

            <flux:card class="!border-zinc-300 !shadow-md dark:!border-zinc-700">
                <x-ui.tabs
                    :tabs="[
                        'detail' => __('Detail'),
                        'status' => ['label' => __('Status'), 'badge' => $barang->conditions->count()],
                        'riwayat' => ['label' => __('Riwayat'), 'badge' => $barang->maintenanceRecords->count()],
                    ]"
                    :current="$tab"
                    property="tab"
                />

                <div class="pt-4">
                    @if ($tab === 'detail')
                        <x-ui.detail-list>
                            <x-ui.detail-item :label="__('Kondisi terkini')">
                                <x-ui.status-badge :kondisi="$barang->currentCondition?->condition_code" size="sm" />
                            </x-ui.detail-item>
                            <x-ui.detail-item :label="__('Jenis Barang')" :value="$barang->itemType->name" />
                            <x-ui.detail-item :label="__('Merk')" :value="$barang->brand" />
                            <x-ui.detail-item :label="__('Spesifikasi')" :value="$barang->specification" />
                            <x-ui.detail-item :label="__('Asal Barang')" :value="$barang->acquisition_source" />
                            <x-ui.detail-item :label="__('Tanggal Masuk')" :value="$barang->entry_date?->translatedFormat('d F Y')" />
                            <x-ui.detail-item :label="__('Lokasi')" :value="$barang->subbuilding->name.' — '.$barang->subbuilding->building->name" />
                            <x-ui.detail-item :label="__('Kode NUP')" :value="$barang->nup_code" />
                            <x-ui.detail-item :label="__('Kode FT')" :value="$barang->faculty_code" />
                            <x-ui.detail-item :label="__('No. Seri Barang')" :value="$barang->serial_number" />
                            <x-ui.detail-item :label="__('Diinput oleh')" :value="$barang->createdBy?->name" />
                            @if ($barang->archived_at)
                                <x-ui.detail-item :label="__('Alasan Arsip')" :value="$barang->archive_reason" />
                            @endif
                        </x-ui.detail-list>
                    @elseif ($tab === 'status')
                        @if ($barang->conditions->isEmpty())
                            <x-ui.empty-state
                                icon="exclamation-triangle"
                                :heading="__('Belum ada riwayat status')"
                                :description="__('Catat kondisi barang ini lewat tombol Update Status di atas.')"
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
                    @else
                        @if ($barang->maintenanceRecords->isEmpty())
                            <x-ui.empty-state
                                icon="wrench-screwdriver"
                                :heading="__('Belum ada riwayat perawatan')"
                                :description="__('Catat perawatan/perbaikan barang ini lewat tombol Tambah Riwayat di atas.')"
                            />
                        @else
                            <x-ui.timeline>
                                @foreach ($barang->maintenanceRecords as $item)
                                    <x-ui.timeline.item
                                        :title="$item->recorded_at->translatedFormat('d F Y')"
                                        :time="$item->recordedBy?->name"
                                        icon="wrench-screwdriver"
                                        tone="brand"
                                    >
                                        <div class="space-y-2">
                                            <flux:text size="sm">{{ $item->description }}</flux:text>

                                            @if ($item->cost_amount)
                                                <flux:text size="sm" class="font-medium">
                                                    {{ Number::currency((float) $item->cost_amount, 'IDR', 'id', precision: 0) }}
                                                </flux:text>
                                            @endif

                                            @if ($item->imageUrls())
                                                <div class="flex flex-wrap gap-2">
                                                    @foreach ($item->imageUrls() as $url)
                                                        <a href="{{ $url }}" target="_blank" class="block size-16 overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                                                            <img src="{{ $url }}" class="size-full object-cover" alt="" />
                                                        </a>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </x-ui.timeline.item>
                                @endforeach
                            </x-ui.timeline>
                        @endif
                    @endif
                </div>
            </flux:card>
        </div>

        <div class="space-y-6">
            <flux:card class="flex flex-col items-center gap-3 text-center !border-zinc-300 !shadow-md dark:!border-zinc-700">
                <flux:heading size="sm">{{ __('Kode QR') }}</flux:heading>
                <img src="{{ route('barang.qr', $barang) }}" alt="{{ __('Kode QR barang') }}" class="size-40 rounded-lg border border-zinc-200 dark:border-zinc-700" />
                <flux:text size="sm" class="!text-zinc-400">{{ __('Pindai untuk buka halaman ini') }}</flux:text>

                <div class="flex w-full gap-2">
                    <flux:button :href="route('barang.qr', $barang)" target="_blank" size="sm" class="flex-1">
                        {{ __('QR Web') }}
                    </flux:button>
                    <flux:button :href="route('barang.qr', ['barang' => $barang, 'format' => 'token'])" target="_blank" size="sm" class="flex-1">
                        {{ __('QR Token') }}
                    </flux:button>
                </div>

                @if (auth()->user()->isAdmin())
                    <flux:button :href="route('barang.export.label-single', $barang)" target="_blank" icon="printer" size="sm" class="w-full">
                        {{ __('Cetak Label') }}
                    </flux:button>
                @endif
            </flux:card>
        </div>
    </div>

    <flux:modal name="update-status" class="w-full max-w-md">
        <form wire:submit="simpanStatus" class="space-y-6">
            <flux:heading size="lg">{{ __('Update Status Barang') }}</flux:heading>

            <flux:radio.group wire:model="statusBaru" :label="__('Kondisi')">
                @foreach (KondisiBarang::cases() as $kondisi)
                    <flux:radio :value="$kondisi->value" :label="$kondisi->label()" />
                @endforeach
            </flux:radio.group>

            <flux:textarea wire:model="keteranganStatus" :label="__('Keterangan')" :placeholder="__('mis. Lampu proyektor mulai redup')" rows="3" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Batal') }}</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary">{{ __('Simpan') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="tambah-riwayat" class="w-full max-w-md">
        <form wire:submit="simpanRiwayat" class="space-y-6">
            <flux:heading size="lg">{{ __('Tambah Riwayat Perawatan') }}</flux:heading>

            <flux:textarea wire:model="deskripsiRiwayat" :label="__('Deskripsi')" :placeholder="__('mis. Ganti lampu proyektor')" rows="3" autofocus />

            <flux:input wire:model="tglInputRiwayat" :label="__('Tanggal')" type="date" />

            <flux:input wire:model="biayaRiwayat" :label="__('Biaya (Rp)')" type="number" min="0" step="1000" placeholder="0" />

            <flux:input type="file" wire:model="fotoRiwayat" multiple accept="image/*" :label="__('Foto bukti')" />
            @error('fotoRiwayat') <flux:error>{{ $message }}</flux:error> @enderror

            @if ($fotoRiwayat)
                <div class="grid grid-cols-4 gap-2">
                    @foreach ($fotoRiwayat as $file)
                        <div class="aspect-square overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                            <img src="{{ $file->temporaryUrl() }}" class="size-full object-cover" alt="" />
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Batal') }}</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary">{{ __('Simpan') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="barang-arsip" class="w-full max-w-md">
        <form wire:submit="arsipkan" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Arsipkan barang ini?') }}</flux:heading>
                <flux:subheading>{{ __('Data tidak dihapus dan bisa dipulihkan kapan saja.') }}</flux:subheading>
            </div>

            <flux:textarea wire:model="alasanArsip" :label="__('Alasan Pengarsipan')" rows="3" required />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Batal') }}</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="danger">{{ __('Arsipkan') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
