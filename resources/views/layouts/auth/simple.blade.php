<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')

        {{--
            Urutan animasi masuk: foto gerbang tampil jelas dulu (0–1.3
            detik, belum ada overlay/blur), lalu badge+judul+kartu+footer
            muncul dengan fade+slide, dan overlay blur baru menyala paling
            akhir setelah kartunya "landed".

            Logic pemicunya (timer + class `is-visible`) ada di
            resources/js/app.js, BUKAN inline <script> di sini — halaman
            auth ini sering dituju lewat REDIRECT dari halaman Livewire
            lain (mis. Pengaturan → Keamanan yang butuh konfirmasi password
            dulu, redirect ke /user/confirm-password). Kalau redirect itu
            diikuti lewat `wire:navigate` (morph DOM, bukan reload
            sungguhan), inline <script> di <head> TIDAK PERNAH dieksekusi
            ulang — browser hanya menjalankan <script> yang di-parse
            langsung, bukan yang disisipkan lewat morph. Makanya kartu bisa
            "hilang" (nyangkut di opacity:0) dan cuma foto latar yang
            kelihatan. `livewire:navigated` di app.js menyala di SEMUA
            skenario (load penuh maupun soft-navigate), jadi ini titik
            pemicu yang benar-benar bisa diandalkan.
        --}}
        <style>
            .auth-overlay,
            .auth-enter {
                opacity: 0;
            }
            .auth-overlay {
                transition: opacity 1.4s ease-out;
            }
            .auth-enter {
                transform: translateY(14px);
                transition: opacity 0.8s cubic-bezier(0.16, 1, 0.3, 1), transform 0.8s cubic-bezier(0.16, 1, 0.3, 1);
            }
            .auth-overlay.is-visible,
            .auth-enter.is-visible {
                opacity: 1;
                transform: none;
            }
        </style>
    </head>
    <body class="min-h-screen antialiased">
        <div class="relative flex min-h-svh flex-col items-center justify-center overflow-hidden p-6 md:p-10">
            {{-- Foto gerbang kampus Unsri — sama dengan yang dipakai di sia-unsri. --}}
            <div class="fixed inset-0 -z-10">
                <img
                    src="{{ asset('images/bg-login-unsri.jpg') }}"
                    alt=""
                    class="h-full w-full object-cover"
                />
                {{--
                    Halaman ini ikut preferensi sistem seperti sisa aplikasi
                    (lihat @fluxAppearance di partials.head). Blur diset ke
                    ~25% dari skala blur Tailwind (6px dari maksimum 24px di
                    `xl`) — cukup untuk menghaluskan foto jadi elemen
                    atmosfer tanpa membuatnya kabur total. Animasi masuknya
                    lewat kelas `auth-overlay` di atas (opacity 0 → 1,
                    ditunda sampai kartu login selesai muncul).
                --}}
                <div class="auth-overlay absolute inset-0 bg-gradient-to-b from-white/60 via-white/35 to-white/60 backdrop-blur-[6px] dark:from-zinc-950/55 dark:via-zinc-950/35 dark:to-zinc-950/55"></div>
            </div>

            <div class="auth-enter auth-enter-1 relative z-10 mb-6 flex flex-col items-center gap-3 text-center">
                <a href="{{ route('home') }}" wire:navigate class="flex flex-col items-center gap-3">
                    {{--
                        Badge "Apple glass": kaca beku (frosted glass) sungguhan
                        — bg putih transparan + backdrop-blur kuat + border tipis
                        terang + kilau gradien di sudut kiri-atas (meniru pantulan
                        cahaya di panel kaca) + ring tipis di bagian dalam. Sengaja
                        SAMA persis di light & dark mode (bukan varian dark: yang
                        beda warna) — ciri khas glassmorphism justru dia netral,
                        warnanya ikut apa pun yang ada di baliknya.
                    --}}
                    <span class="relative inline-flex size-24 items-center justify-center overflow-hidden rounded-[1.75rem] border border-white/40 bg-white/20 shadow-xl backdrop-blur-xl transition-transform duration-500 hover:rotate-3">
                        <span class="pointer-events-none absolute inset-0 bg-gradient-to-br from-white/50 via-white/10 to-transparent"></span>
                        <span class="pointer-events-none absolute inset-0 rounded-[1.75rem] ring-1 ring-white/30 ring-inset"></span>
                        <img
                            src="{{ asset('images/logo-unsri.png') }}"
                            alt="{{ __('Universitas Sriwijaya') }}"
                            class="relative size-16 object-contain drop-shadow-lg"
                        />
                    </span>
                    <span class="font-serif leading-tight">
                        <span class="block text-3xl font-bold text-zinc-900 drop-shadow-lg dark:text-white">{{ __('Fakultas Teknik') }}</span>
                        <span class="mt-1 block text-sm text-zinc-800 italic drop-shadow-sm dark:text-white/90">{{ __('Sistem Inventaris & Aset — Universitas Sriwijaya') }}</span>
                    </span>
                </a>
            </div>

            {{--
                Kartu solid, dipaksa eksplisit — default flux:card di mode
                gelap sebenarnya `dark:bg-white/10` (10% transparan), yang
                di halaman lain tidak masalah karena latar belakangnya cuma
                warna polos. Di sini latarnya foto ramai, jadi transparansi
                itu bikin kartu jadi "kaca" lagi dan teks tumpang tindih
                dengan foto. `!bg-white dark:!bg-zinc-900` menimpa default
                itu supaya kartu benar-benar solid di kedua mode.
            --}}
            <flux:card class="auth-enter auth-enter-2 relative z-10 w-full max-w-md !border-zinc-300 !bg-white !shadow-xl dark:!border-zinc-700 dark:!bg-zinc-900">
                {{ $slot }}
            </flux:card>

            {{-- Warna disamakan dengan subtitle di atas (zinc-800/white-90 + drop-shadow), bukan putih paksa. --}}
            <div class="auth-enter auth-enter-3 relative z-10 mt-10 text-center text-xs text-zinc-800 drop-shadow-sm dark:text-white/90">
                <p>&copy; {{ date('Y') }} {{ __('Universitas Sriwijaya') }}</p>
                <p class="mt-1 italic">{{ __('Fakultas Teknik') }}</p>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
