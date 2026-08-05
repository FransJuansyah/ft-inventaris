<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable(['inventory_item_id', 'description', 'cost_amount'])]
class MaintenanceRecord extends Model
{
    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'cost_amount' => 'integer',
            'recorded_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<InventoryItem, $this>
     */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * @return HasMany<MaintenanceRecordImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(MaintenanceRecordImage::class)->orderBy('sort_order');
    }

    /**
     * @return array<int, string>
     */
    public function imageUrls(): array
    {
        return $this->images->map(fn (MaintenanceRecordImage $image): string => Storage::disk($image->storage_disk)->url($image->path))->all();
    }
}
