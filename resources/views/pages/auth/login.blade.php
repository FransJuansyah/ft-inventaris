<x-layouts::auth :title="__('Masuk')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Masuk ke akun Anda')" :description="__('Masukkan email dan kata sandi untuk masuk')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        {{-- @chisel-passkeys --}}
        <x-passkey-verify />
        {{-- @end-chisel-passkeys --}}

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6" onsubmit="window.__ftShowLoginLoading && window.__ftShowLoginLoading()">
            @csrf

            <!-- Email Address -->
            <flux:input
                name="email"
                icon="envelope"
                :label="__('Alamat email')"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="email@contoh.com"
            />

            <!-- Password -->
            <div class="relative">
                <flux:input
                    name="password"
                    icon="lock-closed"
                    :label="__('Kata sandi')"
                    type="password"
                    required
                    autocomplete="current-password"
                    :placeholder="__('Kata sandi')"
                    viewable
                />

                @if (Route::has('password.request'))
                    <flux:link
                        :accent="false"
                        class="absolute top-0 text-sm end-0 !text-blue-600 dark:!text-blue-400"
                        :href="route('password.request')"
                        wire:navigate
                    >
                        {{ __('Lupa kata sandi?') }}
                    </flux:link>
                @endif
            </div>

            <!-- Remember Me -->
            <flux:checkbox name="remember" :label="__('Ingat saya')" :checked="old('remember')" />

            <div class="flex items-center justify-end">
                <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                    {{ __('Masuk') }}
                </flux:button>
            </div>
        </form>
    </div>

    {{--
        Layar loading setelah submit login — sebelum ini, jeda antara klik
        "Masuk" dan dashboard muncul cuma blank/putih polos karena form ini
        POST biasa (bukan Livewire), jadi browser reload penuh. Overlay ini
        langsung tampil saat submit (lihat onsubmit di atas) dan otomatis
        hilang begitu halaman baru (dashboard) selesai dimuat menimpanya —
        tidak perlu di-JS-matikan manual.
    --}}
    <div
        id="ft-login-loading"
        class="pointer-events-none fixed inset-0 z-50 flex flex-col items-center justify-center gap-4 bg-zinc-50 opacity-0 transition-opacity duration-300 dark:bg-zinc-900"
    >
        <span class="flex size-16 items-center justify-center overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-800">
            <img src="{{ asset('images/logo-unsri.png') }}" alt="" class="size-11 object-contain" />
        </span>
        <svg class="size-6 animate-spin text-brand-600 dark:text-brand-400" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
        <flux:text class="!text-zinc-500 dark:!text-zinc-400">{{ __('Menyiapkan dashboard...') }}</flux:text>
    </div>
    <script>
        window.__ftShowLoginLoading = function () {
            var el = document.getElementById('ft-login-loading');
            if (!el) return;
            el.classList.remove('pointer-events-none');
            el.style.opacity = '1';
        };
    </script>
</x-layouts::auth>
