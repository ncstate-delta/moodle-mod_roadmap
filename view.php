<?php
require_once("../../config.php");
require_once("lib.php");

$id = required_param('id', PARAM_INT);                 // Course Module ID
$url = new moodle_url('/mod/roadmap/view.php', array('id'=>$id));

$PAGE->set_url($url);

if (!$cm = get_coursemodule_from_id('roadmap', $id)) {
    print_error('invalidcoursemodule');
}

$context = context_module::instance($cm->id);

// If allowed, forward to configuration page
// Show configuration link if editing is on.
if (has_capability('mod/roadmap:configure', $context)) {
    redirect(new \moodle_url('/mod/roadmap/configuration.php', array('id' => $cm->id)));
} else {
    // course page
    redirect(new \moodle_url('/course/view.php', array('id' => $cm->course)));
}