(function() {
    var config = window.OnePluginLightImageAltFix || {};
    var companyName = config.companyName || '';
    if (!companyName) {
        return;
    }

    var applyAltText = function(root) {
        var scope = root && root.querySelectorAll ? root : document;
        scope.querySelectorAll('img').forEach(function(img) {
            img.setAttribute('alt', companyName);
            img.setAttribute('title', companyName);
            if (img.closest('a')) {
                img.closest('a').setAttribute('title', companyName);
            }
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            applyAltText(document);
        });
    } else {
        applyAltText(document);
    }

    if (window.MutationObserver) {
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(node) {
                    if (!node || node.nodeType !== 1) {
                        return;
                    }

                    if (node.tagName && node.tagName.toLowerCase() === 'img') {
                        node.setAttribute('alt', companyName);
                    }

                    applyAltText(node);
                });
            });
        });

        observer.observe(document.documentElement, {
            childList: true,
            subtree: true
        });
    }
})();

