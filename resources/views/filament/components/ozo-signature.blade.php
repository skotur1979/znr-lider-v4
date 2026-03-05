{{-- resources/views/filament/components/ozo-signature.blade.php --}}
@php
    $statePath = $getStatePath();
    $uid = 'sig_' . preg_replace('/[^a-z0-9_]/i', '_', $statePath);
@endphp

<div
    x-data="ozoSignature()"
    x-init="init($refs.canvas, $refs.state)"
    style="display:flex; flex-direction:column; gap:10px;"
>
    <div style="width:100%; max-width:780px; height:240px; background:#fff; border:1px solid #6b7280; border-radius:10px; overflow:hidden;">
        <canvas
            x-ref="canvas"
            id="{{ $uid }}_canvas"
            style="width:100%; height:100%; display:block; touch-action:none; user-select:none; pointer-events:auto;"
        ></canvas>
    </div>

    <div style="display:flex; flex-wrap:wrap; gap:8px;">
        <x-filament::button type="button" color="gray" size="sm" x-on:click="clear()">Obriši</x-filament::button>
        <x-filament::button type="button" color="gray" size="sm" x-on:click="download('png')">Spremi PNG</x-filament::button>
        <x-filament::button type="button" color="gray" size="sm" x-on:click="download('jpg')">Spremi JPG</x-filament::button>
        <x-filament::button type="button" color="gray" size="sm" x-on:click="download('svg')">Spremi SVG</x-filament::button>
    </div>

    <input x-ref="state" type="hidden" id="{{ $uid }}_state" wire:model.defer="{{ $statePath }}" />
</div>