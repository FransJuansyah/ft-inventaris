<?php

namespace App\Enums;

/**
 * Peran pengguna sistem.
 *
 * Peran menentukan menu apa yang tampil dan aksi apa yang boleh
 * dilakukan. Perlu ditegaskan: menyembunyikan menu BUKAN otorisasi —
 * setiap route tetap wajib dijaga middleware/policy di sisi server.
 */
enum PeranPengguna: string
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case Petugas = 'petugas';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Admin => 'Admin',
            self::Petugas => 'Petugas',
        };
    }

    public function fluxColor(): string
    {
        return match ($this) {
            self::SuperAdmin => 'purple',
            self::Admin => 'blue',
            self::Petugas => 'zinc',
        };
    }

    /**
     * Apakah peran ini boleh mengelola data master (gedung, subgedung,
     * jenis barang) dan tambah/ubah/arsip/export barang.
     *
     * Tidak termasuk manajemen akun pengguna — itu wewenang Super Admin
     * saja, lihat isSuperAdmin().
     */
    public function isAdmin(): bool
    {
        return $this === self::Admin || $this === self::SuperAdmin;
    }

    /**
     * Apakah peran ini boleh mengelola akun pengguna (tambah, hapus,
     * blokir/buka blokir Petugas maupun Admin lain). Admin biasa TIDAK
     * mendapat akses ini — hanya Super Admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this === self::SuperAdmin;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $carry, self $case): array => $carry + [$case->value => $case->label()],
            [],
        );
    }
}
