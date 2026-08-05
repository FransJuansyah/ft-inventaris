<?php

namespace Database\Seeders;

use App\Models\InventoryItem;
use App\Models\ItemType;
use App\Models\Subbuilding;
use App\Models\User;
use Illuminate\Database\Seeder;

class InventoryItemSeeder extends Seeder
{
    /**
     * Data contoh lintas gedung/ruangan yang sudah di-seed BuildingSeeder,
     * supaya daftar Barang tidak kosong saat dicoba. Tiap barang otomatis
     * dapat kondisi awal "Baik" lewat InventoryItem::buatDenganKondisiAwal().
     */
    public function run(): void
    {
        $pembuat = User::first();

        if (! $pembuat || Subbuilding::count() === 0 || ItemType::count() === 0) {
            return;
        }

        $items = [
            ['Kursi Kuliah Chitose', 'Furnitur', '-5 years'],
            ['Meja Dosen', 'Furnitur', '-4 years'],
            ['Lemari Arsip Besi', 'Peralatan Kantor', '-3 years'],
            ['Whiteboard', 'Peralatan Kantor', '-2 years'],
            ['Proyektor Epson EB-X51', 'Elektronik', '-1 years'],
            ['AC Split Daikin 1PK', 'Elektronik', '-2 years'],
            ['Laptop ASUS ExpertBook', 'Elektronik', '-6 months'],
            ['Komputer PC Rakitan', 'Elektronik', '-1 years'],
            ['Printer Canon LBP', 'Elektronik', '-8 months'],
            ['Mesin Bubut CNC', 'Peralatan Bengkel', '-4 years'],
            ['Mesin Las Listrik', 'Peralatan Bengkel', '-3 years'],
            ['Gerinda Tangan', 'Peralatan Bengkel', '-2 years'],
            ['Osiloskop Digital', 'Alat Ukur', '-2 years'],
            ['Multimeter Digital', 'Alat Ukur', '-1 years'],
            ['Jangka Sorong Digital', 'Alat Ukur', '-1 years'],
            ['Timbangan Analitik', 'Peralatan Laboratorium', '-3 years'],
            ['Autoklaf Laboratorium', 'Peralatan Laboratorium', '-2 years'],
            ['Lemari Asam', 'Peralatan Laboratorium', '-4 years'],
            ['Sepeda Motor Dinas', 'Kendaraan', '-5 years'],
            ['Mobil Operasional', 'Kendaraan', '-3 years'],
        ];

        $subbuildingIds = Subbuilding::pluck('id')->all();

        foreach ($items as $i => [$merk, $jenis, $masuk]) {
            $itemType = ItemType::where('name', $jenis)->first();

            if (! $itemType) {
                continue;
            }

            InventoryItem::buatDenganKondisiAwal([
                'subbuilding_id' => $subbuildingIds[$i % count($subbuildingIds)],
                'item_type_id' => $itemType->id,
                'brand' => $merk,
                'specification' => '',
                'acquisition_source' => fake()->randomElement(['APBN', 'Hibah', 'Mandiri']),
                'entry_date' => now()->modify($masuk)->format('Y-m-d'),
                'nup_code' => sprintf('3.05.%02d.%02d.%03d', fake()->numberBetween(1, 5), fake()->numberBetween(1, 5), $i + 1),
                'faculty_code' => sprintf('FT-%04d-%03d', now()->year, $i + 1),
                'serial_number' => strtoupper(fake()->bothify('SN-########')),
            ], $pembuat);
        }
    }
}
