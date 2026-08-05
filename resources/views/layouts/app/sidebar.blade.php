<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-50 dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                @include('partials.nav')
            </flux:sidebar.nav>

            <flux:spacer />

            @auth
                <x-desktop-user-menu class="hidden lg:block" />

                {{-- Versi HP drawer: tombol Keluar gaya item menu biasa
                     (ikon + label), dipindah dari header supaya tidak dobel
                     dan konsisten satu tempat saja. --}}
                <div class="border-t border-zinc-200 p-3 lg:hidden dark:border-zinc-700">
                    <form method="POST" action="{{ route('logout') }}" onsubmit="window.__ftShowLogoutLoading && window.__ftShowLogoutLoading()">
                        @csrf
                        <button
                            type="submit"
                            data-test="logout-button"
                            class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10"
                        >
                            <flux:icon name="arrow-right-start-on-rectangle" class="size-4" />
                            {{ __('Keluar') }}
                        </button>
                    </form>
                </div>
            @else
                <div class="hidden p-2 lg:block">
                    <flux:button :href="route('login')" wire:navigate class="w-full">
                        {{ __('Login') }}
                    </flux:button>
                </div>
            @endauth
        </flux:sidebar>

        {{-- Bilah atas khusus HP: cuma tombol menu. Scan QR dan Akun ada di
             bottom navigation (lihat di bawah), dan tombol Keluar (bulat
             merah) sekarang di dalam drawer sidebar itu sendiri — supaya
             tidak dobel dengan yang di header (lihat <flux:sidebar> di
             atas). Sesuai pola DD-UI-004/WF-PET-INV-001 §11.2. --}}
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            @guest
                <flux:button :href="route('login')" wire:navigate size="sm">
                    {{ __('Login') }}
                </flux:button>
            @endguest
        </flux:header>

        {{--
            PENTING: jangan bungkus atau sisipkan elemen sibling di sekitar
            {{ $slot }}. Flux mendeteksi konten utama (grid-area: main,
            attribute data-flux-main) berdasarkan struktur DOM tepat setelah
            <flux:sidebar>/<flux:header> — menambah <div> pembungkus atau
            spacer di sini pernah merusak deteksi itu dan menyisakan area
            kosong besar di layout. Ruang bawah untuk bottom nav ponsel
            ditambahkan lewat CSS langsung ke [data-flux-main] di bawah,
            tanpa mengubah struktur Blade sama sekali.
        --}}
        <style>
            @media (max-width: 1023px) {
                [data-flux-main] {
                    padding-bottom: 6rem;
                }
            }
        </style>
        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        {{--
            Bottom navigation ponsel (DD-UI-004 & WF-PET-INV-001 §11.2):
            Scan QR menonjol di tengah karena itu aksi lapangan paling
            sering dipakai Petugas — sengaja dibuat besar & mudah dijangkau
            ibu jari, bukan cuma tombol kecil di header. Sama untuk publik
            (§4.3 IA doc) dengan item Login menggantikan Akun.

            z-10, BUKAN lebih tinggi — drawer sidebar mobile Flux memakai
            `z-20!` (lihat vendor/livewire/flux/stubs/.../sidebar/index.blade.php).
            Sempat dipasang z-40 di sini dan menutupi tombol Keluar di
            drawer (klik tidak tembus, ketutup nav ini) karena z-40 > z-20.
        --}}
        <nav
            class="fixed inset-x-0 bottom-0 z-10 flex items-stretch justify-around border-t border-zinc-200 bg-white pb-[env(safe-area-inset-bottom)] lg:hidden dark:border-zinc-700 dark:bg-zinc-900"
            aria-label="{{ __('Navigasi utama') }}"
        >
            @auth
                <a
                    href="{{ route('barang.index') }}"
                    wire:navigate
                    class="flex flex-1 flex-col items-center justify-center gap-0.5 py-2.5 text-xs {{ request()->routeIs('barang.*') ? 'text-[var(--color-accent)]' : 'text-zinc-500 dark:text-zinc-400' }}"
                >
                    <flux:icon name="cube" class="size-5" />
                    {{ __('Barang') }}
                </a>

                <a href="{{ route('scan') }}" wire:navigate class="flex flex-1 flex-col items-center justify-start pt-1">
                    <span class="-mt-5 flex size-12 items-center justify-center rounded-full bg-[var(--color-accent)] text-white shadow-lg ring-4 ring-zinc-50 dark:ring-zinc-800">
                        <flux:icon name="qr-code" class="size-6" />
                    </span>
                    <span class="mt-1 text-xs {{ request()->routeIs('scan') ? 'font-medium text-[var(--color-accent)]' : 'text-zinc-500 dark:text-zinc-400' }}">
                        {{ __('Scan') }}
                    </span>
                </a>

                <a
                    href="{{ route('profile.edit') }}"
                    wire:navigate
                    class="flex flex-1 flex-col items-center justify-center gap-0.5 py-2.5 text-xs {{ request()->routeIs('profile.*') || request()->routeIs('appearance.*') || request()->routeIs('security.*') ? 'text-[var(--color-accent)]' : 'text-zinc-500 dark:text-zinc-400' }}"
                >
                    <flux:icon name="user-circle" class="size-5" />
                    {{ __('Akun') }}
                </a>
            @else
                <a
                    href="{{ route('inventaris.index') }}"
                    wire:navigate
                    class="flex flex-1 flex-col items-center justify-center gap-0.5 py-2.5 text-xs {{ request()->routeIs('inventaris.*') ? 'text-[var(--color-accent)]' : 'text-zinc-500 dark:text-zinc-400' }}"
                >
                    <flux:icon name="cube" class="size-5" />
                    {{ __('Inventaris') }}
                </a>

                <a href="{{ route('scan') }}" wire:navigate class="flex flex-1 flex-col items-center justify-start pt-1">
                    <span class="-mt-5 flex size-12 items-center justify-center rounded-full bg-[var(--color-accent)] text-white shadow-lg ring-4 ring-zinc-50 dark:ring-zinc-800">
                        <flux:icon name="qr-code" class="size-6" />
                    </span>
                    <span class="mt-1 text-xs {{ request()->routeIs('scan') ? 'font-medium text-[var(--color-accent)]' : 'text-zinc-500 dark:text-zinc-400' }}">
                        {{ __('Scan') }}
                    </span>
                </a>

                <a
                    href="{{ route('login') }}"
                    wire:navigate
                    class="flex flex-1 flex-col items-center justify-center gap-0.5 py-2.5 text-xs {{ request()->routeIs('login') ? 'text-[var(--color-accent)]' : 'text-zinc-500 dark:text-zinc-400' }}"
                >
                    <flux:icon name="arrow-right-end-on-rectangle" class="size-5" />
                    {{ __('Login') }}
                </a>
            @endauth
        </nav>

        {{--
            Layar loading saat logout — sama polanya seperti overlay login
            (lihat pages/auth/login.blade.php), supaya jeda POST + redirect
            ke halaman publik tidak terasa blank/kosong. Dipasang sekali di
            sini (layout bersama), dipicu dari form logout mana pun lewat
            `window.__ftShowLogoutLoading()` (ada di sidebar desktop & bar
            atas mobile — lihat onsubmit di form masing-masing).
        --}}
        <div
            id="ft-logout-loading"
            class="pointer-events-none fixed inset-0 z-50 flex flex-col items-center justify-center gap-4 bg-zinc-50 opacity-0 transition-opacity duration-300 dark:bg-zinc-900"
        >
            <span class="flex size-16 items-center justify-center overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-800">
                <img src="{{ asset('images/logo-unsri.png') }}" alt="" class="size-11 object-contain" />
            </span>
            <svg class="size-6 animate-spin text-brand-600 dark:text-brand-400" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <flux:text class="!text-zinc-500 dark:!text-zinc-400">{{ __('Keluar...') }}</flux:text>
        </div>
        <script>
            window.__ftShowLogoutLoading = function () {
                var el = document.getElementById('ft-logout-loading');
                if (!el) return;
                el.classList.remove('pointer-events-none');
                el.style.opacity = '1';
            };
        </script>

        @fluxScripts
    </body>
</html>
