<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Scan QR')] class extends Component {
    //
}; ?>

<div class="space-y-6">
    <x-ui.page-header
        :title="__('Scan QR')"
        :subtitle="__('Arahkan kamera ke label QR di barang untuk buka detailnya')"
    />

    @assets
        @vite('resources/js/scan.js')
    @endassets

    <div
        class="mx-auto max-w-md space-y-4"
        x-data="{
            status: 'idle',
            errorMessage: '',
            scanner: null,
            manualCode: '',
            origin: '{{ url('/') }}',
            cameras: [],
            activeCameraId: '',

            async mulai() {
                this.status = 'starting';
                this.errorMessage = '';

                if (! window.QrScanner) {
                    await new Promise((resolve) => window.addEventListener('qr-scanner:ready', resolve, { once: true }));
                }

                const adaKamera = await window.QrScanner.hasCamera();

                if (! adaKamera) {
                    this.status = 'no-camera';
                    return;
                }

                this.scanner = new window.QrScanner(
                    this.$refs.video,
                    (result) => this.handleDecode(result.data),
                    {
                        returnDetailedScanResult: true,
                        highlightScanRegion: true,
                        highlightCodeOutline: true,
                        preferredCamera: 'environment',
                    },
                );

                try {
                    await this.scanner.start();
                    this.status = 'scanning';

                    /* Laptop/PC sering punya lebih dari satu kamera (mis.
                       webcam biasa + kamera IR bawaan Windows Hello). Kalau
                       scanner salah pilih (gambar hitam/kosong tapi status
                       sudah scanning), munculkan pemilih supaya pengguna
                       bisa ganti sendiri. qr-scanner tidak punya API publik
                       untuk baca device ID kamera yang otomatis terpilih —
                       jadi field activeCameraId di bawah cuma dipakai untuk
                       pilihan pengguna berikutnya (ganti kamera), bukan
                       menampilkan device yang sedang aktif. */
                    this.cameras = await window.QrScanner.listCameras(true);
                } catch (e) {
                    this.status = 'denied';
                    this.errorMessage = e.message || '{{ __('Tidak bisa mengakses kamera.') }}';
                }
            },

            async gantiKamera() {
                if (! this.scanner || ! this.activeCameraId) return;
                await this.scanner.setCamera(this.activeCameraId);
            },

            berhenti() {
                this.scanner?.stop();
                this.status = 'idle';
                this.cameras = [];
            },

            resolveTarget(text) {
                const value = text.trim();

                /* QR Web: URL penuh ke sistem ini — dipakai apa adanya. */
                if (value.startsWith(this.origin)) {
                    return value;
                }

                /* URL dari domain lain sama sekali bukan QR internal. */
                if (/^[a-z][a-z0-9+.-]*:\/\//i.test(value)) {
                    return null;
                }

                /* QR Token (dan input token manual): raw token, baik format
                   lama maupun UUID baru — arahkan ke halaman publik barang. */
                return `{{ url('/inventaris') }}/${encodeURIComponent(value)}`;
            },

            handleDecode(text) {
                const target = this.resolveTarget(text);

                if (! target) {
                    this.errorMessage = '{{ __('Kode QR ini bukan dari sistem FT Inventaris.') }}';
                    return;
                }

                this.scanner?.stop();
                this.status = 'found';
                window.location.href = target;
            },

            kirimManual() {
                if (! this.manualCode) return;

                const target = this.resolveTarget(this.manualCode);

                if (target) {
                    window.location.href = target;
                }
            },
        }"
        x-on:livewire:navigating.window="scanner?.stop()"
    >
        <flux:card class="!border-zinc-300 !shadow-md dark:!border-zinc-700">
            <div class="relative aspect-square overflow-hidden rounded-xl bg-zinc-950">
                {{--
                    PENTING: video TIDAK BOLEH pakai x-show/x-if untuk
                    sembunyi-tampil. qr-scanner (lihat mulai() di atas)
                    mengecek saat konstruksi apakah video sedang
                    display:none — kalau iya, dia "menyelamatkan" dengan
                    memaksa video jadi opacity:0 + width/height:0 PERMANEN
                    (workaround bawaan library untuk Safari yang suka stop
                    playback pada video tersembunyi). Scanner dibuat saat
                    status masih 'starting' (sebelum 'scanning'), jadi kalau
                    video di-x-show, race condition ini gampang kena —
                    hasilnya kamera "jalan" (status scanning, tidak ada
                    error) tapi preview-nya hitam permanen. Solusinya:
                    video SELALU ada & terlihat, placeholder ditumpuk di
                    atasnya (bukan sebaliknya).
                --}}
                <video x-ref="video" autoplay muted playsinline class="size-full object-cover"></video>

                <div x-show="status !== 'scanning' && status !== 'found'" class="absolute inset-0 flex flex-col items-center justify-center gap-3 bg-zinc-950 p-6 text-center">
                    <flux:icon name="qr-code" class="size-10 text-zinc-600" />

                    <template x-if="status === 'idle' || status === 'starting'">
                        <flux:text class="!text-zinc-400">{{ __('Kamera belum aktif') }}</flux:text>
                    </template>
                    <template x-if="status === 'denied'">
                        <flux:text class="!text-red-400" x-text="errorMessage"></flux:text>
                    </template>
                    <template x-if="status === 'no-camera'">
                        <flux:text class="!text-red-400">{{ __('Tidak ada kamera terdeteksi di perangkat ini.') }}</flux:text>
                    </template>
                </div>
            </div>

            <div class="mt-4 flex justify-center gap-2">
                <flux:button x-show="status === 'idle' || status === 'denied' || status === 'no-camera'" variant="primary" icon="qr-code" x-on:click="mulai()">
                    {{ __('Mulai Scan') }}
                </flux:button>
                <flux:button x-show="status === 'starting'" variant="primary" disabled>
                    {{ __('Membuka kamera...') }}
                </flux:button>
                <flux:button x-show="status === 'scanning'" variant="ghost" icon="x-mark" x-on:click="berhenti()">
                    {{ __('Berhenti') }}
                </flux:button>
            </div>

            <flux:text x-show="errorMessage && status === 'scanning'" x-text="errorMessage" class="mt-2 text-center !text-red-500"></flux:text>

            {{--
                Muncul saat status "scanning" dan perangkat punya lebih dari
                satu kamera — kasus umum di laptop Windows (webcam biasa +
                kamera IR Windows Hello). Kalau gambar hitam padahal status
                sudah scanning, kemungkinan besar kamera yang aktif salah;
                pengguna bisa pilih manual di sini.
            --}}
            <div x-show="status === 'scanning' && cameras.length > 1" class="mt-3">
                <flux:select x-model="activeCameraId" x-on:change="gantiKamera()" size="sm" :label="__('Kamera')">
                    <template x-for="camera in cameras" :key="camera.id">
                        <flux:select.option x-bind:value="camera.id" x-text="camera.label || 'Kamera'"></flux:select.option>
                    </template>
                </flux:select>
                <flux:text size="sm" class="mt-1 !text-zinc-400">
                    {{ __('Gambar hitam? Coba ganti kamera di sini — laptop sering punya kamera IR selain webcam biasa.') }}
                </flux:text>
            </div>
        </flux:card>

        <flux:card class="!border-zinc-300 !shadow-md dark:!border-zinc-700">
            <flux:heading size="sm" class="mb-3">{{ __('Atau masukkan manual') }}</flux:heading>

            <form x-on:submit.prevent="kirimManual()" class="flex gap-2">
                <flux:input x-model="manualCode" :placeholder="__('ID barang atau tautan QR')" class="min-w-0 flex-1" />
                <flux:button type="submit" icon="arrow-right" class="shrink-0" :aria-label="__('Buka')" />
            </form>
        </flux:card>
    </div>
</div>
