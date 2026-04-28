(function() {
    var selector = '.dipi_masonry_gallery_container img';
    var applyMasonryIndexClasses = function() {
        document.querySelectorAll(selector).forEach(function(img, index) {
            var position = index % 4;
            var isOuterPair = position === 0 || position === 3;
            img.classList.toggle('oneplugin-masonry-outer-pair', isOuterPair);
            img.classList.toggle('oneplugin-masonry-inner-pair', !isOuterPair);
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', applyMasonryIndexClasses);
    } else {
        applyMasonryIndexClasses();
    }

    if (window.MutationObserver) {
        var observer = new MutationObserver(applyMasonryIndexClasses);
        observer.observe(document.documentElement, {
            childList: true,
            subtree: true
        });
    }
})();

