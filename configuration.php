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
 * Renderer class for mod_roadmap
 *
 * @package   mod_roadmap
 * @copyright 2020 NC State DELTA {@link http://delta.ncsu.edu}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$cmid = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('', $cmid, 0, false, MUST_EXIST);
$context = context_module::instance($cm->id);

$params = array('id' => $cm->course);
$course = $DB->get_record('course', $params, '*', MUST_EXIST);

require_login($course);

$urlparams = array('id' => $cmid);
$url = new moodle_url('/mod/roadmap/configuration.php', $urlparams);

$title = get_string('roadmapconfiguration', 'mod_roadmap');

$PAGE->set_url($url);
$PAGE->set_title($title);
$PAGE->set_heading($cm->name);
$PAGE->set_pagelayout('incourse');

$output = $PAGE->get_renderer('mod_roadmap');

echo $output->header();

echo $output->heading($title, 3);

//$roadmap_configuration = new \mod_roadmap\output\configuration($course->id, $cmid);
//$page = $output->render_configuration($roadmap_configuration);
//echo $page;

$configuration_form = new mod_roadmap_configuration_form('configuration.php', [
    'course' => $course,
    'cm' => $cm,
], 'post', '', array('id' => 'mformroadmap'));

$configuration_form->display();

echo $output->footer();
