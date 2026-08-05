<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BarangQrController extends Controller
{
    /**
     * Render kode QR barang sebagai PNG, dua format (FR-QR-001/002):
     * - `format=web` (default): payload URL publik lengkap, langsung membuka
     *   detail barang di kamera HP mana pun.
     * - `format=token`: payload hanya token, kompatibel dengan scanner lama.
     * Keduanya memakai `public_token` yang sama — tidak pernah membuat token
     * baru saat mengganti format.
     */
    public function __invoke(Request $request, InventoryItem $barang): Response
    {
        $payload = $request->query('format') === 'token'
            ? $barang->public_token
            : route('inventaris.show', $barang->public_token);

        $options = new QROptions([
            'outputInterface' => QRGdImagePNG::class,
            'scale' => 6,
            'imageTransparent' => false,
            // Default library behavior wraps output in a base64 data URI;
            // kita butuh byte PNG mentah untuk response gambar HTTP biasa.
            'outputBase64' => false,
        ]);

        $png = (new QRCode($options))->render($payload);

        return response($png, 200, ['Content-Type' => 'image/png']);
    }
}
