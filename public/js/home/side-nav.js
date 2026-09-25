document.addEventListener('DOMContentLoaded', function () {
    const navbar = document.getElementById('navbar');
    if (!navbar) return;

    const dock = navbar.querySelector('.navbar-dock');
    const hero = document.querySelector('.hero');

    /* ---------------------------------------------------------
       DOCK HEIGHT SYNC

       The dock's height drives the morphing bar via --dock-h.
       The dock is never height-clamped in top mode, so
       scrollHeight is always its true natural content height.
       A small buffer guarantees the glass wraps every icon.
    --------------------------------------------------------- */
    if (dock) {
        const setDockHeight = function () {
            navbar.style.setProperty(
                '--dock-h',
                (dock.scrollHeight + 4) + 'px'
            );
        };

        setDockHeight();

        /* Re-measure whenever anything could change the height:
           full load, web fonts finishing, window resize */
        window.addEventListener('load', setDockHeight);
        window.addEventListener('resize', setDockHeight);

        if (document.fonts && document.fonts.ready) {
            document.fonts.ready.then(setDockHeight);
        }

        /* Expose for the toggle below */
        navbar.__syncDockHeight = setDockHeight;
    }

    /* ---------------------------------------------------------
       HERO OUT OF VIEW => MORPH; BACK IN VIEW => MORPH HOME
    --------------------------------------------------------- */
    if (hero && 'IntersectionObserver' in window) {
        new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (navbar.__syncDockHeight) navbar.__syncDockHeight();
                navbar.classList.toggle('docked-mode', !entry.isIntersecting);
            });
        }, { threshold: 0 }).observe(hero);
    } else {
        const onScroll = function () {
            const threshold = hero ? hero.offsetHeight - 80 : 400;

            if (navbar.__syncDockHeight) navbar.__syncDockHeight();
            navbar.classList.toggle('docked-mode', window.scrollY > threshold);
        };
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
    }

    /* ---------------------------------------------------------
       SECTION HIGHLIGHT
    --------------------------------------------------------- */
    const sectionLinks = navbar.querySelectorAll('.dock-link[data-side-link]');

    if ('IntersectionObserver' in window && sectionLinks.length) {
        const setActive = function (key) {
            sectionLinks.forEach(function (link) {
                link.classList.toggle(
                    'active',
                    link.getAttribute('data-side-link') === key
                );
            });
        };

        const sectionObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) setActive(entry.target.id);
            });
        }, { rootMargin: '-40% 0px -55% 0px' });

        sectionLinks.forEach(function (link) {
            const key = link.getAttribute('data-side-link');
            if (key === 'home') return;

            const section = document.getElementById(key);
            if (section) sectionObserver.observe(section);
        });

        if (hero) {
            new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) setActive('home');
                });
            }, { threshold: 0.4 }).observe(hero);
        }
    }

    /* ---------------------------------------------------------
       DOCK ACTIONS
    --------------------------------------------------------- */
    navbar.querySelectorAll('[data-side-action="search"]').forEach(function (item) {
        item.addEventListener('click', function (event) {
            const input = document.getElementById('patentQuery');
            if (input) {
                event.preventDefault();
                input.focus();
            }
        });
    });

    navbar.querySelectorAll('[data-side-action="top"]').forEach(function (item) {
        item.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });
});