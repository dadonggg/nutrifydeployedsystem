/**
 * NUTRIFY - SHARED RESPONSIVE LOGIC (responsive.js)
 * Covers all roles: Fitness Enthusiast, Trainer, Gym Owner, Admin Officer, Admin.
 */
(function () {
    'use strict';

    function initDrawer() {
        var sidebar = document.getElementById('sidebarMenu') || document.querySelector('.app-sidebar') || document.querySelector('.sidebar');
        if (!sidebar) return;

        // Ensure backdrop exists
        var backdrop = document.querySelector('.sidebar-backdrop');
        if (!backdrop) {
            backdrop = document.createElement('div');
            backdrop.className = 'sidebar-backdrop';
            document.body.appendChild(backdrop);
        }

        function openDrawer() {
            sidebar.classList.add('show', 'active', 'open');
            backdrop.classList.add('show', 'open');
            document.body.classList.add('drawer-open');
        }

        function closeDrawer() {
            sidebar.classList.remove('show', 'active', 'open');
            backdrop.classList.remove('show', 'open');
            document.body.classList.remove('drawer-open');
            if (typeof bootstrap !== 'undefined' && bootstrap.Offcanvas) {
                var inst = bootstrap.Offcanvas.getInstance(sidebar);
                if (inst) inst.hide();
            }
        }

        // Toggle buttons
        var toggleBtns = document.querySelectorAll('#mobileMenuBtn, .btn-nav-toggle, [data-bs-toggle="offcanvas"], [data-drawer-toggle]');
        toggleBtns.forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                if (window.innerWidth < 992) {
                    if (sidebar.classList.contains('show') || sidebar.classList.contains('open') || sidebar.classList.contains('active')) {
                        closeDrawer();
                    } else {
                        openDrawer();
                    }
                }
            });
        });

        // Close when clicking backdrop
        backdrop.addEventListener('click', closeDrawer);

        // Close when clicking nav-links on mobile
        sidebar.querySelectorAll('.nav-link').forEach(function (link) {
            link.addEventListener('click', function () {
                if (window.innerWidth < 992) {
                    closeDrawer();
                }
            });
        });

        // Close on Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' || e.keyCode === 27) {
                closeDrawer();
            }
        });

        // Auto close if resized above 991px
        window.addEventListener('resize', function () {
            if (window.innerWidth >= 992) {
                closeDrawer();
            }
        });
    }

    function initTables() {
        var tables = document.querySelectorAll('table');
        tables.forEach(function (table) {
            // Check if already in a scroll container
            var parent = table.parentElement;
            var isWrapped = parent && (parent.classList.contains('table-scroll') || parent.classList.contains('table-responsive'));

            if (!isWrapped) {
                var wrapper = document.createElement('div');
                wrapper.className = 'table-scroll';
                parent.insertBefore(wrapper, table);
                wrapper.appendChild(table);
                parent = wrapper;
            } else {
                parent.classList.add('table-scroll');
            }

            // Check if hint already exists
            var nextEl = parent.nextElementSibling;
            if (!nextEl || !nextEl.classList.contains('table-scroll-hint')) {
                var hint = document.createElement('div');
                hint.className = 'table-scroll-hint';
                hint.innerHTML = '<i class="bi bi-arrow-left-right me-1"></i> Swipe left/right to see more';
                parent.parentNode.insertBefore(hint, parent.nextSibling);
            }
        });
    }

    function initChartsAndGrids() {
        // Ensure chart canvases are responsive
        var canvases = document.querySelectorAll('canvas');
        canvases.forEach(function (canvas) {
            var parent = canvas.parentElement;
            if (parent && !parent.classList.contains('chart-container')) {
                parent.classList.add('chart-container');
            }
        });
    }

    function initActiveMenu() {
        var urlParams = new URLSearchParams(window.location.search);
        var currentRoute = urlParams.get('r');
        if (!currentRoute) currentRoute = 'home/index';

        var links = document.querySelectorAll('.app-sidebar .nav-link, .sidebar .nav-link, .mobile-bottom-item');
        links.forEach(function (link) {
            var href = link.getAttribute('href');
            if (href && href.indexOf('r=') !== -1) {
                var linkRoute = href.split('r=')[1].split('&')[0];
                if (linkRoute === currentRoute) {
                    link.classList.add('active');
                }
            }
        });
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initDrawer();
            initTables();
            initChartsAndGrids();
            initActiveMenu();
        });
    } else {
        initDrawer();
        initTables();
        initChartsAndGrids();
        initActiveMenu();
    }
})();
