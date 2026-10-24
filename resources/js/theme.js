// White / Mid / Dark / System themes. The initial palette is applied by an inline script in
// <head> (resources/views/partials/head.blade.php) to avoid a flash of the wrong theme;
// resolveTheme() mirrors that script.
const KEY = 'ah-theme';
const media = window.matchMedia('(prefers-color-scheme: dark)');
const root = document.documentElement;

function store(value) {
    try { localStorage.setItem(KEY, value); } catch (e) { /* storage unavailable */ }
}

export function resolveTheme(choice) {
    if (choice !== 'system') return choice;
    return media.matches ? 'dark' : (root.dataset.themeLight || 'mid');
}

export function applyTheme(choice) {
    const palette = resolveTheme(choice);
    root.dataset.theme = choice;
    root.dataset.palette = palette;
    root.classList.toggle('dark', palette === 'dark');
}

export function registerTheme(Alpine) {
    Alpine.data('themeToggle', () => ({
        choice: root.dataset.theme || 'mid',
        open: false,
        set(choice) {
            this.choice = choice;
            this.open = false;
            store(choice);
            applyTheme(choice);
            const url = root.dataset.themeUrl;
            if (url) {
                // Logged-in users: persist on the user record so it follows them across devices.
                window.axios.put(url, { theme: choice }).catch(() => {});
            }
        },
    }));

    media.addEventListener('change', () => {
        if ((root.dataset.theme || 'mid') === 'system') applyTheme('system');
    });
}
