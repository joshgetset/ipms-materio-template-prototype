document.addEventListener('DOMContentLoaded', function () {
    const navbar = document.getElementById('navbar');
    if (!navbar) {
        return;
    }

    const toggle = navbar.querySelector('.mobile-nav-toggle');
    const panel = navbar.querySelector('.mobile-nav-panel');

    if (!toggle || !panel) {
        return;
    }

    const closeMenu = function () {
        panel.setAttribute('hidden', 'hidden');
        panel.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
    };

    const openMenu = function () {
        panel.removeAttribute('hidden');
        requestAnimationFrame(function () {
            panel.classList.add('is-open');
        });
        toggle.setAttribute('aria-expanded', 'true');
    };

    toggle.addEventListener('click', function () {
        const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
        if (isExpanded) {
            closeMenu();
            return;
        }

        openMenu();
    });

    panel.querySelectorAll('a').forEach(function (item) {
        item.addEventListener('click', function (event) {
            const shouldFocusSearch = this.getAttribute('data-mobile-search') === 'true';

            if (shouldFocusSearch) {
                event.preventDefault();
                const patentQuery = document.getElementById('patentQuery');
                if (patentQuery) {
                    patentQuery.focus();
                    patentQuery.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }

            closeMenu();
        });
    });

    document.addEventListener('click', function (event) {
        if (!navbar.contains(event.target)) {
            closeMenu();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeMenu();
        }
    });

    closeMenu();
});
