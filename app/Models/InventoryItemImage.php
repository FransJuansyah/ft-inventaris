<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['inventory_item_id', 'storage_disk', 'path', 'original_filename', 'mime_type', 'size_bytes', 'width', 'height', 'sort_order', 'uploaded_by'])]
class InventoryItemImage extends Model
{
    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
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

    public function url(): string
    {
        return Storage::disk($this->storage_disk)->url($this->path);
    }
}
