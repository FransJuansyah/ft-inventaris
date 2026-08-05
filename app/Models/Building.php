<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['name'])]
class Building extends Model
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
        static::saving(function (Building $building): void {
            $building->normalized_name = self::normalize($building->name);
        });
    }

    public static function normalize(string $name): string
    {
        return Str::of($name)->squish()->lower()->value();
    }

    /**
     * @return HasMany<Subbuilding, $this>
     */
    public function subbuildings(): HasMany
    {
        return $this->hasMany(Subbuilding::class);
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
     * Mengarsipkan gedung. Menolak jika masih mempunyai subgedung aktif
     * (§9.2 Domain Model). Pelaku selalu diambil dari parameter eksplisit,
     * bukan dari session di dalam model — supaya model tetap testable.
     */
    public function archive(User $by): void
    {
        if (! $this->isActive()) {
            return;
        }

        if ($this->subbuildings()->whereNull('archived_at')->exists()) {
            throw new \DomainException('Gedung masih mempunyai subgedung aktif dan tidak dapat diarsipkan.');
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
