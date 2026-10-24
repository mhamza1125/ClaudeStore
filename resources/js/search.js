// Header live search: debounced suggestions from /search/suggest. Stale requests are
// aborted, recent answers are cached, and Enter without a highlighted result submits the
// normal catalogue search.
const DEBOUNCE_MS = 250;
const MIN_CHARS = 2;

export function registerSearch(Alpine) {
    Alpine.data('searchSuggest', (url, id) => ({
        query: '',
        lastQuery: '',
        results: [],
        total: 0,
        allUrl: '#',
        state: 'idle', // idle | loading | results | empty | error
        open: false,
        active: -1,
        announcement: '',
        timer: null,
        controller: null,
        cache: new Map(),
        init() {
            this.query = this.$refs.input.value;
        },
        onInput() {
            clearTimeout(this.timer);
            const q = this.query.trim();
            if (q.length < MIN_CHARS) {
                this.controller?.abort();
                this.reset();
                return;
            }
            this.timer = setTimeout(() => this.fetch(q), DEBOUNCE_MS);
        },
        async fetch(q) {
            if (this.cache.has(q)) return this.show(q, this.cache.get(q));
            this.controller?.abort();
            this.controller = new AbortController();
            this.state = 'loading';
            this.open = true;
            try {
                const { data } = await window.axios.get(url, { params: { q }, signal: this.controller.signal });
                this.cache.set(q, data);
                if (this.query.trim() === q) this.show(q, data);
            } catch (e) {
                if (window.axios.isCancel?.(e) || e.name === 'CanceledError') return;
                this.results = [];
                this.state = 'error';
                this.announcement = 'Suggestions unavailable';
            }
        },
        show(q, data) {
            this.lastQuery = q;
            this.results = data.results || [];
            this.total = data.total || 0;
            this.allUrl = data.all_url || '#';
            this.active = -1;
            this.state = this.results.length ? 'results' : 'empty';
            this.open = true;
            this.announcement = this.results.length ? `${this.total} products found` : 'No products found';
        },
        move(step) {
            if (!this.open && this.results.length) this.open = true;
            if (!this.results.length) return;
            // Cycle through -1 (the input itself) and each result.
            const n = this.results.length;
            let next = this.active + step;
            if (next >= n) next = -1;
            else if (next < -1) next = n - 1;
            this.active = next;
            this.$nextTick(() => document.getElementById(`${id}-option-${this.active}`)?.scrollIntoView({ block: 'nearest' }));
        },
        onSubmit(event) {
            if (this.open && this.active >= 0 && this.results[this.active]) {
                event.preventDefault();
                window.location.href = this.results[this.active].url;
            }
            // otherwise: normal GET submission to the catalogue search
        },
        close() {
            this.open = false;
            this.active = -1;
        },
        reset() {
            this.results = [];
            this.state = 'idle';
            this.open = false;
            this.active = -1;
        },
    }));
}
