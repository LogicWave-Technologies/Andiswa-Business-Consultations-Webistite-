

(() => {
    'use strict';

    const STORAGE_KEY = 'site-theme';
    const root = document.documentElement;

    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = themeToggle ? themeToggle.querySelector('i') : null;

    const menuToggle = document.getElementById('menuToggle');
    const navLinks = document.querySelector('.nav-links');

    /* ---------- theme ---------- */

    function getSystemPreference() {
        return window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches
            ? 'light'
            : 'dark';
    }

    function applyTheme(theme) {
        root.setAttribute('data-theme', theme);

        if (themeIcon) {
            themeIcon.classList.remove('fa-moon', 'fa-sun');
            themeIcon.classList.add(theme === 'light' ? 'fa-sun' : 'fa-moon');
        }

        if (themeToggle) {
            themeToggle.setAttribute(
                'aria-label',
                theme === 'light' ? 'Switch to dark mode' : 'Switch to light mode'
            );
        }
    }

    function setTheme(theme) {
        applyTheme(theme);
        localStorage.setItem(STORAGE_KEY, theme);
    }

    function toggleTheme() {
        const current = root.getAttribute('data-theme') === 'light' ? 'light' : 'dark';
        setTheme(current === 'light' ? 'dark' : 'light');
    }

    // theme is already applied by the inline anti-flash snippet in <head>;
    // this just makes sure the icon matches on load.
    applyTheme(root.getAttribute('data-theme') || getSystemPreference());

    if (themeToggle) {
        themeToggle.addEventListener('click', toggleTheme);
    }

    /* ---------- mobile nav ---------- */

    function toggleMobileNav() {
        if (!navLinks) return;
        navLinks.classList.toggle('active');

        const isOpen = navLinks.classList.contains('active');
        if (menuToggle) {
            menuToggle.setAttribute('aria-expanded', String(isOpen));
            const icon = menuToggle.querySelector('i');
            if (icon) {
                icon.classList.toggle('fa-bars', !isOpen);
                icon.classList.toggle('fa-xmark', isOpen);
            }
        }
    }

    if (menuToggle) {
        menuToggle.addEventListener('click', toggleMobileNav);
    }

    if (navLinks) {
        navLinks.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => navLinks.classList.remove('active'));
        });
    }
})();