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
 * Prints course roadmap configuration form
 *
 * @package   mod_roadmap
 * @copyright 2020 NC State DELTA {@link http://delta.ncsu.edu}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/lib/formslib.php');

$id = required_param('id', PARAM_INT);

$url = new moodle_url('/mod/roadmap/configuration.php', ['id' => $id]);
$PAGE->set_url($url);

[$course, $cm] = get_course_and_cm_from_cmid($id, 'roadmap');
require_login($course, true, $cm);

$returnurl = new moodle_url('/course/view.php', ['id' => $course->id]);
$title = get_string('roadmapconfiguration', 'mod_roadmap');

$PAGE->set_title($title);
$PAGE->set_heading($cm->name);
$PAGE->set_pagelayout('incourse');

$roadmap = $DB->get_record('roadmap', ['id' => $cm->instance], '*', MUST_EXIST);

$configuration = new mod_roadmap\form\configuration($url->out(false), [
    'course' => $course,
    'cm' => $cm,
    'roadmap' => $roadmap,
    'colorpatterns' => roadmap_color_set_names(),
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

    // Any saves from this version on will clear the configuration field.  Soon to be deprecated.
    $roadmap->configuration = '';
    $roadmap->learningobjectives = $data->learningobjectivesconfiguration;
    $roadmap->colors = $data->phasecolorpattern;
    $roadmap->clodisplayposition = $data->displayposition;
    $roadmap->cloalignment = $data->cyclealignment;
    $roadmap->clodecoration = $data->cycledecoration;
    $roadmap->cloprefix = $data->cloprefix;

    $DB->update_record('roadmap', $roadmap);

    redirect($returnurl, 'Configuration Saved Successfully.', null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->heading($title, 3);
$configuration->display();
echo $OUTPUT->footer();
