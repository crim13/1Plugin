(function() {
    var selectors = {
        nav: "[data-oneplugin-menu]",
        toggle: ".oneplugin-menu__toggle",
        overlay: ".oneplugin-menu__overlay",
        submenuToggle: ".oneplugin-menu__submenu-toggle",
        itemsWithChildren: ".menu-item-has-children"
    };

    var findDirectToggle = function(item) {
        var inner = item ? item.querySelector(".oneplugin-menu__item-inner") : null;
        if (!inner) {
            return null;
        }

        for (var i = 0; i < inner.children.length; i++) {
            if (inner.children[i].classList && inner.children[i].classList.contains("oneplugin-menu__submenu-toggle")) {
                return inner.children[i];
            }
        }

        return null;
    };

    var closeNested = function(root, exceptItem) {
        root.querySelectorAll(".oneplugin-menu__item.is-submenu-open").forEach(function(item) {
            if (exceptItem && (item === exceptItem || item.contains(exceptItem))) {
                return;
            }
            item.classList.remove("is-submenu-open");
            var button = findDirectToggle(item);
            if (button) {
                button.setAttribute("aria-expanded", "false");
            }
        });
    };

    var closeMenu = function(menu) {
        menu.classList.remove("is-open");
        closeNested(menu);
        var toggle = menu.querySelector(selectors.toggle);
        if (toggle) {
            toggle.setAttribute("aria-expanded", "false");
        }
    };

    var openMenu = function(menu) {
        menu.classList.add("is-open");
        var toggle = menu.querySelector(selectors.toggle);
        if (toggle) {
            toggle.setAttribute("aria-expanded", "true");
        }
    };

    var syncMode = function(menu) {
        var breakpoint = parseInt(menu.getAttribute("data-mobile-breakpoint") || "980", 10);
        if (!breakpoint || breakpoint < 320) {
            breakpoint = 980;
        }

        if (window.innerWidth <= breakpoint) {
            menu.classList.add("is-mobile-view");
            return;
        }

        menu.classList.remove("is-mobile-view");
        menu.classList.remove("is-open");
        closeNested(menu);
        var toggle = menu.querySelector(selectors.toggle);
        if (toggle) {
            toggle.setAttribute("aria-expanded", "false");
        }
    };

    var setupMenu = function(menu) {
        if (menu.__onepluginReady) {
            syncMode(menu);
            return;
        }

        menu.__onepluginReady = true;

        menu.querySelectorAll(selectors.itemsWithChildren).forEach(function(item) {
            var button = findDirectToggle(item);
            if (button) {
                button.setAttribute("aria-expanded", "false");
            }
        });

        var toggle = menu.querySelector(selectors.toggle);
        if (toggle) {
            toggle.addEventListener("click", function() {
                if (menu.classList.contains("is-open")) {
                    closeMenu(menu);
                    return;
                }
                openMenu(menu);
            });
        }

        var overlay = menu.querySelector(selectors.overlay);
        if (overlay) {
            overlay.addEventListener("click", function() {
                closeMenu(menu);
            });
        }

        menu.addEventListener("click", function(event) {
            var button = event.target.closest(selectors.submenuToggle);
            if (!button || !menu.contains(button)) {
                return;
            }

            var item = button.closest(".oneplugin-menu__item");
            if (!item) {
                return;
            }

            event.preventDefault();
            var expanded = button.getAttribute("aria-expanded") === "true";
            var allowMultiple = item.closest(".oneplugin-menu__submenu") !== null;
            if (!allowMultiple) {
                closeNested(menu, item);
            }

            item.classList.toggle("is-submenu-open", !expanded);
            button.setAttribute("aria-expanded", expanded ? "false" : "true");
        });

        document.addEventListener("click", function(event) {
            if (menu.getAttribute("data-close-outside") === "off") {
                return;
            }

            if (menu.contains(event.target)) {
                return;
            }

            closeMenu(menu);
        });

        document.addEventListener("keydown", function(event) {
            if (event.key === "Escape") {
                closeMenu(menu);
            }
        });

        syncMode(menu);
    };

    var boot = function() {
        document.querySelectorAll(selectors.nav).forEach(setupMenu);
    };

    document.addEventListener("DOMContentLoaded", boot);
    window.addEventListener("load", boot);
    window.addEventListener("resize", function() {
        document.querySelectorAll(selectors.nav).forEach(syncMode);
    });
    if (window.MutationObserver) {
        var observer = new MutationObserver(function() {
            boot();
        });
        observer.observe(document.documentElement, { childList: true, subtree: true });
    }
})();

