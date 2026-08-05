<?php

namespace App\Models;

use App\Enums\AksiArsip;
use App\Enums\KondisiBarang;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable([
    'subbuilding_id',
    'item_type_id',
    'brand',
    'specification',
    'acquisition_source',
    'entry_date',
    'nup_code',
    'faculty_code',
    'serial_number',
])]
class InventoryItem extends Model
{
    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'archived_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (InventoryItem $item): void {
            $item->public_token ??= (string) Str::uuid();
            $item->nup_code_normalized = self::normalizeCode($item->nup_code);
            $item->faculty_code_normalized = self::normalizeCode($item->faculty_code);
            $item->serial_number_normalized = self::normalizeCode($item->serial_number);
        });
    }

    public static function normalizeCode(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return Str::of($value)->squish()->lower()->value();
    }

    /**
     * @return BelongsTo<Subbuilding, $this>
     */
    public function subbuilding(): BelongsTo
    {
        return $this->belongsTo(Subbuilding::class);
    }

    /**
     * @return BelongsTo<ItemType, $this>
     */
    public function itemType(): BelongsTo
    {
        return $this->belongsTo(ItemType::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<InventoryItemCondition, $this>
     */
    public function currentCondition(): BelongsTo
    {
        return $this->belongsTo(InventoryItemCondition::class, 'current_condition_id');
    }

    /**
     * @return HasMany<InventoryItemCondition, $this>
     */
    public function conditions(): HasMany
    {
        return $this->hasMany(InventoryItemCondition::class)->orderByDesc('recorded_at')->orderByDesc('id');
    }

    /**
     * @return HasMany<MaintenanceRecord, $this>
     */
    public function maintenanceRecords(): HasMany
    {
        return $this->hasMany(MaintenanceRecord::class)->orderByDesc('recorded_at');
    }

    /**
     * @return HasMany<InventoryItemImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(InventoryItemImage::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<InventoryItemArchiveEvent, $this>
     */
    public function archiveEvents(): HasMany
    {
        return $this->hasMany(InventoryItemArchiveEvent::class)->orderByDesc('occurred_at');
    }

    public function isActive(): bool
    {
        return $this->archived_at === null;
    }

    /**
     * @return array<int, string>
     */
    public function imageUrls(): array
    {
        return $this->images->map(fn (InventoryItemImage $image): string => Storage::disk($image->storage_disk)->url($image->path))->all();
    }

    /**
     * Membuat barang baru beserta kondisi awal Baik dalam satu transaksi
     * atomik (§22.1 Domain Model / DATA-001 Use Case Spec) — jika kondisi
     * awal gagal dibuat, barang tidak ikut tersimpan.
     *
     * @param  array<string, mixed>  $attributes
     */
    public static function buatDenganKondisiAwal(array $attributes, User $pembuat): self
    {
        return DB::transaction(function () use ($attributes, $pembuat): self {
            $item = new self($attributes);
            $item->created_by = $pembuat->id;
            $item->updated_by = $pembuat->id;
            $item->save();

            $item->tambahKondisi(
                kondisi: KondisiBarang::Baik,
                keterangan: 'Status awal saat barang didaftarkan.',
                pencatat: $pembuat,
            );

            return $item;
        });
    }

    /**
     * Menambah riwayat kondisi baru dan memindahkan pointer
     * current_condition_id dalam satu transaksi (§22.2 Domain Model).
     * Menolak jika barang sudah diarsipkan (FR-COND / SEC-005).
     */
    public function tambahKondisi(KondisiBarang $kondisi, string $keterangan, User $pencatat): InventoryItemCondition
    {
        if (! $this->isActive()) {
            throw new \DomainException('Barang sudah tidak aktif sehingga kondisi tidak dapat ditambahkan.');
        }

        return DB::transaction(function () use ($kondisi, $keterangan, $pencatat): InventoryItemCondition {
            $condition = $this->conditions()->create([
                'condition_code' => $kondisi,
                'notes' => $keterangan,
                'recorded_by' => $pencatat->id,
                'record_source' => 'user',
                'recorded_at' => now(),
            ]);

            $this->forceFill(['current_condition_id' => $condition->id])->save();

            return $condition;
        });
    }

    /**
     * Mengarsipkan barang. Alasan wajib diisi (§17.3 Domain Model).
     */
    public function archive(string $reason, User $by): void
    {
        if (! $this->isActive()) {
            return;
        }

        if (blank($reason)) {
            throw new \DomainException('Alasan pengarsipan wajib diisi.');
        }

        DB::transaction(function () use ($reason, $by): void {
            $this->forceFill([
                'archived_at' => now(),
                'archived_by' => $by->id,
                'archive_reason' => $reason,
                'updated_by' => $by->id,
            ])->save();

            $this->archiveEvents()->create([
                'action' => AksiArsip::Arsipkan,
                'reason' => $reason,
                'performed_by' => $by->id,
                'event_source' => 'user',
                'occurred_at' => now(),
            ]);
        });
    }

    /**
     * Memulihkan barang arsip. Menolak jika subgedung/jenis tidak aktif
     * atau Kode FT konflik dengan barang aktif lain (§17.4 Domain Model).
     */
    public function restore(User $by): void
    {
        if ($this->isActive()) {
            return;
        }

        if (! $this->subbuilding->isActive()) {
            throw new \DomainException('Subgedung barang ini sudah diarsipkan, pilih subgedung aktif terlebih dahulu.');
        }

        if (! $this->itemType->isActive()) {
            throw new \DomainException('Jenis barang ini sudah diarsipkan, pilih jenis barang aktif terlebih dahulu.');
        }

        if (
            filled($this->faculty_code_normalized)
            && self::query()
                ->whereKeyNot($this->id)
                ->whereNull('archived_at')
                ->where('faculty_code_normalized', $this->faculty_code_normalized)
                ->exists()
        ) {
            throw new \DomainException('Nomor Kode FT sudah dipakai barang aktif lain, tidak dapat dipulihkan.');
        }

        DB::transaction(function () use ($by): void {
            $this->forceFill([
                'archived_at' => null,
                'archived_by' => null,
                'archive_reason' => null,
                'updated_by' => $by->id,
            ])->save();

            $this->archiveEvents()->create([
                'action' => AksiArsip::Pulihkan,
                'performed_by' => $by->id,
                'event_source' => 'user',
                'occurred_at' => now(),
            ]);
        });
    }

    public function qrCodeDataUri(): string
    {
        $options = new QROptions([
            'outputInterface' => QRGdImagePNG::class,
            'scale' => 6,
            'imageTransparent' => false,
        ]);

        return (new QRCode($options))->render(route('inventaris.show', $this->public_token));
    }

    /**
     * Pencarian + filter + sort dipakai bareng oleh halaman list internal
     * dan export — satu sumber kebenaran.
     *
     * @param  Builder<InventoryItem>  $query
     * @return Builder<InventoryItem>
     */
    public function scopeFilter(
        Builder $query,
        ?string $cari,
        ?int $gedung,
        ?int $jenis,
        string $urut = 'terbaru',
        string $arah = 'desc',
    ): Builder {
        $kolomUrut = match ($urut) {
            'nama' => 'brand',
            'kode_nup' => 'nup_code',
            'no_seri_ft' => 'faculty_code',
            'no_seri_barang' => 'serial_number',
            default => 'created_at',
        };

        $arahUrut = $arah === 'asc' ? 'asc' : 'desc';

        return $query
            ->when($cari, fn (Builder $q) => $q->where(function (Builder $q) use ($cari) {
                $q->where('brand', 'like', "%{$cari}%")
                    ->orWhere('nup_code', 'like', "%{$cari}%")
                    ->orWhere('faculty_code', 'like', "%{$cari}%")
                    ->orWhere('serial_number', 'like', "%{$cari}%");
            }))
            ->when($gedung, fn (Builder $q) => $q->whereHas(
                'subbuilding',
                fn (Builder $q) => $q->where('building_id', $gedung)
            ))
            ->when($jenis, fn (Builder $q) => $q->where('item_type_id', $jenis))
            ->orderBy($kolomUrut, $arahUrut);
    }

    /**
     * Query publik: hanya barang aktif, tidak pernah dipakai untuk query
     * yang meng-eager-load data internal (maintenance/pencatat) — lihat
     * §23.1 Domain Model.
     *
     * @param  Builder<InventoryItem>  $query
     * @return Builder<InventoryItem>
     */
    public function scopePublik(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }
}
