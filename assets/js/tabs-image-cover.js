(function() {
    var selector = 'img.dipi-at-panel-image';
    var applyCoverClass = function(root) {
        var scope = root && root.querySelectorAll ? root : document;
        if (scope.matches && scope.matches(selector)) {
            scope.classList.add('cover-img');
        }

        scope.querySelectorAll(selector).forEach(function(img) {
            img.classList.add('cover-img');
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            applyCoverClass(document);
        });
    } else {
        applyCoverClass(document);
    }

    if (window.MutationObserver) {
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(node) {
                    if (!node || node.nodeType !== 1) {
                        return;
                    }

                    applyCoverClass(node);
                });
            });
        });

        observer.observe(document.documentElement, {
            childList: true,
            subtree: true
        });
    }
})();

