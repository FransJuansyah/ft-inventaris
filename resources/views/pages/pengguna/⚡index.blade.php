<?php

use App\Enums\PeranPengguna;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Pengguna')] class extends Component {
    use WithPagination;

    #[Url]
    public string $cari = '';

    #[Url]
    public string $peran = '';

    public ?int $editId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $role = '';

    public string $nip = '';

    public string $no_hp = '';

    public string $no_wa = '';

    public ?int $nonaktifId = null;

    public ?int $deleteId = null;

    public function updatingCari(): void
    {
        $this->resetPage();
    }

    public function updatingPeran(): void
    {
        $this->resetPage();
    }

    public function tambah(): void
    {
        $this->reset('editId', 'name', 'email', 'password', 'role', 'nip', 'no_hp', 'no_wa');
        $this->role = PeranPengguna::Petugas->value;
        $this->resetValidation();
        Flux::modal('pengguna-form')->show();
    }

    public function edit(int $id): void
    {
        $user = User::findOrFail($id);

        $this->editId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->role = $user->role->value;
        $this->nip = (string) $user->nip;
        $this->no_hp = (string) $user->no_hp;
        $this->no_wa = (string) $user->no_wa;
        $this->resetValidation();

        Flux::modal('pengguna-form')->show();
    }

    public function simpan(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editId)],
            'password' => [$this->editId ? 'nullable' : 'required', 'string', 'min:8'],
            'role' => ['required', Rule::enum(PeranPengguna::class)],
            'nip' => ['nullable', 'string', 'max:50'],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'no_wa' => ['nullable', 'string', 'max:20'],
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        } else {
            $validated['password'] = Hash::make($validated['password']);
        }

        User::updateOrCreate(['id' => $this->editId], $validated);

        Flux::modal('pengguna-form')->close();
        Flux::toast(variant: 'success', text: __('Pengguna berhasil disimpan.'));

        $this->reset('editId', 'name', 'email', 'password', 'role', 'nip', 'no_hp', 'no_wa');
    }

    public function konfirmasiNonaktifkan(int $id): void
    {
        $this->nonaktifId = $id;
        Flux::modal('pengguna-ban')->show();
    }

    public function toggleAktif(): void
    {
        $user = User::findOrFail($this->nonaktifId);

        if ($user->is_active) {
            $user->deactivate(auth()->user());
        } else {
            $user->activate();
        }

        Flux::modal('pengguna-ban')->close();
        Flux::toast(variant: 'success', text: $user->is_active ? __('Blokir pengguna dibuka.') : __('Pengguna diblokir.'));

        $this->nonaktifId = null;
    }

    public function konfirmasiHapus(int $id): void
    {
        $this->deleteId = $id;
        Flux::modal('pengguna-hapus')->show();
    }

    public function hapus(): void
    {
        if ($this->deleteId === auth()->id()) {
            Flux::modal('pengguna-hapus')->close();
            Flux::toast(variant: 'danger', text: __('Tidak bisa menghapus akun sendiri.'));
            $this->deleteId = null;

            return;
        }

        User::findOrFail($this->deleteId)->delete();

        Flux::modal('pengguna-hapus')->close();
        Flux::toast(variant: 'success', text: __('Pengguna berhasil dihapus.'));

        $this->deleteId = null;
        $this->resetPage();
    }

    public function with(): array
    {
        return [
            'penggunaList' => User::query()
                ->when($this->cari, fn ($query) => $query
                    ->where('name', 'like', "%{$this->cari}%")
                    ->orWhere('email', 'like', "%{$this->cari}%"))
                ->when($this->peran, fn ($query) => $query->where('role', $this->peran))
                ->orderBy('name')
                ->paginate(10),
            'peranOptions' => PeranPengguna::options(),
        ];
    }
}; ?>

