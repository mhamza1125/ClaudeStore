// Website chatbot widget. Talks only to our own Laravel endpoint (CSRF-protected);
// the AI provider and its key are server-side. Model output is rendered with DOM nodes,
// never innerHTML: paragraphs, "- " lists, "1. " steps and **bold** (the format the system
// prompt asks for, app/Assistant/BusinessContext.php); only same-site paths and a short
// list of https hosts become links.
const SAFE_HOSTS = ['api.whatsapp.com', 'wa.me'];
const TEASER_SECONDS = 10;
const TOKEN = /(https?:\/\/[^\s<>()"']+|(?<![\w/])\/(?:products|categories|quote|account|register|login|about|contact|custom-manufacturing|terms|privacy|shipping|returns)(?:\/[\w\-./]*)?)/g;

const BULLET = /^\s*[-*•]\s+(.*)$/;
const NUMBERED = /^\s*\d{1,2}[.)]\s+(.*)$/;
const HEADING = /^\s*#{1,6}\s+(.*)$/;

/**
 * Splits a reply into blocks: {type: 'p', lines}, {type: 'ul' | 'ol', items}. A blank line
 * ends a block; list items continue until a non-list line. Headings become bold paragraphs.
 */
export function parseReply(text) {
    const blocks = [];
    let current = null;
    const flush = () => { if (current) blocks.push(current); current = null; };
    for (const raw of String(text).replace(/\r\n?/g, '\n').split('\n')) {
        const line = raw.trimEnd();
        let m;
        if (line.trim() === '') { flush(); continue; }
        if ((m = line.match(BULLET)) || (m = line.match(NUMBERED))) {
            const type = BULLET.test(line) ? 'ul' : 'ol';
            if (current?.type !== type) { flush(); current = { type, items: [] }; }
            current.items.push(m[1].trim());
            continue;
        }
        if ((m = line.match(HEADING))) { flush(); blocks.push({ type: 'p', lines: [`**${m[1].replace(/\*\*/g, '').trim()}**`] }); continue; }
        if (current?.type !== 'p') { flush(); current = { type: 'p', lines: [] }; }
        current.lines.push(line.trim());
    }
    flush();
    return blocks;
}

/** Inline segments of one line: [{text, bold}]. Unpaired ** markers stay as text. */
export function parseInline(line) {
    const parts = String(line).split(/\*\*(.+?)\*\*/g);
    return parts.map((text, i) => ({ text, bold: i % 2 === 1 })).filter((p) => p.text !== '');
}

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

function appendInline(el, line) {
    for (const part of parseInline(line)) {
        if (!part.bold) { appendLinked(el, part.text); continue; }
        const strong = document.createElement('strong');
        strong.className = 'font-semibold';
        appendLinked(strong, part.text);
        el.append(strong);
    }
}

/** Text nodes, with same-site paths and allowed https hosts turned into links. */
function appendLinked(el, text) {
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
}

export function registerChat(Alpine) {
    Alpine.data('chatWidget', (config) => ({
        ...config,
        open: false,
        navOpen: false, // the mobile menu is open; the launcher steps aside
        showTeaser: false,
        messages: config.history || [],
        draft: '',
        sending: false,
        error: '',
        // The teaser appears at most once per browser session and hides itself after a few
        // seconds, so it never sits over page content.
        init() {
            if (this.teaser && !read('ah-chat-teaser-dismissed')) {
                setTimeout(() => {
                    if (this.open || this.navOpen) return;
                    this.showTeaser = true;
                    store('ah-chat-teaser-dismissed', '1');
                    setTimeout(() => { this.showTeaser = false; }, TEASER_SECONDS * 1000);
                }, Math.max(0, this.teaserDelay) * 1000);
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
        // Visitor messages: plain text plus vetted links. Assistant replies: blocks, bold
        // and vetted links. Never innerHTML.
        render(el, text, role = 'assistant') {
            el.replaceChildren();
            if (role === 'user') {
                el.classList.add('whitespace-pre-line');
                appendLinked(el, text);
                return;
            }
            for (const block of parseReply(text)) {
                if (block.type === 'p') {
                    const p = document.createElement('p');
                    block.lines.forEach((line, i) => { if (i) p.append(document.createElement('br')); appendInline(p, line); });
                    el.append(p);
                } else {
                    const list = document.createElement(block.type);
                    list.className = block.type === 'ul' ? 'list-disc space-y-1 pl-5 marker:text-accent' : 'list-decimal space-y-1 pl-5 marker:text-muted';
                    for (const item of block.items) {
                        const li = document.createElement('li');
                        appendInline(li, item);
                        list.append(li);
                    }
                    el.append(list);
                }
            }
        },
    }));
}
