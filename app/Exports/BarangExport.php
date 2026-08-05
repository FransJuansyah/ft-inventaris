<?php

namespace App\Exports;

use App\Models\InventoryItem;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Kolom mengikuti FR-EXP-003 (Requirement MVP). Query-nya pakai scope yang
 * sama dengan halaman list — filter yang aktif di layar ikut terbawa ke
 * file Excel-nya (ADM-REPORT-001-A).
 *
 * @implements WithMapping<InventoryItem>
 */
class BarangExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(
        private readonly ?string $cari,
        private readonly ?int $gedung,
        private readonly ?int $jenis,
        private readonly string $urut = 'terbaru',
        private readonly string $arah = 'desc',
    ) {}

    /**
     * @return Builder<InventoryItem>
     */
    public function query(): Builder
    {
        return InventoryItem::query()
            ->with(['subbuilding.building', 'itemType', 'createdBy', 'currentCondition'])
            ->filter($this->cari, $this->gedung, $this->jenis, $this->urut, $this->arah);
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Jenis Barang',
            'Merk',
            'Spesifikasi',
            'Asal Barang',
            'Tanggal Masuk',
            'Kode NUP',
            'No. Kode FT',
            'No. Seri Barang',
            'Gedung',
            'Subgedung',
            'Kondisi Terkini',
            'Status',
            'Dibuat Oleh',
        ];
    }

    /**
     * @return array<int, string|null>
     */
    public function map($barang): array
    {
        return [
            $barang->itemType->name,
            $barang->brand,
            $barang->specification,
            $barang->acquisition_source,
            $barang->entry_date?->format('d-m-Y'),
            $barang->nup_code,
            $barang->faculty_code,
            $barang->serial_number,
            $barang->subbuilding->building->name,
            $barang->subbuilding->name,
            $barang->currentCondition?->condition_code->label(),
            $barang->isActive() ? 'Aktif' : 'Arsip',
            $barang->createdBy->name,
        ];
    }
}