<div class="space-y-6">
    <x-ui.page-header :title="__('Pengguna')" :subtitle="__('Kelola akun admin dan petugas lapangan')">
        <x-slot:actions>
            <flux:button variant="primary" icon="plus" wire:click="tambah">
                {{ __('Tambah Pengguna') }}
            </flux:button>
        </x-slot:actions>
    </x-ui.page-header>

    <flux:card class="space-y-4 !border-zinc-300 !shadow-md dark:!border-zinc-700">
        <x-ui.toolbar>
            <x-slot:search>
                <flux:input wire:model.live.debounce.300ms="cari" icon="magnifying-glass" :placeholder="__('Cari nama atau email...')" />
            </x-slot:search>

            <flux:select wire:model.live="peran" :placeholder="__('Semua peran')">
                <flux:select.option value="">{{ __('Semua peran') }}</flux:select.option>
                @foreach ($peranOptions as $value => $label)
                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
        </x-ui.toolbar>

        @if ($penggunaList->isEmpty())
            <x-ui.empty-state
                icon="users"
                :heading="$cari || $peran ? __('Tidak ditemukan') : __('Belum ada pengguna')"
                :description="$cari || $peran ? __('Coba kata kunci atau filter lain.') : __('Tambahkan admin atau petugas pertama.')"
            >
                @unless ($cari || $peran)
                    <flux:button variant="primary" icon="plus" wire:click="tambah">
                        {{ __('Tambah Pengguna') }}
                    </flux:button>
                @endunless
            </x-ui.empty-state>
        @else
            <div class="hidden sm:block">
                <flux:table :paginate="$penggunaList">
                    <flux:table.columns>
                        <flux:table.column>{{ __('Nama') }}</flux:table.column>
                        <flux:table.column>{{ __('Email') }}</flux:table.column>
                        <flux:table.column>{{ __('Peran') }}</flux:table.column>
                        <flux:table.column>{{ __('Status') }}</flux:table.column>
                        <flux:table.column align="end">{{ __('Aksi') }}</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($penggunaList as $user)
                            <flux:table.row :key="$user->id">
                                <flux:table.cell variant="strong">{{ $user->name }}</flux:table.cell>
                                <flux:table.cell>{{ $user->email }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge :color="$user->role->fluxColor()" size="sm">{{ $user->role->label() }}</flux:badge>
                                </flux:table.cell>
                                <flux:table.cell>
                                    @if ($user->is_active)
                                        <flux:badge color="green" size="sm">{{ __('Aktif') }}</flux:badge>
                                    @else
                                        <flux:badge color="red" size="sm">{{ __('Nonaktif') }}</flux:badge>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell align="end">
                                    <div class="flex justify-end gap-1">
                                        <flux:button size="sm" variant="ghost" :icon="$user->is_active ? 'lock-closed' : 'lock-open'" wire:click="konfirmasiNonaktifkan({{ $user->id }})" :aria-label="__('Blokir/buka')" />
                                        <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="edit({{ $user->id }})" :aria-label="__('Ubah')" />
                                        <flux:button size="sm" variant="ghost" icon="trash" wire:click="konfirmasiHapus({{ $user->id }})" :aria-label="__('Hapus')" />
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>

            <div class="space-y-2 sm:hidden">
                @foreach ($penggunaList as $user)
                    <x-ui.list-card wire:key="card-{{ $user->id }}" :title="$user->name" :subtitle="$user->email" icon="user">
                        <x-slot:trailing>
                            <flux:button size="sm" variant="ghost" :icon="$user->is_active ? 'lock-closed' : 'lock-open'" wire:click="konfirmasiNonaktifkan({{ $user->id }})" :aria-label="__('Blokir/buka')" />
                            <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="edit({{ $user->id }})" :aria-label="__('Ubah')" />
                            <flux:button size="sm" variant="ghost" icon="trash" wire:click="konfirmasiHapus({{ $user->id }})" :aria-label="__('Hapus')" />
                        </x-slot:trailing>
                    </x-ui.list-card>
                @endforeach

                <flux:pagination :paginator="$penggunaList" class="pt-2" />
            </div>
        @endif
    </flux:card>

    <flux:modal name="pengguna-form" class="w-full max-w-md" @close="$set('editId', null)">
        <form wire:submit="simpan" class="space-y-6">
            <flux:heading size="lg">
                {{ $editId ? __('Ubah Pengguna') : __('Tambah Pengguna') }}
            </flux:heading>

            <flux:input wire:model="name" :label="__('Nama')" autofocus />
            <flux:input wire:model="email" type="email" :label="__('Email')" />
            <flux:input wire:model="password" type="password" :label="__('Kata Sandi')" :description="$editId ? __('Kosongkan jika tidak ingin mengubah.') : null" />

            <flux:select wire:model="role" :label="__('Peran')">
                @foreach ($peranOptions as $value => $label)
                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model="nip" :label="__('NIP')" />
            <flux:input wire:model="no_hp" :label="__('No. HP')" />
            <flux:input wire:model="no_wa" :label="__('No. WhatsApp')" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Batal') }}</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary">{{ __('Simpan') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="pengguna-ban" class="w-full max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Ubah status blokir pengguna ini?') }}</flux:heading>
                <flux:subheading>
                    {{ __('Pengguna yang diblokir tidak bisa masuk ke sistem.') }}
                </flux:subheading>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Batal') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="toggleAktif">{{ __('Konfirmasi') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="pengguna-hapus" class="w-full max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Hapus pengguna ini?') }}</flux:heading>
                <flux:subheading>
                    {{ __('Tindakan ini tidak bisa dibatalkan.') }}
                </flux:subheading>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Batal') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="hapus">{{ __('Hapus') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
