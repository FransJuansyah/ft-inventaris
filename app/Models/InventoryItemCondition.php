<?php

namespace App\Models;

use App\Enums\KondisiBarang;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['inventory_item_id', 'condition_code', 'notes'])]
class InventoryItemCondition extends Model
{
    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'condition_code' => KondisiBarang::class,
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

    public function isLegacy(): bool
    {
        return $this->record_source === 'legacy';
    }
}
