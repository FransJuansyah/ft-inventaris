<?php

namespace Database\Seeders;

use App\Enums\PeranPengguna;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    // Catatan: WithoutModelEvents sengaja TIDAK dipakai — seeder di rebuild
    // ini bergantung pada model event `saving` (normalisasi nama, kode
    // barang, dsb.) dan pada method domain seperti
    // InventoryItem::buatDenganKondisiAwal(), yang keduanya butuh event
    // Eloquent aktif.

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => PeranPengguna::SuperAdmin,
        ]);

        $this->call(BuildingSeeder::class);
        $this->call(ItemTypeSeeder::class);
        $this->call(InventoryItemSeeder::class);
        $this->call(MaintenanceRecordSeeder::class);
    }
}
