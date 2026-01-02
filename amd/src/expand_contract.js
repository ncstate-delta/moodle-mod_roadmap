// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Handle the expanding and contracting of roadmap nodes.
 *
 * @module     mod_roadmap/expand_contract
 * @copyright  2021 Steve Bader <smbader@ncsu.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
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
