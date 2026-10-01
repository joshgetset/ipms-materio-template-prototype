/**
 * Main
 */

'use strict';

import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

let menu, animate;
document.addEventListener('DOMContentLoaded', function () {
  // class for ios specific styles
  if (navigator.userAgent.match(/iPhone|iPad|iPod/i)) {
    document.body.classList.add('ios');
  }

  const logoutForm = document.querySelector('[data-logout-form]');
  if (logoutForm) {
    let confirmationPending = false;

    logoutForm.addEventListener('submit', async event => {
      if (logoutForm.dataset.confirmed === 'true') return;

      event.preventDefault();
      if (confirmationPending) return;
      confirmationPending = true;

      const result = await Swal.fire({
        title: 'Log out?',
        text: 'Are you sure you want to log out of your account?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Log out',
        cancelButtonText: 'Stay signed in',
        reverseButtons: true,
        focusCancel: true
      });

      confirmationPending = false;
      if (!result.isConfirmed) return;

      logoutForm.dataset.confirmed = 'true';
      HTMLFormElement.prototype.submit.call(logoutForm);
    });
  }

  const navbar = document.querySelector('.layout-navbar.navbar-detached');
  if (navbar) {
    const initialMarginTop = Number.parseFloat(window.getComputedStyle(navbar).marginTop) || 0;
    const dockThreshold = navbar.offsetHeight + initialMarginTop;
    const captureDockBounds = () => {
      const menu = document.querySelector('.layout-menu');
      const menuRight = menu?.getBoundingClientRect().right ?? 0;
      const navbarRect = navbar.getBoundingClientRect();
      const content = navbar.querySelector('.navbar-nav-right');

      navbar.style.setProperty('--navbar-dock-start', `${menuRight}px`);
      navbar.style.setProperty('--navbar-content-offset', `${Math.max(0, navbarRect.left - menuRight)}px`);
      navbar.style.setProperty(
        '--navbar-content-width',
        `${content?.getBoundingClientRect().width ?? navbarRect.width}px`
      );
    };
    const updateDockState = () => {
      const shouldDock = window.scrollY > dockThreshold;
      if (shouldDock && !navbar.classList.contains('navbar-docked')) captureDockBounds();
      navbar.classList.toggle('navbar-docked', shouldDock);
    };

    window.addEventListener('scroll', updateDockState, { passive: true });
    updateDockState();
  }
});

(function () {
  // Button & Pagination Waves effect
  if (typeof Waves !== 'undefined') {
    Waves.init();
    Waves.attach(".btn[class*='btn-']:not(.position-relative):not([class*='btn-outline-'])", ['waves-light']);
    Waves.attach("[class*='btn-outline-']:not(.position-relative)");
    Waves.attach('.pagination .page-item .page-link');
    Waves.attach('.dropdown-menu .dropdown-item');
    Waves.attach('[data-bs-theme="light"] .list-group .list-group-item-action');
    Waves.attach('.nav-tabs:not(.nav-tabs-widget) .nav-item .nav-link');
    Waves.attach('.nav-pills .nav-item .nav-link', ['waves-light']);
  }

  // Initialize menu
  //-----------------

  let layoutMenuEl = document.querySelectorAll('#layout-menu');
  layoutMenuEl.forEach(function (element) {
    menu = new Menu(element, {
      orientation: 'vertical',
      closeChildren: false
    });
    // Change parameter to true if you want scroll animation
    window.Helpers.scrollToActive((animate = false));
    window.Helpers.mainMenu = menu;
  });

  // Initialize menu togglers and bind click on each
  let menuToggler = document.querySelectorAll('.layout-menu-toggle');
  menuToggler.forEach(item => {
    item.addEventListener('click', event => {
      event.preventDefault();
      window.Helpers.setCollapsed(false);
    });
  });

  // Display menu toggle (layout-menu-toggle) on hover with delay
  let delay = function (elem, callback) {
    let timeout = null;
    elem.onmouseenter = function () {
      // Set timeout to be a timer which will invoke callback after 300ms (not for small screen)
      if (!Helpers.isSmallScreen()) {
        timeout = setTimeout(callback, 300);
      } else {
        timeout = setTimeout(callback, 0);
      }
    };

    elem.onmouseleave = function () {
      // Clear any timers set to timeout
      document.querySelector('.layout-menu-toggle').classList.remove('d-block');
      clearTimeout(timeout);
    };
  };
  if (document.querySelector('#layout-menu .layout-menu-toggle')) {
    delay(document.getElementById('layout-menu'), function () {
      // not for small screen
      if (!Helpers.isSmallScreen()) {
        document.querySelector('.layout-menu-toggle').classList.add('d-block');
      }
    });
  }

  // Display in main menu when menu scrolls
  let menuInnerContainer = document.getElementsByClassName('menu-inner'),
    menuInnerShadow = document.getElementsByClassName('menu-inner-shadow')[0];
  if (menuInnerContainer.length > 0 && menuInnerShadow) {
    menuInnerContainer[0].addEventListener('ps-scroll-y', function () {
      if (this.querySelector('.ps__thumb-y').offsetTop) {
        menuInnerShadow.style.display = 'block';
      } else {
        menuInnerShadow.style.display = 'none';
      }
    });
  }

  // Init helpers & misc
  // --------------------

  // Init BS Tooltip
  const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });

  // Accordion active class and previous-active class
  const accordionActiveFunction = function (e) {
    if (e.type == 'show.bs.collapse' || e.type == 'show.bs.collapse') {
      e.target.closest('.accordion-item').classList.add('active');
      e.target.closest('.accordion-item').previousElementSibling?.classList.add('previous-active');
    } else {
      e.target.closest('.accordion-item').classList.remove('active');
      e.target.closest('.accordion-item').previousElementSibling?.classList.remove('previous-active');
    }
  };

  const accordionTriggerList = [].slice.call(document.querySelectorAll('.accordion'));
  const accordionList = accordionTriggerList.map(function (accordionTriggerEl) {
    accordionTriggerEl.addEventListener('show.bs.collapse', accordionActiveFunction);
    accordionTriggerEl.addEventListener('hide.bs.collapse', accordionActiveFunction);
  });

  // Auto update layout based on screen size
  window.Helpers.setAutoUpdate(true);
  window.Helpers.update();

  // Toggle Password Visibility
  window.Helpers.initPasswordToggle();

  // Speech To Text
  window.Helpers.initSpeechToText();

  if (document.querySelector('#layout-menu')) {
    window.Helpers.setCollapsed(false, false);
  }
})();
