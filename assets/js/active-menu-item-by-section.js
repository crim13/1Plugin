(function() {
    var menuSelector = '#top-menu, .oneplugin-menu__list';
    var activeClasses = ['current_page_item', 'current-menu-item'];
    var items = [];
    var menuItems = [];
    var ticking = false;

    var decodeHash = function(hash) {
        if (!hash || hash === '#') {
            return '';
        }

        try {
            return decodeURIComponent(hash.slice(1));
        } catch (e) {
            return hash.slice(1);
        }
    };

    var getHeaderOffset = function() {
        var header = document.querySelector('#main-header, header#main-header, .et-l--header, header');
        if (!header) {
            return 0;
        }

        var styles = window.getComputedStyle(header);
        if (styles.position !== 'fixed' && styles.position !== 'sticky') {
            return 0;
        }

        return Math.max(0, Math.round(header.getBoundingClientRect().height));
    };

    var collectItems = function() {
        var menus = Array.prototype.slice.call(document.querySelectorAll(menuSelector));
        if (!menus.length) {
            return [];
        }

        menuItems = [];

        return menus.reduce(function(collected, menu) {
            menuItems = menuItems.concat(Array.prototype.slice.call(menu.querySelectorAll('.menu-item')));

            Array.prototype.slice.call(menu.querySelectorAll('a[href*="#"]')).forEach(function(link) {
                var url;
                try {
                    url = new URL(link.getAttribute('href'), window.location.href);
                } catch (e) {
                    return;
                }

                if (!url.hash || url.pathname.replace(/\/$/, '') !== window.location.pathname.replace(/\/$/, '') || url.hostname !== window.location.hostname) {
                    return;
                }

                var targetId = decodeHash(url.hash);
                if (!targetId) {
                    return;
                }

                var section = document.getElementById(targetId);
                var menuItem = link.closest('li.menu-item') || link.parentElement;
                if (!section || !menuItem) {
                    return;
                }

                collected.push({
                    link: link,
                    menuItem: menuItem,
                    section: section
                });
            });

            return collected;
        }, []);
    };

    var setActiveItem = function(activeItem) {
        menuItems.forEach(function(menuItem) {
            activeClasses.forEach(function(activeClass) {
                menuItem.classList.remove(activeClass);
            });
        });

        if (!activeItem) {
            return;
        }

        activeClasses.forEach(function(activeClass) {
            activeItem.menuItem.classList.add(activeClass);
        });
    };

    var clearActiveItems = function() {
        menuItems.forEach(function(menuItem) {
            activeClasses.forEach(function(activeClass) {
                menuItem.classList.remove(activeClass);
            });
        });
    };

    var updateActiveItem = function() {
        ticking = false;

        if (!items.length) {
            return;
        }

        var offset = getHeaderOffset();
        var viewportHeight = window.innerHeight || document.documentElement.clientHeight;
        var activationLine = offset + Math.max(80, Math.round((viewportHeight - offset) * 0.35));
        var bestItem = null;
        var bestDistance = Infinity;

        items.forEach(function(item) {
            var rect = item.section.getBoundingClientRect();
            var isVisible = rect.bottom > offset && rect.top < viewportHeight;
            if (!isVisible) {
                return;
            }

            var distance = Math.abs(rect.top - activationLine);
            if (rect.top <= activationLine && rect.bottom >= activationLine) {
                distance = 0;
            }

            if (distance < bestDistance) {
                bestDistance = distance;
                bestItem = item;
            }
        });

        if (bestItem) {
            setActiveItem(bestItem);
        } else {
            clearActiveItems();
        }
    };

    var requestUpdate = function() {
        if (ticking) {
            return;
        }

        ticking = true;
        window.requestAnimationFrame(updateActiveItem);
    };

    var init = function() {
        items = collectItems();
        if (!items.length) {
            return;
        }

        window.addEventListener('scroll', requestUpdate, { passive: true });
        window.addEventListener('resize', requestUpdate);
        window.addEventListener('hashchange', requestUpdate);
        requestUpdate();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

