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
 * Individual activity view - not used for roadmap.  This will forward to config or course view.
 *
 * @package   mod_roadmap
 * @copyright 2020 NC State DELTA {@link http://delta.ncsu.edu}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../config.php");
require_once("lib.php");

$id = required_param('id', PARAM_INT); // Course Module ID.
$url = new moodle_url('/mod/roadmap/view.php', ['id' => $id]);

$PAGE->set_url($url);

if (!$cm = get_coursemodule_from_id('roadmap', $id)) {
    moodle_exception('invalidcoursemodule');
}

if (! $course = $DB->get_record("course", ["id" => $cm->course])) {
    moodle_exception('coursemisconf');
}

$context = context_module::instance($cm->id);

require_course_login($course, false, $cm);

// If allowed, forward to configuration page
// Show configuration link if editing is on.
if (has_capability('mod/roadmap:configure', $context)) {
    redirect(new \moodle_url('/mod/roadmap/configuration.php', ['id' => $cm->id]));
} else {
    // Redirect to course page.
    redirect(new \moodle_url('/course/view.php', ['id' => $cm->course]));
}
