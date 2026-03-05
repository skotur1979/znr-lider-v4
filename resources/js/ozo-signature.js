// resources/js/ozo-signature.js
export function ozoSignature() {
    return {
        canvas: null,
        stateInput: null,
        ctx: null,
        drawing: false,
        lastX: 0,
        lastY: 0,
        dpr: 1,

        init(canvasEl, stateInputEl) {
            this.canvas = canvasEl;
            this.stateInput = stateInputEl;

            if (!this.canvas || !this.stateInput) return;

            this.ctx = this.canvas.getContext('2d', { willReadFrequently: true });

            // pointer events (mouse + touch + pen)
            this.canvas.addEventListener('pointerdown', (e) => this.onDown(e));
            this.canvas.addEventListener('pointermove', (e) => this.onMove(e));
            this.canvas.addEventListener('pointerup', (e) => this.onUp(e));
            this.canvas.addEventListener('pointercancel', (e) => this.onUp(e));
            this.canvas.addEventListener('pointerleave', (e) => this.onUp(e));

            // spriječi scroll na touch
            this.canvas.style.touchAction = 'none';

            this.resize();
            window.addEventListener('resize', () => this.resize());

            // ako već ima spremljeno u state-u (edit), nacrtaj
            this.redrawFromState();
        },

        resize() {
            if (!this.canvas || !this.ctx) return;

            this.dpr = Math.max(window.devicePixelRatio || 1, 1);
            const rect = this.canvas.getBoundingClientRect();

            this.canvas.width = Math.floor(rect.width * this.dpr);
            this.canvas.height = Math.floor(rect.height * this.dpr);

            // reset transform + scale
            this.ctx.setTransform(this.dpr, 0, 0, this.dpr, 0, 0);

            // stil olovke
            this.ctx.lineCap = 'round';
            this.ctx.lineJoin = 'round';
            this.ctx.strokeStyle = '#111827';
            this.ctx.lineWidth = 2.5;

            // nakon resize-a vrati sadržaj
            this.redrawFromState();
        },

        pos(e) {
            const r = this.canvas.getBoundingClientRect();
            return { x: e.clientX - r.left, y: e.clientY - r.top };
        },

        dot(x, y) {
            this.ctx.beginPath();
            this.ctx.arc(x, y, this.ctx.lineWidth / 2, 0, Math.PI * 2);
            this.ctx.fillStyle = this.ctx.strokeStyle;
            this.ctx.fill();
        },

        line(x1, y1, x2, y2) {
            this.ctx.beginPath();
            this.ctx.moveTo(x1, y1);
            this.ctx.lineTo(x2, y2);
            this.ctx.stroke();
        },

        onDown(e) {
            e.preventDefault();
            this.canvas.setPointerCapture?.(e.pointerId);

            const p = this.pos(e);
            this.drawing = true;
            this.lastX = p.x;
            this.lastY = p.y;
            this.dot(p.x, p.y);
        },

        onMove(e) {
            if (!this.drawing) return;
            e.preventDefault();

            const p = this.pos(e);
            this.line(this.lastX, this.lastY, p.x, p.y);
            this.lastX = p.x;
            this.lastY = p.y;
        },

        onUp(e) {
            if (!this.drawing) return;
            e.preventDefault();
            this.drawing = false;
            this.saveToStateAsPng();
        },

        saveToStateAsPng() {
            // spremamo PNG s bijelom pozadinom
            const rect = this.canvas.getBoundingClientRect();
            const exportCanvas = document.createElement('canvas');
            exportCanvas.width = Math.floor(rect.width * this.dpr);
            exportCanvas.height = Math.floor(rect.height * this.dpr);

            const ectx = exportCanvas.getContext('2d');
            ectx.setTransform(this.dpr, 0, 0, this.dpr, 0, 0);

            ectx.fillStyle = '#ffffff';
            ectx.fillRect(0, 0, exportCanvas.width, exportCanvas.height);

            // nacrtaj postojeći canvas
            ectx.drawImage(this.canvas, 0, 0, exportCanvas.width / this.dpr, exportCanvas.height / this.dpr);

            const url = exportCanvas.toDataURL('image/png');
            this.stateInput.value = url;

            // trigger Livewire
            this.stateInput.dispatchEvent(new Event('input', { bubbles: true }));
            this.stateInput.dispatchEvent(new Event('change', { bubbles: true }));
        },

        clear() {
            if (!this.ctx) return;
            this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);

            this.stateInput.value = '';
            this.stateInput.dispatchEvent(new Event('input', { bubbles: true }));
            this.stateInput.dispatchEvent(new Event('change', { bubbles: true }));
        },

        redrawFromState() {
            const val = this.stateInput?.value;
            if (!val) return;

            const img = new Image();
            img.onload = () => {
                this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
                this.ctx.drawImage(img, 0, 0, this.canvas.width / this.dpr, this.canvas.height / this.dpr);
            };
            img.src = val;
        },

        download(ext) {
            let mime = 'image/png';
            if (ext === 'jpg') mime = 'image/jpeg';
            if (ext === 'svg') mime = 'image/svg+xml';

            const url = this.canvas.toDataURL(mime);
            const a = document.createElement('a');
            a.href = url;
            a.download = `potpis.${ext}`;
            a.click();
        },
    };
}