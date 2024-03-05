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
 * Prints roadmap configuration form
 *
 * @package   mod_roadmap
 * @copyright 2020 NC State DELTA {@link http://delta.ncsu.edu}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/lib/formslib.php');

$coursemoduleid = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('', $coursemoduleid, 0, false, MUST_EXIST);
$context = context_module::instance($cm->id);

$params = ['id' => $cm->course];
$course = $DB->get_record('course', $params, '*', MUST_EXIST);

require_login($course);

$urlparams = ['id' => $coursemoduleid];
$url = new moodle_url('/mod/roadmap/configuration.php', $urlparams);
$returnurl = new moodle_url('/course/view.php', ['id' => $course->id]);

$title = get_string('roadmapconfiguration', 'mod_roadmap');

$PAGE->set_url($url);
$PAGE->set_title($title);
$PAGE->set_heading($cm->name);
$PAGE->set_pagelayout('incourse');

$output = $PAGE->get_renderer('mod_roadmap');

$roadmap = $DB->get_record('roadmap', ['id' => $cm->instance], '*', MUST_EXIST);

$configuration = new mod_roadmap\form\configuration($url->out(false), [
    'course' => $course,
    'cm' => $cm,
    'roadmap' => $roadmap,
], 'post', '', ['id' => 'mformroadmap']);

// Form cancelled.
if ($configuration->is_cancelled()) {
    redirect($returnurl);
}

// Get form data.
$data = $configuration->get_submitted_data();
if ($data) {

    // Update phase, cycle, step tables.
    roadmap_configuration_save($data->roadmapconfiguration, $roadmap->id);

    $sql = "UPDATE {roadmap}
                   SET configuration = ?,
                       learningobjectives = ?,
                       colors = ?,
                       clodisplayposition = ?,
                       cloalignment = ?,
                       clodecoration = ?,
                       cloprefix = ?
                 WHERE id = ?";

    // Any saves from this version on will clear the configuration field.  Soon to be deprecated.
    $DB->execute($sql, ['', $data->learningobjectivesconfiguration,
        $data->phasecolorpattern, $data->displayposition, $data->cyclealignment, $data->cycledecoration,
        $data->cloprefix, $roadmap->id]);

    redirect($returnurl, 'Configuration Saved Successfully.', null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $output->header();
echo $output->heading($title, 3);
$configuration->display();
echo $output->footer();
