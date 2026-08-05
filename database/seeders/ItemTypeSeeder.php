<?php

namespace Database\Seeders;

use App\Models\ItemType;
use Illuminate\Database\Seeder;

class ItemTypeSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            'Elektronik',
            'Furnitur',
            'Peralatan Laboratorium',
            'Peralatan Bengkel',
            'Alat Ukur',
            'Peralatan Kantor',
            'Kendaraan',
        ])->each(fn (string $nama) => ItemType::create(['name' => $nama]));
    }
}
