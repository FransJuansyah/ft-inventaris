import QrScanner from 'qr-scanner';

// Dimuat lewat @vite('resources/js/scan.js') hanya di halaman /scan (bukan
// bundle utama) — library QR-scan tidak perlu ikut termuat di halaman lain.
window.QrScanner = QrScanner;
window.dispatchEvent(new CustomEvent('qr-scanner:ready'));
