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
 * Private roadmap module utility functions
 *
 * @package   mod_roadmap
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->dirroot/mod/roadmap/lib.php");

/**
 */
function roadmap_list_icons() {
    return [
        ['name' => 'announcement'],
        ['name' => 'assessment'],
        ['name' => 'input'],
        ['name' => 'membership'],
    ];
}


/**
 * Return the form elements used for configuring this objective
 *
 * @return array $elements - return the elements to be added to the form
 */
function roadmap_list_activities($course) {
    $completionoptions = [];
    
    // For activity completition we need to generate a list of activities the same way moodle does.
    // Conditions based on completion
    $completion = new completion_info($course);
    if ($completion->is_enabled()) {
        $modinfo = get_fast_modinfo($course);
        foreach($modinfo->cms as $id=>$cm) {
            // Add each course-module if it:
            // (a) has completion turned on
            // (b) is not the same as current course-module
            if ($cm->completion && $cm->modname != 'roadmap') {
                $completionoptions[]= ['coursemoduleid' => $cm->id, 'name' => $cm->name];
            }
        }
        asort($completionoptions);
    }

    return $completionoptions;
}