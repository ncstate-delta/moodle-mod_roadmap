<?php
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
 * Web Service functions for steps.
 *
 * @package   mod_roadmap
 * @copyright 2024 NC State DELTA {@link http://delta.ncsu.edu}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_roadmap;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_multiple_structure;
use core_external\external_value;

/**
 * Web Service functions for roadmap configuration.
 *
 * @copyright 2024 NC State DELTA {@link http://delta.ncsu.edu}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class external extends external_api {

    /**
     * Fetch the course modules with activity completion and expected completion time.
     *
     * @param   int     $contextid  The ID of the course module of the roadmap asking for information.
     * @param   int     $courseid   The ID of the course which modules will be fetched.
     * @param   string  $pageurl    The page url of the course in question
     * @return  array               As described in fetch_and_start_tour_returns
     */
    public static function fetch_course_modules_for_steps($contextid, $courseid, $pageurl) {
        global $CFG;
        require_once($CFG->dirroot . '/mod/roadmap/locallib.php');
        require_once($CFG->libdir.'/outputcomponents.php');

        $params = self::validate_parameters(self::fetch_course_modules_for_steps_parameters(), [
            'context'   => $contextid,
            'course'    => $courseid,
            'pageurl'   => $pageurl,
        ]);

        $context = \context_helper::instance_by_id($params['context']);
        self::validate_context($context);

        $course = \get_course($courseid);
        $activities = \roadmap_list_activities($course);

        $dateto = time() + YEARSECS;
        $customexpectedcomplete =
              \html_writer::div(\html_writer::select_time('months', 'tomonth', $dateto), 'form-group fitem mt-2')
            . \html_writer::div(\html_writer::select_time('days', 'today', $dateto), 'form-group fitem mt-2 ml-2')
            . \html_writer::div(\html_writer::select_time('years', 'toyear', $dateto), 'form-group fitem mt-2 ml-2')
            . \html_writer::div(\html_writer::select_time('hours', 'tohour', $dateto), 'form-group fitem mt-2 ml-4 mr-1')
            . '<strong>:</strong>'
            . \html_writer::div(\html_writer::select_time('minutes', 'tominute', $dateto), 'form-group fitem mt-2 ml-1');

        // TODO: Pull all course modules that have activity completion in the same order displayed
        // displayed on the course index.  Also pull the expected completion date if it exists.

        return [ 'sections' => $activities, 'customdatefields' => $customexpectedcomplete ];
    }

    /**
     * The parameters for fetch_course_modules_for_steps.
     *
     * @return external_function_parameters
     */
    public static function fetch_course_modules_for_steps_parameters() {
        return new external_function_parameters([
            'context'   => new external_value(PARAM_INT, 'Context ID'),
            'course'    => new external_value(PARAM_INT, 'Course ID'),
            'pageurl'   => new external_value(PARAM_URL, 'Page URL'),
        ]);
    }

    /**
     * The return configuration for fetch_course_modules_for_steps.
     *
     * @return external_single_structure
     */
    public static function fetch_course_modules_for_steps_returns() {
        return new external_single_structure(
            [
                'sections' => new external_multiple_structure(
                    new external_single_structure([
                        'id'        => new external_value(PARAM_INT, 'Course Module ID'),
                        'name'      => new external_value(PARAM_RAW, 'Tour Name'),
                        'coursemodules' => new external_multiple_structure(
                            new external_single_structure([
                                'id'                    => new external_value(PARAM_INT, 'Course Module ID'),
                                'name'                  => new external_value(PARAM_RAW, 'Tour Name'),
                                'completionexpecteddatetime' => new external_value(PARAM_RAW, 'Expected Completion Date'),
                                'completionexpectedreadable' => new external_value(PARAM_RAW, 'Readable Expected Completion Date'),
                            ], 'Course Modules')
                        ),
                    ], 'Sections')
                ),
                'customdatefields' => new external_value( PARAM_RAW, 'Custom Date Fields'),
            ]
        );
    }


    /**
     * Fetch the course modules with activity completion and expected completion time.
     *
     * @param   int     $colorid  The ID of the color set being fetched
     * @return  array             An array of hex colors as strings
     */
    public static function fetch_color_pattern($colorid) {
        global $CFG;
        require_once($CFG->dirroot . '/mod/roadmap/locallib.php');

        $params = self::validate_parameters(self::fetch_color_pattern_parameters(), [
            'colorid'   => $colorid,
        ]);

        $colors = \roadmap_color_sets($params['colorid']);

        return $colors;
    }

    /**
     * The parameters for fetch_color_pattern.
     *
     * @return external_function_parameters
     */
    public static function fetch_color_pattern_parameters() {
        return new external_function_parameters([
            'colorid'   => new external_value(PARAM_INT, 'Color ID'),
        ]);
    }

    /**
     * The return configuration for fetch_color_pattern.
     *
     * @return external_multiple_structure
     */
    public static function fetch_color_pattern_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                    'color'      => new external_value(PARAM_RAW, 'Color Hex Value'),
                ], 'Colors')
        );
    }

}
