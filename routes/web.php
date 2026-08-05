<?php

use App\Http\Controllers\BarangExportController;
use App\Http\Controllers\BarangQrController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/inventaris')->name('home');

// Portal publik — tanpa login (FR-PUB-001..007, UC-PUB-001..004).
Route::livewire('inventaris', 'pages::publik.index')->name('inventaris.index');
Route::livewire('inventaris/{token}', 'pages::publik.show')->name('inventaris.show');
Route::livewire('scan', 'pages::scan.index')->name('scan');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', 'pages::dashboard.index')->name('dashboard');

    // Barang: list boleh diakses Petugas maupun Administrator (§5
    // Requirement MVP — Petugas punya akses global ke seluruh inventaris
    // aktif).
    Route::livewire('barang', 'pages::barang.index')->name('barang.index');

    // Master data, tambah/export barang adalah kewenangan Administrator
    // (§5 Requirement MVP: Petugas tidak boleh mengelola master data atau
    // export). PENTING: route literal seperti `barang/create` WAJIB
    // terdaftar SEBELUM `barang/{barang}` di bawah — Laravel mencocokkan
    // route berurutan, jadi kalau wildcard duluan, `/barang/create` malah
    // ditangkap sebagai {barang}="create" dan 404 karena barang dengan ID
    // itu tidak ada.
    Route::middleware('admin')->group(function () {
        Route::livewire('gedung', 'pages::gedung.index')->name('gedung.index');
        Route::livewire('gedung/{gedung}/subgedung', 'pages::subgedung.index')->name('subgedung.index');
        Route::livewire('jenis-barang', 'pages::jenis-barang.index')->name('jenis-barang.index');

        Route::livewire('barang/create', 'pages::barang.form')->name('barang.create');
        Route::get('barang/export/excel', [BarangExportController::class, 'excel'])->name('barang.export.excel');
        Route::get('barang/export/label', [BarangExportController::class, 'label'])->name('barang.export.label');
    });

    // Detail & QR barang: Petugas maupun Administrator. Wildcard, jadi
    // harus di bawah semua route literal `barang/...` di atas.
    Route::livewire('barang/{barang}', 'pages::barang.show')->name('barang.show.internal');
    Route::get('barang/{barang}/qr', BarangQrController::class)->name('barang.qr');

    Route::middleware('admin')->group(function () {
        Route::livewire('barang/{barang}/edit', 'pages::barang.form')->name('barang.edit');
        Route::get('barang/{barang}/export/label', [BarangExportController::class, 'labelSingle'])->name('barang.export.label-single');
    });

    // Manajemen akun pengguna (tambah, hapus, blokir) khusus Super Admin
    // — Admin biasa tidak boleh membuat atau menghapus pengguna.
    Route::middleware('super-admin')->group(function () {
        Route::livewire('pengguna', 'pages::pengguna.index')->name('pengguna.index');
    });
});

require __DIR__.'/settings.php';
