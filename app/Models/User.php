<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\PeranPengguna;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property PeranPengguna $role
 * @property string|null $username
 * @property string|null $nip
 * @property string|null $no_hp
 * @property string|null $no_wa
 * @property bool $is_active
 * @property Carbon $tgl_daftar
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'username', 'email', 'password', 'role', 'nip', 'no_hp', 'no_wa'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => PeranPengguna::class,
            'is_active' => 'boolean',
            'tgl_daftar' => 'datetime',
            'last_login_at' => 'datetime',
            'deactivated_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            /* @chisel-2fa */
            'two_factor_confirmed_at' => 'datetime',
            /* @end-chisel-2fa */
        ];
    }

    /**
     * Apakah pengguna boleh mengelola data master dan pengguna lain.
     */
    public function isAdmin(): bool
    {
        return $this->role->isAdmin();
    }

    /**
     * Apakah pengguna boleh mengelola akun pengguna lain (§ manajemen
     * pengguna) — hanya Super Admin, bukan Admin biasa.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role->isSuperAdmin();
    }

    /**
     * Menonaktifkan akun dan mencabut seluruh session aktifnya (§7.5
     * Requirement MVP / US-USR-004) dalam satu operasi konsisten.
     */
    public function deactivate(self $by): void
    {
        if (! $this->is_active) {
            return;
        }

        $this->forceFill([
            'is_active' => false,
            'deactivated_at' => now(),
            'deactivated_by' => $by->id,
        ])->save();

        \Illuminate\Support\Facades\DB::table('sessions')->where('user_id', $this->id)->delete();
    }

    public function activate(): void
    {
        $this->forceFill([
            'is_active' => true,
            'deactivated_at' => null,
            'deactivated_by' => null,
        ])->save();
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }
}
