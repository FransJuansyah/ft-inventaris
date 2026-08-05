<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name'])]
class ItemType extends Model
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
        static::saving(function (ItemType $itemType): void {
            $itemType->normalized_name = Building::normalize($itemType->name);
        });
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

    public function archive(User $by): void
    {
        if (! $this->isActive()) {
            return;
        }

        if ($this->inventoryItems()->whereNull('archived_at')->exists()) {
            throw new \DomainException('Jenis barang masih digunakan barang aktif dan tidak dapat diarsipkan.');
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

        $this->forceFill([
            'archived_at' => null,
            'archived_by' => null,
        ])->save();
    }
}
