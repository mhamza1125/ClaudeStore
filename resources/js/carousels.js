// Homepage banner slider and featured-products carousel. Both pause on hover, keyboard
// focus, touch and hidden tabs, and turn autoplay off for visitors who prefer reduced motion.
const reducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

export function registerCarousels(Alpine) {
    // Autoplay is driven by the CSS progress animation on the active dot (see .hero-progress):
    // its animationend advances the slide, so pausing the animation pauses autoplay.
    Alpine.data('heroBanner', (count, seconds) => ({
        active: 0,
        count,
        interval: reducedMotion() || count < 2 ? 0 : seconds,
        hover: false,
        focused: false,
        paused: false,
        hidden: document.hidden,
        touchX: null,
        init() {
            document.addEventListener('visibilitychange', () => { this.hidden = document.hidden; });
        },
        isPaused() { return this.paused || this.hover || this.focused || this.hidden; },
        go(i) { this.active = (i + this.count) % this.count; },
        next(auto = false) {
            if (auto && (!this.interval || this.isPaused())) return;
            this.go(this.active + 1);
        },
        prev() { this.go(this.active - 1); },
        touchStart(e) { this.touchX = e.changedTouches[0].clientX; },
        touchEnd(e) {
            if (this.touchX === null) return;
            const dx = e.changedTouches[0].clientX - this.touchX;
            this.touchX = null;
            if (Math.abs(dx) > 50) (dx < 0 ? this.next() : this.prev());
        },
    }));

    // Native horizontal scroll-snap track (touch swipe works without JS); JS adds arrows,
    // gentle autoplay one card at a time, looping back to the start, and a progress bar.
    Alpine.data('productCarousel', (seconds) => ({
        interval: reducedMotion() ? 0 : seconds,
        hover: false,
        focused: false,
        holdUntil: 0,
        progress: 0,
        atStart: true,
        atEnd: false,
        timer: null,
        init() {
            const track = this.$refs.track;
            track.addEventListener('scroll', () => this.update(), { passive: true });
            window.addEventListener('resize', () => this.update(), { passive: true });
            this.update();
            if (this.interval > 0) {
                this.timer = setInterval(() => {
                    if (!this.hover && !this.focused && !document.hidden && Date.now() > this.holdUntil) this.next(true);
                }, this.interval * 1000);
            }
        },
        destroy() { clearInterval(this.timer); },
        behavior() { return reducedMotion() ? 'auto' : 'smooth'; },
        step() {
            const track = this.$refs.track;
            const card = track.querySelector('[data-card]');
            const gap = parseFloat(getComputedStyle(track).columnGap) || 0;
            return card ? card.getBoundingClientRect().width + gap : track.clientWidth;
        },
        next(auto = false) {
            const t = this.$refs.track;
            if (this.atEnd) t.scrollTo({ left: 0, behavior: this.behavior() });
            else t.scrollBy({ left: this.step(), behavior: this.behavior() });
            if (!auto) this.hold();
        },
        prev() {
            const t = this.$refs.track;
            if (this.atStart) t.scrollTo({ left: t.scrollWidth, behavior: this.behavior() });
            else t.scrollBy({ left: -this.step(), behavior: this.behavior() });
            this.hold();
        },
        // After a manual interaction, wait before moving again.
        hold() { this.holdUntil = Date.now() + 10000; },
        update() {
            const t = this.$refs.track;
            const max = t.scrollWidth - t.clientWidth;
            this.atStart = t.scrollLeft <= 4;
            this.atEnd = t.scrollLeft >= max - 4;
            this.progress = max > 0 ? Math.min(1, t.scrollLeft / max) : 1;
        },
    }));
}
