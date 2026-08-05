{{--
    Badge kondisi barang. Menerima enum KondisiBarang, nilai integer-nya
    (0/1/2/9), atau null untuk barang yang belum punya riwayat status.

    <x-ui.status-badge :kondisi="$barang->kondisiTerkini" />
    <x-ui.status-badge :kondisi="0" size="sm" />
--}}
@props([
    'kondisi' => null,
    'size' => null,
    'icon' => true,
])

@php
    $kondisi = $kondisi instanceof \App\Enums\KondisiBarang
        ? $kondisi
        : (is_null($kondisi) ? null : \App\Enums\KondisiBarang::tryFrom((int) $kondisi));
@endphp

@if ($kondisi)
    <flux:badge
        :color="$kondisi->fluxColor()"
        :icon="$icon ? $kondisi->icon() : null"
        :size="$size"
        :attributes="$attributes"
    >
        {{ $kondisi->label() }}
    </flux:badge>
@else
    <flux:badge color="zinc" variant="subtle" :size="$size" :attributes="$attributes">
        {{ __('Belum ada status') }}
    </flux:badge>
@endif
