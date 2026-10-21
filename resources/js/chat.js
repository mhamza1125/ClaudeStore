// Website chatbot widget. Talks only to our own Laravel endpoint (CSRF-protected);
// the AI provider and its key are server-side. Model output is rendered as text
// nodes; only same-site paths and a short list of https hosts become links.
const SAFE_HOSTS = ['api.whatsapp.com', 'wa.me'];
const TOKEN = /(https?:\/\/[^\s<>()"']+|(?<![\w/])\/(?:products|categories|quote|pages|account|register|login)(?:\/[\w\-./]*)?)/g;

function store(key, value) {
    try { sessionStorage.setItem(key, value); } catch (e) { /* storage unavailable */ }
}
function read(key) {
    try { return sessionStorage.getItem(key); } catch (e) { return null; }
}

function safeHref(token) {
    if (token.startsWith('/')) return token.replace(/[.,]+$/, '');
    try {
        const url = new URL(token.replace(/[.,]+$/, ''));
        if (url.protocol === 'https:' && (url.host === location.host || SAFE_HOSTS.includes(url.host))) return url.href;
        if (url.host === location.host) return url.href;
    } catch (e) { /* not a URL */ }
    return null;
}

export function registerChat(Alpine) {
    Alpine.data('chatWidget', (config) => ({
        ...config,
        open: false,
        showTeaser: false,
        messages: config.history || [],
        draft: '',
        sending: false,
        error: '',
        init() {
            if (this.teaser && !read('ah-chat-teaser-dismissed')) {
                setTimeout(() => { if (!this.open) this.showTeaser = true; }, Math.max(0, this.teaserDelay) * 1000);
            }
        },
        toggle() { this.open ? this.close() : this.openPanel(); },
        openPanel() {
            this.open = true;
            this.showTeaser = false;
            window.dispatchEvent(new CustomEvent('chat-panel', { detail: { open: true } }));
            this.$nextTick(() => { this.$refs.input?.focus(); this.scroll(); });
        },
        close() {
            this.open = false;
            window.dispatchEvent(new CustomEvent('chat-panel', { detail: { open: false } }));
        },
        dismissTeaser() {
            this.showTeaser = false;
            store('ah-chat-teaser-dismissed', '1');
        },
        ask(text) { this.draft = text; this.send(); },
        async send() {
            const text = this.draft.trim();
            if (!text || this.sending || !this.online) return;
            this.error = '';
            this.messages.push({ role: 'user', content: text });
            this.draft = '';
            this.sending = true;
            this.scroll();
            try {
                const { data } = await window.axios.post(this.sendUrl, { message: text });
                this.messages.push({ role: 'assistant', content: data.reply });
            } catch (e) {
                this.messages.pop();
                this.draft = text;
                const status = e.response?.status;
                this.error = status === 429
                    ? 'You have sent a lot of messages. Please wait a moment, or request a quote at /quote.'
                    : (e.response?.data?.message || 'Sorry, something went wrong. Please try again.');
            } finally {
                this.sending = false;
                this.scroll();
            }
        },
        async reset() {
            this.messages = [];
            this.error = '';
            try { await window.axios.post(this.resetUrl); } catch (e) { /* ignore */ }
        },
        scroll() {
            this.$nextTick(() => { const log = this.$refs.log; if (log) log.scrollTop = log.scrollHeight; });
        },
        // Render text safely: text nodes plus vetted links, never innerHTML.
        render(el, text) {
            el.replaceChildren();
            let last = 0;
            for (const match of text.matchAll(TOKEN)) {
                const href = safeHref(match[0]);
                if (!href) continue;
                el.append(document.createTextNode(text.slice(last, match.index)));
                const a = document.createElement('a');
                a.href = href;
                a.textContent = match[0].replace(/[.,]+$/, '');
                a.className = 'underline underline-offset-2';
                if (!href.startsWith('/') && new URL(href).host !== location.host) {
                    a.target = '_blank';
                    a.rel = 'noopener noreferrer';
                }
                el.append(a);
                last = match.index + a.textContent.length;
            }
            el.append(document.createTextNode(text.slice(last)));
        },
    }));
}
