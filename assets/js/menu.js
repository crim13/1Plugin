(function() {
    var selectors = {
        nav: "[data-oneplugin-menu]",
        toggle: ".oneplugin-menu__toggle",
        overlay: ".oneplugin-menu__overlay",
        panel: ".oneplugin-menu__panel",
        submenuToggle: ".oneplugin-menu__submenu-toggle",
        item: ".oneplugin-menu__item",
        itemsWithChildren: ".menu-item-has-children, .oneplugin-menu__item--has-children"
    };

    var raf = window.requestAnimationFrame || function(callback) {
        return window.setTimeout(callback, 16);
    };

    var forEachNode = function(nodes, callback) {
        Array.prototype.forEach.call(nodes || [], callback);
    };

    var closest = function(target, selector) {
        return target && target.closest ? target.closest(selector) : null;
    };

    var findDirectChild = function(item, className) {
        if (!item) {
            return null;
        }

        for (var i = 0; i < item.children.length; i++) {
            if (item.children[i].classList && item.children[i].classList.contains(className)) {
                return item.children[i];
            }
        }

        return null;
    };

    var findDirectInner = function(item) {
        return findDirectChild(item, "oneplugin-menu__item-inner");
    };

    var findDirectToggle = function(item) {
        var inner = findDirectInner(item);
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

    var findDirectLink = function(item) {
        var inner = findDirectInner(item);
        return inner ? inner.querySelector(".oneplugin-menu__link") : null;
    };

    var isPlaceholderLink = function(link) {
        if (!link) {
            return true;
        }

        var href = (link.getAttribute("href") || "").trim();
        return href === "" || href === "#" || href.indexOf("javascript:") === 0;
    };

    var setItemExpanded = function(item, expanded) {
        if (!item) {
            return;
        }

        item.classList.toggle("is-submenu-open", !!expanded);

        var button = findDirectToggle(item);
        if (button) {
            button.setAttribute("aria-expanded", expanded ? "true" : "false");
        }

        var link = findDirectLink(item);
        if (link && link.hasAttribute("data-oneplugin-submenu-link")) {
            link.setAttribute("aria-expanded", expanded ? "true" : "false");
        }
    };

    var closeNested = function(root, exceptItem) {
        forEachNode(root.querySelectorAll(".oneplugin-menu__item.is-submenu-open"), function(item) {
            if (exceptItem && (item === exceptItem || item.contains(exceptItem))) {
                return;
            }

            setItemExpanded(item, false);
        });
    };

    var setMenuOpen = function(menu, open) {
        menu.classList.toggle("is-open", !!open);

        var toggle = menu.querySelector(selectors.toggle);
        if (toggle) {
            toggle.setAttribute("aria-expanded", open ? "true" : "false");
        }

        var panel = menu.querySelector(selectors.panel);
        if (panel) {
            panel.setAttribute("aria-hidden", menu.classList.contains("is-mobile-view") && !open ? "true" : "false");
        }

        var overlay = menu.querySelector(selectors.overlay);
        if (overlay) {
            overlay.setAttribute("aria-hidden", open ? "false" : "true");
        }
    };

    var closeMenu = function(menu) {
        setMenuOpen(menu, false);
        closeNested(menu);
    };

    var openMenu = function(menu) {
        setMenuOpen(menu, true);
    };

    var closeMenusOutside = function(target) {
        forEachNode(document.querySelectorAll(selectors.nav), function(menu) {
            if (menu.getAttribute("data-close-outside") === "off" || menu.contains(target)) {
                return;
            }

            closeMenu(menu);
        });
    };

    var closeAllMenus = function() {
        forEachNode(document.querySelectorAll(selectors.nav), closeMenu);
    };

    var globalListenersReady = false;
    var setupGlobalListeners = function() {
        if (globalListenersReady) {
            return;
        }

        globalListenersReady = true;
        document.addEventListener("click", function(event) {
            closeMenusOutside(event.target);
        });

        document.addEventListener("keydown", function(event) {
            if (event.key === "Escape") {
                closeAllMenus();
            }
        });
    };

    var getBreakpoint = function(menu) {
        var breakpoint = parseInt(menu.getAttribute("data-mobile-breakpoint") || "980", 10);
        return !breakpoint || breakpoint < 320 ? 980 : breakpoint;
    };

    var syncMode = function(menu) {
        var isMobile = window.innerWidth <= getBreakpoint(menu);
        menu.classList.toggle("is-mobile-view", isMobile);

        if (!isMobile) {
            menu.classList.remove("is-open");
            closeNested(menu);
        }

        setMenuOpen(menu, isMobile && menu.classList.contains("is-open"));
    };

    var toggleSubmenu = function(menu, item) {
        if (!item) {
            return;
        }

        var expanded = item.classList.contains("is-submenu-open");
        var allowMultiple = closest(item, ".oneplugin-menu__submenu") !== null;

        if (!allowMultiple) {
            closeNested(menu, item);
        }

        setItemExpanded(item, !expanded);
    };

    var closeAfterLinkClick = function(menu, link) {
        if (menu.getAttribute("data-close-on-link-click") === "off") {
            return;
        }

        if (!menu.classList.contains("is-mobile-view")) {
            return;
        }

        if (isPlaceholderLink(link)) {
            return;
        }

        window.setTimeout(function() {
            closeMenu(menu);
        }, 0);
    };

    var setupMenu = function(menu) {
        if (menu.__onepluginReady) {
            syncMode(menu);
            return;
        }

        menu.__onepluginReady = true;

        forEachNode(menu.querySelectorAll(selectors.itemsWithChildren), function(item) {
            setItemExpanded(item, false);
        });

        var panel = menu.querySelector(selectors.panel);
        if (panel) {
            panel.setAttribute("aria-hidden", "false");
        }

        var overlay = menu.querySelector(selectors.overlay);
        if (overlay) {
            overlay.setAttribute("aria-hidden", "true");
            overlay.addEventListener("click", function() {
                closeMenu(menu);
            });
        }

        var toggle = menu.querySelector(selectors.toggle);
        if (toggle) {
            toggle.addEventListener("click", function(event) {
                event.preventDefault();

                if (menu.classList.contains("is-open")) {
                    closeMenu(menu);
                    return;
                }

                openMenu(menu);
            });
        }

        menu.addEventListener("click", function(event) {
            var button = closest(event.target, selectors.submenuToggle);
            if (button && menu.contains(button)) {
                var buttonItem = closest(button, selectors.item);
                if (!buttonItem) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();
                toggleSubmenu(menu, buttonItem);
                return;
            }

            var link = closest(event.target, ".oneplugin-menu__link");
            if (!link || !menu.contains(link)) {
                return;
            }

            var item = closest(link, selectors.item);
            var hasChildren = !!(item && item.matches(selectors.itemsWithChildren));
            var submenuTrigger = menu.getAttribute("data-submenu-trigger") || "hover";
            var indicatorHidden = menu.getAttribute("data-show-indicator") === "off";
            var isMobile = menu.classList.contains("is-mobile-view");
            var shouldToggleByLink = hasChildren && (
                isPlaceholderLink(link) ||
                (indicatorHidden && isMobile) ||
                (indicatorHidden && submenuTrigger === "click" && !isMobile)
            );

            if (shouldToggleByLink) {
                event.preventDefault();
                toggleSubmenu(menu, item);
                return;
            }

            closeAfterLinkClick(menu, link);
        });

        syncMode(menu);
    };

    var setupMenusIn = function(root) {
        if (!root || !root.querySelectorAll) {
            return;
        }

        if (root.matches && root.matches(selectors.nav)) {
            setupMenu(root);
        }

        forEachNode(root.querySelectorAll(selectors.nav), setupMenu);
    };

    var boot = function() {
        setupGlobalListeners();
        setupMenusIn(document);
    };

    var resizeQueued = false;
    var syncAllMenus = function() {
        if (resizeQueued) {
            return;
        }

        resizeQueued = true;
        raf(function() {
            resizeQueued = false;
            forEachNode(document.querySelectorAll(selectors.nav), syncMode);
        });
    };

    document.addEventListener("DOMContentLoaded", boot);
    window.addEventListener("load", boot);
    window.addEventListener("resize", syncAllMenus);

    if (document.readyState !== "loading") {
        boot();
    }

    if (window.MutationObserver) {
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                forEachNode(mutation.addedNodes, function(node) {
                    if (node.nodeType === 1) {
                        setupMenusIn(node);
                    }
                });
            });
        });
        observer.observe(document.documentElement, { childList: true, subtree: true });
    }
})();
