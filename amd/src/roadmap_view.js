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
 * Handle the view of the roadmap.
 *
 * @module     mod_roadmap/roadmapview
 * @copyright  2021 Steve Bader <smbader@ncsu.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery'],
    function($) {

        /**
         * Roadmap view helper object.
         */
        var RoadmapView = function() {
            // Get progress, normalized

            $('li.roadmap span.bg').each(function() {
               var p = $(this).data('progress');

                // Clamp progress
                if (p < 0) {
                    p = 0;
                }
                if (p > 1) {
                    p = 1;
                }

                var circle = $(this).find('.roadmap-circle-progress')[0];

                // Get radius, calculate circumference
                var r = circle.getAttribute('r');
                var c = Math.PI * r * 2;

                circle.setAttribute('stroke-dashoffset', c - (c * p));
            });

            $('h2.roadmap-phase-title').each(function() {
                $(this).click(function() {
                    var cc = $(this).closest('.roadmap-phase').find('.roadmap-cycle-container');
                    cc.toggle();

                    var ind = $(this).find('.roadmap-phase-progress-indicators');
                    ind.toggle();
                });
            });
        };

        return {

            /**
             * Main initialisation.
             *
             * @return {RoadmapView} A new instance of PhasesConfig.
             * @method init
             */
            init: function() {
                return new RoadmapView();
            }
        };
    });
