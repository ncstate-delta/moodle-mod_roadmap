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
 * Handle the configuration of the roadmap.
 *
 * @module     mod_roadmap/roadmapconfig
 * @copyright  2021 Steve Bader <smbader@ncsu.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['mod_roadmap/learningobjectivesconfig',
        'mod_roadmap/step_icon_select',
        'mod_roadmap/roadmap_config',
    ],
    function(
        LearningObjectivesConfig,
        StepIconSelector,
        RoadmapConfig,
    ) {

        /**
         * Learning objectives config object.
         * @param {String} loInputSelector The hidden input field selector for learning objectives.
         * @param {String} loInputConfig The hidden input configuration for learning objectives.
         * @param {String} rmInputSelector The hidden input field selector for the roadmap.
         * @param {String} rmInputConfig The hidden input configuration for the roadmap.
         */
        var Configuration = function(loInputSelector, loInputConfig, rmInputSelector, rmInputConfig) {
            LearningObjectivesConfig.init(loInputSelector, loInputConfig);
            RoadmapConfig.init(rmInputSelector, rmInputConfig);
            StepIconSelector.init();
        };

        return {

            /**
             * Main initialisation.
             *
             * @param {String} loInputSelector The hidden input field selector for learning objectives.
             * @param {String} loInputConfig The hidden input configuration for learning objectives.
             * @param {String} rmInputSelector The hidden input field selector for the roadmap.
             * @param {String} rmInputConfig The hidden input configuration for the roadmap.
             * @return {Configuration} A new instance of PhasesConfig.
             * @method init
             */
            init: function(loInputSelector, loInputConfig, rmInputSelector, rmInputConfig) {
                return new Configuration(loInputSelector, loInputConfig, rmInputSelector, rmInputConfig);
            }
        };
    });
