<?php

namespace Database\Seeders;

use App\Models\Building;
use Illuminate\Database\Seeder;

class BuildingSeeder extends Seeder
{
    /**
     * Data contoh berbasis jurusan nyata Fakultas Teknik Unsri, supaya
     * dashboard/daftar tidak kosong saat dicoba.
     */
    public function run(): void
    {
        $data = [
            'Gedung Dekanat' => ['Ruang Dekan', 'Ruang Wakil Dekan', 'Ruang TU', 'Ruang Rapat', 'Aula'],
            'Gedung Teknik Sipil' => ['Ruang 101', 'Ruang 102', 'Ruang 201', 'Lab Struktur', 'Lab Mekanika Tanah', 'Ruang Dosen'],
            'Gedung Teknik Mesin' => ['Ruang 101', 'Ruang 102', 'Bengkel Mesin', 'Lab CNC', 'Lab Konversi Energi'],
            'Gedung Teknik Elektro' => ['Ruang 101', 'Ruang 102', 'Lab Elektronika', 'Lab Sistem Tenaga', 'Ruang Dosen'],
            'Gedung Teknik Kimia' => ['Ruang 101', 'Lab Kimia Dasar', 'Lab Operasi Teknik Kimia', 'Ruang Dosen'],
            'Gedung Teknik Pertambangan' => ['Ruang 101', 'Lab Geologi', 'Lab Tambang', 'Ruang Dosen'],
            'Gedung Arsitektur' => ['Ruang Studio 1', 'Ruang Studio 2', 'Lab Komputer', 'Ruang Dosen'],
        ];

        foreach ($data as $namaGedung => $ruangan) {
            $gedung = Building::create(['name' => $namaGedung]);

            $gedung->subbuildings()->createMany(
                collect($ruangan)->map(fn (string $nama) => ['name' => $nama])->all()
            );
        }
    }
}
