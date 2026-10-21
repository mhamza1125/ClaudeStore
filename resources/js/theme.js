// Light / Dark / System theme. The initial class is applied by an inline script in
// <head> (resources/views/partials/head.blade.php) to avoid a flash of the wrong theme.
const KEY = 'ah-theme';
const media = window.matchMedia('(prefers-color-scheme: dark)');

function store(value) {
    try { localStorage.setItem(KEY, value); } catch (e) { /* storage unavailable */ }
}

export function applyTheme(choice) {
    const dark = choice === 'dark' || (choice === 'system' && media.matches);
    document.documentElement.classList.toggle('dark', dark);
    document.documentElement.dataset.theme = choice;
}

export function registerTheme(Alpine) {
    Alpine.data('themeToggle', () => ({
        choice: document.documentElement.dataset.theme || 'system',
        open: false,
        set(choice) {
            this.choice = choice;
            this.open = false;
            store(choice);
            applyTheme(choice);
            const url = document.documentElement.dataset.themeUrl;
            if (url) {
                // Logged-in users: persist on the user record so it follows them across devices.
                window.axios.put(url, { theme: choice }).catch(() => {});
            }
        },
    }));

    media.addEventListener('change', () => {
        if ((document.documentElement.dataset.theme || 'system') === 'system') applyTheme('system');
    });
}
