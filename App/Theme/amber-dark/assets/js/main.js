/**
 * ===============================
 * AMBER DARK THEME - JavaScript
 * Dashboard Dark Mode con accenti Arancione/Amber
 * ===============================
 */

(function($) {
  'use strict';

  // Theme Configuration
  const AmberDarkTheme = {
    init: function() {
      this.initSidebar();
      this.initMenuActive();
      this.initAnimations();
      this.initTooltips();
      this.initRippleEffect();
    },

    /**
     * Sidebar Toggle & Submenu Handling
     */
    initSidebar: function() {
      // Remember sidebar state
      const sidebarState = localStorage.getItem('sidebarCollapsed');
      if (sidebarState === 'true') {
        $('body').addClass('sidebar-collapsed');
      }

      // Toggle sidebar on mobile
      $('.menu-toggle-amber, .button-menu-mobile').on('click', function() {
        $('body').toggleClass('sidebar-enable');
      });

      // Close sidebar when clicking outside on mobile
      $(document).on('click', function(e) {
        if ($(window).width() < 992) {
          if (!$(e.target).closest('.left-side-menu, .menu-toggle-amber, .button-menu-mobile').length) {
            $('body').removeClass('sidebar-enable');
          }
        }
      });

      // Submenu smooth animation
      $('.menu-link-amber.has-submenu').on('click', function(e) {
        const $submenu = $(this).next('.submenu-amber');

        // Close other submenus
        $('.submenu-amber').not($submenu).slideUp(200);
        $('.menu-link-amber.has-submenu').not(this).removeClass('active');

        // Toggle current submenu
        $submenu.slideToggle(200);
        $(this).toggleClass('active');
      });
    },

    /**
     * Highlight Active Menu Item based on URL
     */
    initMenuActive: function() {
      const currentPath = window.location.pathname;

      $('.menu-link-amber, .submenu-link-amber').each(function() {
        const href = $(this).attr('href');
        if (href && currentPath.includes(href) && href !== '#' && href !== 'javascript:void(0)') {
          $(this).addClass('active');

          // If it's a submenu item, open parent
          if ($(this).hasClass('submenu-link-amber')) {
            const $parent = $(this).closest('.submenu-amber');
            $parent.addClass('show').css('display', 'block');
            $parent.prev('.menu-link-amber').addClass('active');
          }
        }
      });
    },

    /**
     * Initialize Entrance Animations
     */
    initAnimations: function() {
      // Fade in sidebar menu items sequentially
      $('.menu-item-amber').each(function(index) {
        $(this).css({
          'opacity': '0',
          'transform': 'translateX(-20px)'
        }).delay(index * 50).animate({
          'opacity': '1'
        }, 300).css('transform', 'translateX(0)');
      });

      // Add hover glow effect to cards
      $('.card').on('mouseenter', function() {
        $(this).addClass('glow-amber');
      }).on('mouseleave', function() {
        $(this).removeClass('glow-amber');
      });
    },

    /**
     * Initialize Bootstrap Tooltips
     */
    initTooltips: function() {
      if (typeof bootstrap !== 'undefined') {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
          return new bootstrap.Tooltip(tooltipTriggerEl, {
            template: '<div class="tooltip tooltip-amber" role="tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner"></div></div>'
          });
        });
      }
    },

    /**
     * Ripple Effect on Buttons
     */
    initRippleEffect: function() {
      $('.btn-amber, .menu-link-amber').on('click', function(e) {
        const $this = $(this);
        const offset = $this.offset();
        const x = e.pageX - offset.left;
        const y = e.pageY - offset.top;

        const $ripple = $('<span class="ripple-effect"></span>');
        $ripple.css({
          left: x,
          top: y
        });

        $this.append($ripple);

        setTimeout(function() {
          $ripple.remove();
        }, 600);
      });

      // Add ripple CSS dynamically
      if (!$('#ripple-style').length) {
        $('head').append(`
          <style id="ripple-style">
            .ripple-effect {
              position: absolute;
              border-radius: 50%;
              background: rgba(245, 158, 11, 0.3);
              transform: scale(0);
              animation: ripple 0.6s linear;
              pointer-events: none;
            }
            @keyframes ripple {
              to {
                transform: scale(4);
                opacity: 0;
              }
            }
            .btn-amber, .menu-link-amber {
              position: relative;
              overflow: hidden;
            }
          </style>
        `);
      }
    }
  };

  // Initialize theme when DOM is ready
  $(document).ready(function() {
    AmberDarkTheme.init();
  });

  // Expose to global scope if needed
  window.AmberDarkTheme = AmberDarkTheme;

})(jQuery);
