(function () {
    const DEFAULT_START = 50;

    class ImageComparison {
        constructor(container) {
            this.container = container;
            this.after = container.querySelector('.comparison-after');
            this.handle = container.querySelector('.comparison-handle');
            this.dragging = false;
            this.activePointerId = null;

            if (!this.after || !this.handle) return;

            const start = parseFloat(container.dataset.start || String(DEFAULT_START));
            this.setPosition(Number.isFinite(start) ? start : DEFAULT_START);
            this.bindEvents();
        }

        bindEvents() {
            const startDrag = (event) => {
                this.dragging = true;
                this.container.classList.add('is-dragging');

                if (event.pointerId !== undefined) {
                    this.activePointerId = event.pointerId;
                    this.container.setPointerCapture?.(event.pointerId);
                }

                this.updateFromEvent(event);
            };

            const moveDrag = (event) => {
                if (!this.dragging) return;
                if (this.activePointerId !== null && event.pointerId !== undefined && event.pointerId !== this.activePointerId) return;
                this.updateFromEvent(event);
            };

            const endDrag = (event) => {
                if (!this.dragging) return;
                this.dragging = false;
                this.container.classList.remove('is-dragging');

                if (event.pointerId !== undefined) {
                    this.container.releasePointerCapture?.(event.pointerId);
                }

                this.activePointerId = null;
            };

            this.container.addEventListener('pointerdown', startDrag);
            window.addEventListener('pointermove', moveDrag);
            window.addEventListener('pointerup', endDrag);
            window.addEventListener('pointercancel', endDrag);

            if (!window.PointerEvent) {
                this.container.addEventListener('touchstart', (event) => {
                    event.preventDefault();
                    this.dragging = true;
                    this.updateFromEvent(event.touches[0]);
                }, { passive: false });

                this.container.addEventListener('touchmove', (event) => {
                    if (!this.dragging || !event.touches[0]) return;
                    event.preventDefault();
                    this.updateFromEvent(event.touches[0]);
                }, { passive: false });

                this.container.addEventListener('touchend', () => {
                    this.dragging = false;
                });
            }

            this.handle.addEventListener('keydown', (event) => {
                const current = parseFloat(this.handle.getAttribute('aria-valuenow') || '50');
                if (event.key === 'ArrowLeft') {
                    event.preventDefault();
                    this.setPosition(current - 2);
                }
                if (event.key === 'ArrowRight') {
                    event.preventDefault();
                    this.setPosition(current + 2);
                }
            });
        }

        updateFromEvent(event) {
            const rect = this.container.getBoundingClientRect();
            const clientX = event.clientX;
            if (typeof clientX !== 'number') return;
            const relative = ((clientX - rect.left) / rect.width) * 100;
            this.setPosition(relative);
        }

        setPosition(percent) {
            const clamped = Math.min(100, Math.max(0, percent));
            this.after.style.width = clamped + '%';
            this.handle.style.left = clamped + '%';
            this.handle.setAttribute('aria-valuenow', String(Math.round(clamped)));
        }
    }

    window.initImageComparison = function initImageComparison() {
        document.querySelectorAll('.image-comparison').forEach((container) => {
            if (!container.dataset.comparisonReady) {
                container.dataset.comparisonReady = '1';
                new ImageComparison(container);
            }
        });
    };
})();
