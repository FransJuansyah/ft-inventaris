<?php

namespace Database\Seeders;

use App\Models\InventoryItem;
use App\Models\MaintenanceRecord;
use App\Models\User;
use Illuminate\Database\Seeder;

class MaintenanceRecordSeeder extends Seeder
{
    /**
     * Riwayat perawatan untuk sebagian barang saja (bukan semua) — supaya
     * ada contoh barang yang sudah punya riwayat dan yang belum saat dicoba.
     * Tidak membangun form maintenance record — di luar scope pass ini,
     * data hanya di-seed langsung.
     */
    public function run(): void
    {
        $user = User::first();
        $barangList = InventoryItem::orderBy('id')->get();

        if (! $user || $barangList->isEmpty()) {
            return;
        }

        $riwayat = [
            ['AC Split Daikin 1PK', 'Pembersihan filter dan pengisian ulang freon.', 150_000, '-4 months'],
            ['Proyektor Epson EB-X51', 'Penggantian lampu proyektor yang mulai redup.', 850_000, '-2 months'],
            ['Mesin Bubut CNC', 'Servis rutin dan pelumasan komponen mekanis.', 1_200_000, '-6 months'],
            ['Laptop ASUS ExpertBook', 'Penggantian baterai yang sudah drop.', 650_000, '-1 months'],
            ['Osiloskop Digital', 'Kalibrasi ulang sesuai jadwal tahunan.', 400_000, '-3 months'],
            ['Mesin Las Listrik', 'Perbaikan kabel yang terkelupas.', 200_000, '-5 months'],
            ['Printer Canon LBP', 'Penggantian toner dan pembersihan drum.', 320_000, '-2 weeks'],
            ['Kursi Kuliah Chitose', 'Perbaikan dudukan yang longgar.', 75_000, '-3 weeks'],
        ];

        foreach ($riwayat as [$merk, $deskripsi, $biaya, $waktu]) {
            $barang = $barangList->firstWhere('brand', $merk);

            if (! $barang) {
                continue;
            }

            MaintenanceRecord::create([
                'inventory_item_id' => $barang->id,
                'description' => $deskripsi,
                'cost_amount' => $biaya,
                'recorded_by' => $user->id,
                'record_source' => 'legacy',
                'recorded_at' => now()->modify($waktu)->format('Y-m-d'),
            ]);
        }
    }
}
