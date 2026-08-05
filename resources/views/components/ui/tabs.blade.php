{{--
    Pengganti flux:tabs (komponen Pro).

    Dipakai di detail barang untuk tiga bagian Detail / Status / Riwayat.
    Tab aktif disimpan di properti Livewire — beri atribut #[Url] pada
    properti itu supaya tab ikut tersimpan di URL dan bisa di-share.

    class ShowBarang extends Component
    {
        #[Url] public string $tab = 'detail';
    }

    <x-ui.tabs
        :tabs="['detail' => 'Detail', 'status' => 'Status', 'riwayat' => 'Riwayat']"
        :current="$tab"
        property="tab"
    />

    Tab bisa juga berupa tautan biasa dengan memberi nilai array
    ['label' => '...', 'href' => '...'] alih-alih string.
--}}
@props([
    'tabs' => [],
    'current' => null,
    'property' => 'tab',
])

<div {{ $attributes->class('border-b border-zinc-200 dark:border-zinc-700') }}>
    {{-- Bisa lebih lebar dari layar HP: biarkan menggulung mendatar. --}}
    <div class="-mb-px flex gap-1 overflow-x-auto" role="tablist">
        @foreach ($tabs as $value => $tab)
            @php
                $label = is_array($tab) ? ($tab['label'] ?? $value) : $tab;
                $href = is_array($tab) ? ($tab['href'] ?? null) : null;
                $badge = is_array($tab) ? ($tab['badge'] ?? null) : null;
                $active = (string) $value === (string) $current;

                $tabClasses = 'flex shrink-0 items-center gap-2 whitespace-nowrap border-b-2 px-4 py-2.5 text-sm font-medium transition '
                    . ($active
                        ? 'border-accent text-accent-content'
                        : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-800 dark:text-zinc-400 dark:hover:border-zinc-600 dark:hover:text-white');
            @endphp

            @if ($href)
                <a href="{{ $href }}" wire:navigate role="tab" aria-selected="{{ $active ? 'true' : 'false' }}" class="{{ $tabClasses }}">
                    {{ $label }}
                    @if (! is_null($badge))
                        <flux:badge size="sm" :color="$active ? 'green' : 'zinc'">{{ $badge }}</flux:badge>
                    @endif
                </a>
            @else
                <button
                    type="button"
                    role="tab"
                    aria-selected="{{ $active ? 'true' : 'false' }}"
                    wire:click="$set('{{ $property }}', '{{ $value }}')"
                    class="{{ $tabClasses }}"
                >
                    {{ $label }}
                    @if (! is_null($badge))
                        <flux:badge size="sm" :color="$active ? 'green' : 'zinc'">{{ $badge }}</flux:badge>
                    @endif
                </button>
            @endif
        @endforeach
    </div>
</div>
