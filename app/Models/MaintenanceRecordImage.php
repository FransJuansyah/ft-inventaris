<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['maintenance_record_id', 'storage_disk', 'path', 'original_filename', 'mime_type', 'size_bytes', 'width', 'height', 'sort_order', 'uploaded_by'])]
class MaintenanceRecordImage extends Model
{
    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<MaintenanceRecord, $this>
     */
    public function maintenanceRecord(): BelongsTo
    {
        return $this->belongsTo(MaintenanceRecord::class);
    }

    public function url(): string
    {
        return Storage::disk($this->storage_disk)->url($this->path);
    }
}
