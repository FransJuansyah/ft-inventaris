@props([
    'sidebar' => false,
])

@php
    $classes = $sidebar
        ? 'h-10 min-w-0 flex items-center gap-2 px-2 in-data-flux-sidebar-collapsed-desktop:w-10 in-data-flux-sidebar-collapsed-desktop:px-0 in-data-flux-sidebar-collapsed-desktop:justify-center'
        : 'h-10 flex items-center gap-2 me-4';
@endphp

<a {{ $attributes->class($classes) }} data-flux-sidebar-brand>
    <div class="flex h-7 w-7 shrink-0 items-center justify-center overflow-hidden rounded-sm">
        <img
            src="{{ asset('images/logo-unsri.png') }}"
            alt="{{ __('Universitas Sriwijaya') }}"
            class="h-7 w-7 object-contain"
        />
    </div>

    <div @class(['min-w-0 leading-tight', 'in-data-flux-sidebar-collapsed-desktop:hidden' => $sidebar])>
        <div class="truncate text-sm font-semibold text-zinc-800 dark:text-zinc-100">
            {{ config('app.name', 'Laravel') }}
        </div>
        <div class="truncate text-[11px] text-zinc-500 dark:text-zinc-400">
            {{ __('Fakultas Teknik Unsri') }}
        </div>
    </div>
</a>
