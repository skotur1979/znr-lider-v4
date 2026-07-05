{{-- resources/views/filament/components/ozo-signature.blade.php --}}
@php
    $statePath = $getStatePath();
    $uid = 'sig_' . preg_replace('/[^a-z0-9_]/i', '_', $statePath);
@endphp

<div
    x-data="{
        state: @entangle($statePath).live,
        drawing: false,
        ctx: null,

        initSignature() {
            const canvas = this.$refs.canvas;
            this.ctx = canvas.getContext('2d');

            this.resizeCanvas();

            this.ctx.lineWidth = 5;
            this.ctx.lineCap = 'round';
            this.ctx.lineJoin = 'round';
            this.ctx.strokeStyle = '#111827';
            this.ctx.imageSmoothingEnabled = true;
        },

        resizeCanvas() {
            const canvas = this.$refs.canvas;
            const rect = canvas.getBoundingClientRect();
            const ratio = window.devicePixelRatio || 1;

            canvas.width = rect.width * ratio;
            canvas.height = rect.height * ratio;

            this.ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
        },

        getPosition(event) {
            return {
                x: event.offsetX,
                y: event.offsetY,
            };
        },

        start(event) {
            event.preventDefault();

            if (event.pointerId !== undefined) {
                this.$refs.canvas.setPointerCapture(event.pointerId);
            }

            this.drawing = true;

            const pos = this.getPosition(event);

            this.ctx.beginPath();
            this.ctx.moveTo(pos.x, pos.y);
        },

        draw(event) {
            if (! this.drawing) {
                return;
            }

            event.preventDefault();

            if (event.pressure !== undefined && event.pressure === 0) {
                return;
            }

            const pos = this.getPosition(event);

            this.ctx.lineTo(pos.x, pos.y);
            this.ctx.stroke();

            this.state = this.$refs.canvas.toDataURL('image/png');
        },

        stop(event = null) {
            if (! this.drawing) {
                return;
            }

            this.drawing = false;
            this.state = this.$refs.canvas.toDataURL('image/png');
            this.$wire.set('{{ $statePath }}', this.state);

            if (event && event.pointerId !== undefined) {
                try {
                    this.$refs.canvas.releasePointerCapture(event.pointerId);
                } catch (e) {}
            }
        },

        clear() {
            this.ctx.clearRect(0, 0, this.$refs.canvas.width, this.$refs.canvas.height);
            this.state = null;
            this.$wire.set('{{ $statePath }}', null);
        },

        download(type) {
            this.stop();

            this.state = this.$refs.canvas.toDataURL(
                type === 'jpg' ? 'image/jpeg' : 'image/png',
                0.95
            );

            this.$wire.set('{{ $statePath }}', this.state);
        },
    }"
    x-init="initSignature()"
    style="display:flex; flex-direction:column; gap:10px;"
>
    <div style="width:100%; max-width:780px; height:240px; background:#fff; border:1px solid #6b7280; border-radius:10px; overflow:hidden;">
        <canvas
            x-ref="canvas"
            id="{{ $uid }}_canvas"
            style="width:100%; height:100%; display:block; touch-action:none; user-select:none; pointer-events:auto; cursor:crosshair;"
            @pointerdown="start($event)"
            @pointermove="draw($event)"
            @pointerup="stop($event)"
            @pointercancel="stop($event)"
            @pointerleave="stop($event)"
        ></canvas>
    </div>

    <div style="display:flex; flex-wrap:wrap; gap:8px;">
        <x-filament::button type="button" color="gray" size="sm" x-on:click="clear()">Obriši</x-filament::button>
        <x-filament::button type="button" color="gray" size="sm" x-on:click="download('png')">Spremi PNG</x-filament::button>
        <x-filament::button type="button" color="gray" size="sm" x-on:click="download('jpg')">Spremi JPG</x-filament::button>
        <x-filament::button type="button" color="gray" size="sm" x-on:click="download('svg')">Spremi SVG</x-filament::button>
    </div>
</div>