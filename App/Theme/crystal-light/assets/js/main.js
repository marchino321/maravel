/**
 * Crystal Light Theme - Main JS
 * Funzioni specifiche del tema
 */

(function() {
  'use strict';

  // Menu mobile toggle
  document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.querySelector('.button-menu-mobile');
    if (menuToggle) {
      menuToggle.addEventListener('click', function() {
        document.body.classList.toggle('sidebar-enable');
      });
    }

    // Chiudi sidebar cliccando fuori su mobile
    document.addEventListener('click', function(e) {
      if (window.innerWidth < 992) {
        const sidebar = document.querySelector('.left-side-menu');
        const menuBtn = document.querySelector('.button-menu-mobile');

        if (sidebar && !sidebar.contains(e.target) &&
            menuBtn && !menuBtn.contains(e.target) &&
            document.body.classList.contains('sidebar-enable')) {
          document.body.classList.remove('sidebar-enable');
        }
      }
    });

    // Active menu item based on current URL
    const currentPath = window.location.pathname;
    const menuLinks = document.querySelectorAll('#side-menu a[href]');

    menuLinks.forEach(function(link) {
      if (link.getAttribute('href') === currentPath) {
        link.classList.add('active');

        // Espandi parent collapse se esiste
        const parentCollapse = link.closest('.collapse');
        if (parentCollapse) {
          parentCollapse.classList.add('show');
          const trigger = document.querySelector('[href="#' + parentCollapse.id + '"]');
          if (trigger) {
            trigger.setAttribute('aria-expanded', 'true');
          }
        }
      }
    });

    console.log('%c Crystal Light Theme loaded ',
      'background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; padding: 5px 10px; border-radius: 4px;');
  });
})();
