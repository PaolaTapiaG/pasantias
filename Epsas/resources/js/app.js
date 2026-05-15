import './bootstrap';

const setupAdminSidebar = () => {
    const sidebar = document.querySelector('[data-admin-sidebar]');
    const main = document.querySelector('[data-admin-main]');
    const labels = document.querySelectorAll('[data-sidebar-label]');
    const toggles = document.querySelectorAll('[data-sidebar-toggle]');
    const icons = document.querySelectorAll('[data-sidebar-toggle-icon]');

    if (!sidebar || !main || toggles.length === 0) {
        return;
    }

    const collapsedWidth = 'md:w-24';
    const expandedWidth = 'md:w-72';
    const collapsedPadding = 'md:pl-24';
    const expandedPadding = 'md:pl-72';
    const storageKey = 'epsas-admin-sidebar-collapsed';

    const applyState = (collapsed) => {
        sidebar.dataset.collapsed = collapsed ? 'true' : 'false';

        sidebar.classList.toggle(collapsedWidth, collapsed);
        sidebar.classList.toggle(expandedWidth, !collapsed);
        main.classList.toggle(collapsedPadding, collapsed);
        main.classList.toggle(expandedPadding, !collapsed);

        labels.forEach((label) => {
            label.dataset.collapsed = collapsed ? 'true' : 'false';
            label.classList.toggle('hidden', collapsed);
        });

        icons.forEach((icon) => {
            icon.classList.toggle('rotate-180', collapsed);
        });

        window.localStorage.setItem(storageKey, collapsed ? '1' : '0');
    };

    const initialCollapsed = window.localStorage.getItem(storageKey) === '1';
    applyState(initialCollapsed);

    toggles.forEach((toggle) => {
        toggle.addEventListener('click', () => {
            const nextCollapsed = sidebar.dataset.collapsed !== 'true';
            applyState(nextCollapsed);
        });
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupAdminSidebar);
} else {
    setupAdminSidebar();
}
