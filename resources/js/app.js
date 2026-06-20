import QRCode from 'qrcode';

// Alpine ships with Livewire; register our directive once it boots.
// Usage in Blade:  <div x-data="qrCanvas(@js($uri))"><canvas x-ref="canvas"></canvas></div>
document.addEventListener('alpine:init', () => {
    window.Alpine.data('qrCanvas', (text) => ({
        init() {
            QRCode.toCanvas(this.$refs.canvas, text, { width: 200, margin: 1 }, (err) => {
                if (err) console.error('QR render failed:', err);
            });
        },
    }));
});
