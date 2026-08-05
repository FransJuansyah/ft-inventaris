/**
 * Animasi masuk halaman auth (login, register, confirm-password, dst —
 * lihat resources/views/layouts/auth/simple.blade.php untuk markup &
 * CSS-nya). Dipicu lewat `livewire:navigated`, bukan `DOMContentLoaded`
 * atau inline <script> per halaman — event ini menyala konsisten baik
 * saat load penuh maupun saat halaman dituju lewat redirect yang diikuti
 * `wire:navigate` (mis. Pengaturan → Keamanan yang redirect ke
 * /user/confirm-password), yang mana DOM-nya di-morph dan TIDAK menjalankan
 * ulang <script> yang disisipkan lewat cara itu.
 *
 * Sengaja tidak menghormati `prefers-reduced-motion` — ini animasi yang
 * diminta eksplisit untuk terlihat oleh pemilik sistem, dan setting
 * Windows "Tampilkan animasi di Windows" yang mati ternyata sempat bikin
 * animasinya melompat ke kondisi akhir tanpa sempat terlihat.
 */
document.addEventListener('livewire:navigated', function () {
    document.querySelectorAll('.auth-enter').forEach(function (el, index) {
        setTimeout(function () {
            el.classList.add('is-visible');
        }, 400 + index * 350);
    });

    var overlay = document.querySelector('.auth-overlay');
    if (overlay) {
        setTimeout(function () {
            overlay.classList.add('is-visible');
        }, 2200);
    }
});
