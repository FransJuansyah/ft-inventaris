<?php

namespace App\Http\Controllers;

use App\Exports\BarangExport;
use App\Models\InventoryItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BarangExportController extends Controller
{
    /**
     * Export Excel — kolom sama dengan sistem lama, menghormati filter yang
     * aktif di halaman list.
     */
    public function excel(Request $request): BinaryFileResponse
    {
        $export = new BarangExport(
            cari: $request->string('cari')->value() ?: null,
            gedung: $request->integer('gedung') ?: null,
            jenis: $request->integer('jenis') ?: null,
            urut: $request->string('urut', 'terbaru')->value(),
            arah: $request->string('arah', 'desc')->value(),
        );

        return Excel::download($export, 'barang-'.now()->format('Ymd-His').'.xlsx');
    }

    /**
     * Cetak label QR untuk banyak barang sekaligus (hasil filter aktif di
     * halaman list) — untuk ditempel fisik ke barang.
     */
    public function label(Request $request): Response
    {
        $barangList = InventoryItem::query()
            ->with(['subbuilding.building'])
            ->filter(
                cari: $request->string('cari')->value() ?: null,
                gedung: $request->integer('gedung') ?: null,
                jenis: $request->integer('jenis') ?: null,
                urut: $request->string('urut', 'terbaru')->value(),
                arah: $request->string('arah', 'desc')->value(),
            )
            ->get();

        return $this->renderLabelPdf($barangList);
    }

    /**
     * Cetak label QR untuk satu barang saja, dari halaman detail.
     */
    public function labelSingle(InventoryItem $barang): Response
    {
        return $this->renderLabelPdf(collect([$barang->load('subbuilding.building')]));
    }

    /**
     * @param  Collection<int, InventoryItem>  $barangList
     */
    private function renderLabelPdf(Collection $barangList): Response
    {
        return Pdf::loadView('exports.label-barang', ['barangList' => $barangList])
            ->setPaper('a4')
            ->stream('label-barang-'.now()->format('Ymd-His').'.pdf');
    }
}
