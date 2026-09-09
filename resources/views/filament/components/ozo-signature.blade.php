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
        resizeObserver: null,

        initSignature() {
            const canvas = this.$refs.canvas;

            if (! canvas) {
                return;
            }

            this.ctx = canvas.getContext('2d');

            /*
             * Filament modal se prilikom otvaranja animira.
             * Zato čekamo da se modal smjesti na konačnu veličinu.
             */
            this.$nextTick(() => {
                requestAnimationFrame(() => {
                    requestAnimationFrame(() => {
                        this.resizeCanvas();
                    });
                });
            });

            /*
             * Ako Filament modal ili browser promijeni širinu,
             * ponovno uskladi canvas.
             */
            this.resizeObserver = new ResizeObserver(() => {
                if (! this.drawing) {
                    this.resizeCanvas();
                }
            });

            this.resizeObserver.observe(canvas);
        },

        configureContext() {
            if (! this.ctx) {
                return;
            }

            const ratio = window.devicePixelRatio || 1;

            this.ctx.lineWidth = 4 * ratio;
            this.ctx.lineCap = 'round';
            this.ctx.lineJoin = 'round';
            this.ctx.strokeStyle = '#111827';
            this.ctx.imageSmoothingEnabled = true;
        },

        resizeCanvas() {
            const canvas = this.$refs.canvas;

            if (! canvas || ! this.ctx) {
                return;
            }

            /*
             * clientWidth/clientHeight koriste stvarnu layout veličinu
             * bez problema koje mogu napraviti CSS transformacije modala.
             */
            const cssWidth = canvas.clientWidth;
            const cssHeight = canvas.clientHeight;

            if (
                cssWidth <= 0
                || cssHeight <= 0
            ) {
                return;
            }

            const ratio = window.devicePixelRatio || 1;

            /*
             * Sačuvaj trenutni potpis prije resizea.
             */
            let previousImage = null;

            if (
                canvas.width > 0
                && canvas.height > 0
            ) {
                try {
                    previousImage =
                        canvas.toDataURL('image/png');
                } catch (e) {
                    previousImage = null;
                }
            }

            canvas.width =
                Math.round(cssWidth * ratio);

            canvas.height =
                Math.round(cssHeight * ratio);

            /*
             * Ne koristimo ctx.scale() niti setTransform().
             * Koordinate pretvaramo direktno u interne piksele.
             */
            this.ctx.setTransform(
                1,
                0,
                0,
                1,
                0,
                0
            );

            this.configureContext();

            /*
             * Vrati prethodno nacrtani potpis ako postoji.
             */
            if (
                previousImage
                && previousImage !== 'data:,'
            ) {
                const image = new Image();

                image.onload = () => {
                    this.ctx.drawImage(
                        image,
                        0,
                        0,
                        canvas.width,
                        canvas.height
                    );
                };

                image.src = previousImage;
            }
        },

        getPosition(event) {
            const canvas = this.$refs.canvas;

            const rect =
                canvas.getBoundingClientRect();

            /*
             * Ovo je ključna ispravka.
             *
             * clientX/clientY su stvarni položaj pokazivača na ekranu.
             * Zatim ih pretvaramo u stvarnu internu koordinatu canvasa.
             *
             * Time Filament modal, CSS scale, browser zoom i DPI
             * više ne mogu pomaknuti potpis.
             */
            const scaleX =
                canvas.width / rect.width;

            const scaleY =
                canvas.height / rect.height;

            return {
                x:
                    (event.clientX - rect.left)
                    * scaleX,

                y:
                    (event.clientY - rect.top)
                    * scaleY,
            };
        },

        start(event) {
            event.preventDefault();

            const canvas = this.$refs.canvas;

            if (
                event.pointerId !== undefined
            ) {
                try {
                    canvas.setPointerCapture(
                        event.pointerId
                    );
                } catch (e) {}
            }

            const pos =
                this.getPosition(event);

            this.drawing = true;

            this.ctx.beginPath();

            this.ctx.moveTo(
                pos.x,
                pos.y
            );
        },

        draw(event) {
            if (! this.drawing) {
                return;
            }

            event.preventDefault();

            const pos =
                this.getPosition(event);

            this.ctx.lineTo(
                pos.x,
                pos.y
            );

            this.ctx.stroke();
        },

        stop(event = null) {
            if (! this.drawing) {
                return;
            }

            this.drawing = false;

            this.ctx.closePath();

            /*
             * Automatski spremi potpis u Filament / Livewire stanje
             * kada korisnik digne miš ili prst.
             */
            this.saveSignature();

            if (
                event
                && event.pointerId !== undefined
            ) {
                try {
                    this.$refs.canvas
                        .releasePointerCapture(
                            event.pointerId
                        );
                } catch (e) {}
            }
        },

        saveSignature() {
            const canvas =
                this.$refs.canvas;

            this.state =
                canvas.toDataURL(
                    'image/png'
                );

            this.$wire.set(
                '{{ $statePath }}',
                this.state
            );
        },

        clear() {
            const canvas =
                this.$refs.canvas;

            if (! canvas || ! this.ctx) {
                return;
            }

            this.ctx.clearRect(
                0,
                0,
                canvas.width,
                canvas.height
            );

            this.state = null;

            this.$wire.set(
                '{{ $statePath }}',
                null
            );
        },
    }"

    x-init="initSignature()"

    style="
        display:flex;
        flex-direction:column;
        gap:10px;
    "
>
    <div
        style="
            width:100%;
            max-width:780px;
            height:240px;
            background:#ffffff;
            border:1px solid #6b7280;
            border-radius:10px;
            overflow:hidden;
        "
    >
        <canvas
            x-ref="canvas"

            id="{{ $uid }}_canvas"

            style="
                width:100%;
                height:100%;
                display:block;
                background:#ffffff;
                touch-action:none;
                user-select:none;
                pointer-events:auto;
                cursor:crosshair;
            "

            @pointerdown="start($event)"
            @pointermove="draw($event)"
            @pointerup="stop($event)"
            @pointercancel="stop($event)"
        ></canvas>
    </div>

    <div>
        <x-filament::button
            type="button"
            color="gray"
            size="sm"
            x-on:click="clear()"
        >
            Obriši potpis
        </x-filament::button>
    </div>

    <div
        style="
            font-size:0.875rem;
            color:#6b7280;
        "
    >
        Potpis se automatski sprema zajedno s OZO.
    </div>
</div>