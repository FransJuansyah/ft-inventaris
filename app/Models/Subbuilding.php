<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['building_id', 'name'])]
class Subbuilding extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'archived_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Subbuilding $subbuilding): void {
            $subbuilding->normalized_name = Building::normalize($subbuilding->name);
        });
    }

    /**
     * @return BelongsTo<Building, $this>
     */
    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    /**
     * @return HasMany<InventoryItem, $this>
     */
    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function archivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    public function isActive(): bool
    {
        return $this->archived_at === null;
    }

    /**
     * Menolak arsip jika masih mempunyai barang aktif (§10.2 Domain Model).
     */
    public function archive(User $by): void
    {
        if (! $this->isActive()) {
            return;
        }

        if ($this->inventoryItems()->whereNull('archived_at')->exists()) {
            throw new \DomainException('Subgedung masih mempunyai barang aktif dan tidak dapat diarsipkan.');
        }

        $this->forceFill([
            'archived_at' => now(),
            'archived_by' => $by->id,
        ])->save();
    }

    public function restore(): void
    {
        if ($this->isActive()) {
            return;
        }

        if (! $this->building->isActive()) {
            throw new \DomainException('Gedung induk masih diarsipkan, pulihkan gedung terlebih dahulu.');
        }

        $this->forceFill([
            'archived_at' => null,
            'archived_by' => null,
        ])->save();
    }
}
