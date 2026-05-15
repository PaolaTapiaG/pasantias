import './bootstrap';

const setupTheme = () => {
    const root = document.documentElement;
    const toggle = document.querySelector('[data-theme-toggle]');
    const storageKey = 'epsas-theme';
    const preferred = document.body.dataset.themeDefault || 'light';
    const applyTheme = (theme) => {
        root.classList.toggle('dark', theme === 'dark');
        window.localStorage.setItem(storageKey, theme);
        if (toggle) {
            toggle.dataset.theme = theme;
            toggle.textContent = theme === 'dark' ? 'Modo claro' : 'Modo oscuro';
        }
    };

    applyTheme(window.localStorage.getItem(storageKey) || preferred);

    toggle?.addEventListener('click', () => {
        applyTheme(root.classList.contains('dark') ? 'light' : 'dark');
    });
};

const setupSidebar = () => {
    const sidebar = document.querySelector('[data-admin-sidebar], [data-tech-sidebar]');
    const main = document.querySelector('[data-sidebar-main], [data-admin-main], [data-tech-main]');
    const labels = document.querySelectorAll('[data-sidebar-label]');
    const desktopToggles = document.querySelectorAll('[data-sidebar-toggle]');
    const openers = document.querySelectorAll('[data-sidebar-open]');
    const closers = document.querySelectorAll('[data-sidebar-close], [data-sidebar-overlay]');
    const icons = document.querySelectorAll('[data-sidebar-toggle-icon]');
    const overlay = document.querySelector('[data-sidebar-overlay]');

    if (!sidebar) {
        return;
    }

    const collapsedWidth = 'md:w-24';
    const expandedWidth = 'md:w-72';
    const collapsedPadding = 'md:pl-24';
    const expandedPadding = 'md:pl-72';
    const storageKey = sidebar.hasAttribute('data-tech-sidebar')
        ? 'epsas-tech-sidebar-collapsed'
        : 'epsas-admin-sidebar-collapsed';

    const applyDesktopState = (collapsed) => {
        if (window.innerWidth < 768) {
            return;
        }

        sidebar.dataset.collapsed = collapsed ? 'true' : 'false';
        sidebar.classList.toggle(collapsedWidth, collapsed);
        sidebar.classList.toggle(expandedWidth, !collapsed);

        if (main) {
            main.classList.toggle(collapsedPadding, collapsed);
            main.classList.toggle(expandedPadding, !collapsed);
        }

        labels.forEach((label) => {
            label.dataset.collapsed = collapsed ? 'true' : 'false';
            label.classList.toggle('hidden', collapsed);
        });

        icons.forEach((icon) => {
            icon.classList.toggle('rotate-180', collapsed);
        });

        window.localStorage.setItem(storageKey, collapsed ? '1' : '0');
    };

    const openMobileSidebar = () => {
        sidebar.classList.remove('-translate-x-full');
        overlay?.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    };

    const closeMobileSidebar = () => {
        if (window.innerWidth >= 768) {
            overlay?.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
            return;
        }

        sidebar.classList.add('-translate-x-full');
        overlay?.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    };

    const syncByViewport = () => {
        if (window.innerWidth >= 768) {
            sidebar.classList.remove('-translate-x-full');
            overlay?.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
            applyDesktopState(window.localStorage.getItem(storageKey) === '1');
        } else {
            sidebar.classList.add('-translate-x-full');
            labels.forEach((label) => label.classList.remove('hidden'));
        }
    };

    applyDesktopState(window.localStorage.getItem(storageKey) === '1');
    syncByViewport();

    desktopToggles.forEach((toggle) => {
        toggle.addEventListener('click', () => {
            if (window.innerWidth < 768) {
                openMobileSidebar();
                return;
            }

            const nextCollapsed = sidebar.dataset.collapsed !== 'true';
            applyDesktopState(nextCollapsed);
        });
    });

    openers.forEach((opener) => opener.addEventListener('click', openMobileSidebar));
    closers.forEach((closer) => closer.addEventListener('click', closeMobileSidebar));
    window.addEventListener('resize', syncByViewport);
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeMobileSidebar();
        }
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        setupTheme();
        setupSidebar();
    });
} else {
    setupTheme();
    setupSidebar();
}
