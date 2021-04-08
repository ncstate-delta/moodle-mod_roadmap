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
 * @package    mod_roadmap
 * @copyright  2021 Steve Bader <smbader@ncsu.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/notification', 'core/templates', 'mod_roadmap/expand_contract',
        'mod_roadmap/learningobjectivesconfig', 'mod_roadmap/step_icon_select', 'mod_roadmap/roadmap_config'],
    function($, notification, templates, ec, LearningObjectivesConfig, StepIconSelector, RoadmapConfig) {

        /**
         * Learning objectives config object.
         * @param {String} inputSelector The hidden input field selector.
         */
        var Configuration = function(lo_inputSelector, lo_inputConfig, rm_inputSelector, rm_inputConfig) {
            LearningObjectivesConfig.init(lo_inputSelector, lo_inputConfig);
            RoadmapConfig.init(rm_inputSelector, rm_inputConfig);
            StepIconSelector.init();
        };

        return {

            /**
             * Main initialisation.
             *
             * @param {String} inputSelector The hidden input field selector.
             * @return {LearningObjectivesConfig} A new instance of PhasesConfig.
             * @method init
             */
            init: function(lo_inputSelector, lo_inputConfig, rm_inputSelector, rm_inputConfig) {
                return new Configuration(lo_inputSelector, lo_inputConfig, rm_inputSelector, rm_inputConfig);
            }
        };
    });
