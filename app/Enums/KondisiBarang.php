<?php

namespace App\Enums;

/**
 * Kondisi fisik sebuah barang.
 *
 * Nilai integer sengaja dipertahankan sama persis dengan sistem lama
 * (kolom `barang_status.status`) supaya migrasi data lama tidak perlu
 * pemetaan ulang. Di kode aplikasi, selalu pakai case enum-nya —
 * jangan pernah menulis angka mentah 0/1/2/9.
 */
enum KondisiBarang: int
{
    case Baik = 0;
    case RusakRingan = 1;
    case RusakBerat = 2;
    case Penghapusan = 9;

    /**
     * Label yang ditampilkan ke pengguna.
     */
    public function label(): string
    {
        return match ($this) {
            self::Baik => 'Baik',
            self::RusakRingan => 'Rusak Ringan',
            self::RusakBerat => 'Rusak Berat',
            self::Penghapusan => 'Penghapusan',
        };
    }

    /**
     * Nama warna bawaan flux:badge / flux:callout.
     *
     * Flux hanya menerima nama warna palet Tailwind (bukan token kustom),
     * jadi pemetaannya ada di sini — bukan di CSS.
     */
    public function fluxColor(): string
    {
        return match ($this) {
            self::Baik => 'green',
            self::RusakRingan => 'amber',
            self::RusakBerat => 'red',
            self::Penghapusan => 'zinc',
        };
    }

    /**
     * Nama ikon Heroicon yang mewakili kondisi ini.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Baik => 'check-circle',
            self::RusakRingan => 'exclamation-triangle',
            self::RusakBerat => 'x-circle',
            self::Penghapusan => 'archive-box-x-mark',
        };
    }

    /**
     * Kelas Tailwind untuk aksen non-teks (titik status, garis tepi).
     *
     * Ditulis literal supaya terbaca oleh pemindai kelas Tailwind.
     */
    public function dotClass(): string
    {
        return match ($this) {
            self::Baik => 'bg-kondisi-baik',
            self::RusakRingan => 'bg-kondisi-rusak-ringan',
            self::RusakBerat => 'bg-kondisi-rusak-berat',
            self::Penghapusan => 'bg-kondisi-penghapusan',
        };
    }

    /**
     * Semua kondisi sebagai pasangan nilai => label, untuk isian select.
     *
     * @return array<int, string>
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
