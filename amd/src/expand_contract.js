define(['jquery', 'core/url'], function($, url) {

    var expandedImage = $('<img alt="" src="' + url.imageUrl('t/expanded') + '"/>');
    var collapsedImage = $('<img alt="" src="' + url.imageUrl('t/collapsed') + '"/>');

    /*
     * Class names to apply when expanding/collapsing nodes.
     */
    var CLASSES = {
        EXPAND: 'fa-caret-right',
        COLLAPSE: 'fa-caret-down'
    };

    return {
        /**
         * Expand or collapse a selected node.
         *
         * @param  {object} targetnode The node that we want to expand / collapse
         * @param  {object} thisnode The node that was clicked.
         */
        expandCollapse: function(targetnode, thisnode) {
            if (targetnode.hasClass('hide')) {
                targetnode.removeClass('hide');
                targetnode.addClass('visible');
                targetnode.attr('aria-expanded', true);
                thisnode.find('i.fa').removeClass(CLASSES.EXPAND);
                thisnode.find('i.fa').addClass(CLASSES.COLLAPSE);
                thisnode.find('img.icon').attr('src', expandedImage.attr('src'));
            } else {
                targetnode.removeClass('visible');
                targetnode.addClass('hide');
                targetnode.attr('aria-expanded', false);
                thisnode.find('i.fa').removeClass(CLASSES.COLLAPSE);
                thisnode.find('i.fa').addClass(CLASSES.EXPAND);
                thisnode.find('img.icon').attr('src', collapsedImage.attr('src'));
            }
        },
    };
});
