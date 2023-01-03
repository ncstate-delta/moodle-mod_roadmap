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

function roadmap_configuration_edit($roadmapid) {
    global $DB;
    $data = new \stdClass();
    $data->phases = [];

    $phases = $DB->get_records('roadmap_phase', ['roadmapid' => $roadmapid], 'sort ASC');
    foreach ($phases as $phase) {
        $phase->cycles = [];

        $cycles = $DB->get_records('roadmap_cycle', ['phaseid' => $phase->id], 'sort ASC');
        foreach ($cycles as $cycle) {
            $cycle->steps = [];

            $steps = $DB->get_records('roadmap_step', ['cycleid' => $cycle->id], 'sort ASC');
            foreach ($steps as $step) {

                roadmap_datetime_picker_data($step);
                $cycle->steps[] = $step;
            }
            $phase->cycles[] = $cycle;
        }
        $data->phases[] = $phase;
    }

    return json_encode($data);
}

function roadmap_delete_phase($phaseid) {
    global $DB;

    // Delete cycles and cascade delete steps.
    $cycles = $DB->get_records('roadmap_cycle', ['phaseid' => $phaseid]);
    foreach ($cycles as $cycle) {
        roadmap_delete_cycle($cycle->id);
    }

    $DB->delete_records('roadmap_phase', ['id' => $phaseid]);
}

function roadmap_delete_cycle($cycleid) {
    global $DB;

    // Delete all child steps of the cycle
    $DB->delete_records('roadmap_step', ['cycleid' => $cycleid]);
    // Delete the cycle itself.
    $DB->delete_records('roadmap_cycle', ['id' => $cycleid]);
}



function roadmap_configuration_save($configjson, $roadmap_id, $conversion = false) {
    global $DB;

    $data = json_decode($configjson);

    if (!isset($data->phases)) {
        $data->phases = [];
    }

    // Delete phases that previously existed. that were deleted this submission.
    if (property_exists($data, 'phaseDeletes')) {
        $phase_deletes = explode(',', $data->phaseDeletes);
        foreach ($phase_deletes as $phase_delete) {
            // Cascade deletes to cycles and steps below.
            roadmap_delete_phase($phase_delete);
        }
    }

    // Delete cycles that previously existed that were deleted this submission.
    if (property_exists($data, 'cycleDeletes')) {
        $cycle_deletes = explode(',', $data->cycleDeletes);
        foreach ($cycle_deletes as $cycle_delete) {
            // Cascade deletes to cycles and steps below.
            roadmap_delete_cycle($cycle_delete);
        }
    }

    // Delete steps that previously existed that were deleted this submission.
    if (property_exists($data, 'stepDeletes')) {
        $step_deletes = explode(',', $data->stepDeletes);
        foreach ($step_deletes as $step_delete) {
            $DB->delete_records('roadmap_step', ['id' => $step_delete]);
        }
    }

    $phase_sort = 0;
    foreach ($data->phases as $phase) {
        if (!isset($phase->cycles)) {
            $phase->cycles = [];
        }

        // Save the phase specific data
        $phase_data = [
            'title' => $phase->title,
            'sort' => $phase_sort,
            'roadmapid' => $roadmap_id,
        ];

        // Check to see if the step id exists for this course.
        $sql = "SELECT rp.* 
                      FROM mdl_roadmap_phase rp 
                     WHERE rp.roadmapid = ? AND rp.id = ?";

        $orig_phase = $DB->get_record_sql($sql, [$roadmap_id, $phase->id]);

        if ($orig_phase && !$conversion) {
            // Update
            $phase_data['id'] = $phase->id;
            $DB->update_record('roadmap_phase', $phase_data);
        } else {
            // Add
            $phase->id = $DB->insert_record('roadmap_phase', $phase_data);
        }

        $cycle_sort = 0;
        foreach ($phase->cycles as $cycle) {
            if (!isset($cycle->steps)) {
                $cycle->steps = [];
            }

            // Save the phase specific data
            $cycle_data = [
                'title' => $cycle->title,
                'subtitle' => $cycle->subtitle,
                'pagelink' => $cycle->pagelink,
                'learningobjectives' => $cycle->learningobjectives,
                'sort' => $cycle_sort,
                'phaseid' => $phase->id,
            ];

            // Check to see if the step id exists for this course.
            $sql = "SELECT rc.* 
                      FROM mdl_roadmap_cycle rc
                      JOIN mdl_roadmap_phase rp ON rp.id = rc.phaseid
                     WHERE rp.roadmapid = ? AND rc.id = ?";

            $orig_cycle = $DB->get_record_sql($sql, [$roadmap_id, $cycle->id]);

            if ($orig_cycle && !$conversion) {
                // Update
                $cycle_data['id'] = $cycle->id;
                $DB->update_record('roadmap_cycle', $cycle_data);
            } else {
                // Add
                $cycle->id = $DB->insert_record('roadmap_cycle', $cycle_data);
            }

            $step_sort = 0;
            foreach ($cycle->steps as $step) {

                if (
                    property_exists($step, 'completionexpected_month') &&
                    property_exists($step, 'completionexpected_day') &&
                    property_exists($step, 'completionexpected_year') &&
                    property_exists($step, 'completionexpected_hour') &&
                    property_exists($step, 'completionexpected_minute')
                ) {
                    $strdatetime = sprintf("%02d", $step->completionexpected_month) . '/' .
                        sprintf("%02d", $step->completionexpected_day) . '/' .
                        sprintf("%04d", $step->completionexpected_year) . ' ' .
                        sprintf("%02d", $step->completionexpected_hour) . ':' .
                        sprintf("%02d", $step->completionexpected_minute) . ':00';
                    $step->completionexpected_datetime = strtotime($strdatetime);
                }

                // Save the phase specific data
                $step_data = [
                    'rollovertext' => $step->rollovertext,
                    'stepicon' => $step->stepicon,
                    'completionmodules' => $step->completionmodules,
                    'linksingleactivity' => $step->linksingleactivity,
                    'pagelink' => $step->pagelink,
                    'expectedcomplete' => $step->expectedcomplete,
                    'completionexpected_datetime' => $step->completionexpected_datetime,
                    'sort' => $step_sort,
                    'cycleid' => $cycle->id,
                ];

                // Check to see if the step id exists for this course.
                $sql = "SELECT rs.* 
                          FROM mdl_roadmap_step rs
                          JOIN mdl_roadmap_cycle rc ON rc.id = rs.cycleid
                          JOIN mdl_roadmap_phase rp ON rp.id = rc.phaseid
                         WHERE rp.roadmapid = ? AND rs.id = ?";

                $orig_step = $DB->get_record_sql($sql, [$roadmap_id, $step->id]);

                if ($orig_step && !$conversion) {
                    // Update
                    $step_data['id'] = $step->id;
                    $DB->update_record('roadmap_step', $step_data);
                } else {
                    // Add
                    $step->id = $DB->insert_record('roadmap_step', $step_data);
                }
                $step_sort++;
            }
            $cycle_sort++;
        }
        $phase_sort++;
    }

    return true;
}

function roadmap_datetime_picker_data($step) {
    // This is the old, sad way.
    if (!property_exists($step, 'completionexpected_day')) {
        $step->completionexpected_day = date("d");
    }
    if (!property_exists($step, 'completionexpected_month')) {
        $step->completionexpected_month = date("m");
    }
    if (!property_exists($step, 'completionexpected_year')) {
        $step->completionexpected_year = date("Y");
    }
    if (!property_exists($step, 'completionexpected_hour')) {
        $step->completionexpected_hour = date("H");
    }
    if (!property_exists($step, 'completionexpected_minute')) {
        $step->completionexpected_minute = date("i");
    }
    // The new better way takes priority.
    if (property_exists($step, 'completionexpected_datetime')) {
        if ($step->completionexpected_datetime) {
            $step->completionexpected_day = date("d", $step->completionexpected_datetime);
            $step->completionexpected_month = date("m", $step->completionexpected_datetime);
            $step->completionexpected_year = date("Y", $step->completionexpected_datetime);
            $step->completionexpected_hour = date("H", $step->completionexpected_datetime);
            $step->completionexpected_minute = date("i", $step->completionexpected_datetime);
        }
    }

    $step->days = [];
    for ($i = 1; $i <= 31; $i++) {
        $step->days[] = ['val' => sprintf("%02d", $i), 'txt' => sprintf("%02d", $i), 'sel' => ($step->completionexpected_day == sprintf("%02d", $i))];
    }
    $step->months = [];
    $step->months[] = ['val' => '01', 'txt' => 'January', 'sel' => ($step->completionexpected_month == '01')];
    $step->months[] = ['val' => '02', 'txt' => 'February', 'sel' => ($step->completionexpected_month == '02')];
    $step->months[] = ['val' => '03', 'txt' => 'March', 'sel' => ($step->completionexpected_month == '03')];
    $step->months[] = ['val' => '04', 'txt' => 'April', 'sel' => ($step->completionexpected_month == '04')];
    $step->months[] = ['val' => '05', 'txt' => 'May', 'sel' => ($step->completionexpected_month == '05')];
    $step->months[] = ['val' => '06', 'txt' => 'June', 'sel' => ($step->completionexpected_month == '06')];
    $step->months[] = ['val' => '07', 'txt' => 'July', 'sel' => ($step->completionexpected_month == '07')];
    $step->months[] = ['val' => '08', 'txt' => 'August', 'sel' => ($step->completionexpected_month == '08')];
    $step->months[] = ['val' => '09', 'txt' => 'September', 'sel' => ($step->completionexpected_month == '09')];
    $step->months[] = ['val' => '10', 'txt' => 'October', 'sel' => ($step->completionexpected_month == '10')];
    $step->months[] = ['val' => '11', 'txt' => 'November', 'sel' => ($step->completionexpected_month == '11')];
    $step->months[] = ['val' => '12', 'txt' => 'December', 'sel' => ($step->completionexpected_month == '12')];

    $currentyear = date("Y");
    $step->years = [];
    for ($i = $currentyear; $i <= $currentyear + 6; $i++) {
        $step->years[] = ['val' => $i, 'txt' => $i, 'sel' => ($step->completionexpected_year == $i)];
    }
    $step->hours = [];
    for ($i = 0; $i <= 23; $i++) {
        $step->hours[] = ['val' => sprintf("%02d", $i), 'txt' => sprintf("%02d", $i), 'sel' => ($step->completionexpected_hour == sprintf("%02d", $i))];
    }
    $step->minutes = [];
    for ($i = 0; $i <= 59; $i++) {
        $step->minutes[] = ['val' => sprintf("%02d", $i), 'txt' => sprintf("%02d", $i), 'sel' => ($step->completionexpected_minute == sprintf("%02d", $i))];
    }
}

function roadmap_datetime_picker_options() {
    $day = [];
    for ($i = 1; $i <= 31; $i++) {
        $day[] = ['val' => sprintf("%02d", $i), 'txt' => sprintf("%02d", $i)];
    }
    $month = [];
    $month[] = ['val' => '01', 'txt' => 'January'];
    $month[] = ['val' => '02', 'txt' => 'February'];
    $month[] = ['val' => '03', 'txt' => 'March'];
    $month[] = ['val' => '04', 'txt' => 'April'];
    $month[] = ['val' => '05', 'txt' => 'May'];
    $month[] = ['val' => '06', 'txt' => 'June'];
    $month[] = ['val' => '07', 'txt' => 'July'];
    $month[] = ['val' => '08', 'txt' => 'August'];
    $month[] = ['val' => '09', 'txt' => 'September'];
    $month[] = ['val' => '10', 'txt' => 'October'];
    $month[] = ['val' => '11', 'txt' => 'November'];
    $month[] = ['val' => '12', 'txt' => 'December'];

    $currentyear = date("Y");
    $year = [];
    for ($i = $currentyear; $i <= $currentyear + 6; $i++) {
        $year[] = ['val' => $i, 'txt' => $i];
    }
    $hour = [];
    for ($i = 0; $i <= 23; $i++) {
        $hour[] = ['val' => sprintf("%02d", $i), 'txt' => sprintf("%02d", $i)];
    }
    $minute = [];
    for ($i = 0; $i <= 59; $i++) {
        $minute[] = ['val' => sprintf("%02d", $i), 'txt' => sprintf("%02d", $i)];
    }

    return ['days' => $day, 'months' => $month, 'years' => $year, 'hours' => $hour, 'minutes' => $minute];
}

function roadmap_color_sets($id = -1) {
    $colors = [
        0 => ['#4156A1', '#427E93', '#008473', '#6F7D1C', '#D14905'],
    ];
    if ($id >= 0) {
        return $colors[$id];
    }
    return $colors;
}

/**
 */
function roadmap_list_icons() {
    global $CFG;
    $result = [];

    $iconsfolder = $CFG->dirroot . '/mod/roadmap/pix/icons/';
    $icons = scandir($iconsfolder);

    foreach ($icons as $icon) {
        // PHP 8.0: we can use the function str_ends_with.
        if (substr($icon, -4) === '.svg') {
            $result[] = ['name' => substr($icon, 0, -4)];
        }
    }

    return $result;
}


/**
 * Return the form elements used for configuring this objective
 *
 * @return array $elements - return the elements to be added to the form
 */
function roadmap_list_activities($course) {
    $completionoptions = [];
    $index = 0;

    // For activity completition we need to generate a list of activities the same way moodle does.
    // Conditions based on completion.
    $completion = new completion_info($course);
    if ($completion->is_enabled()) {
        $modinfo = get_fast_modinfo($course);

        foreach ($modinfo->cms as $id => $cm) {
            // Add each course-module if it:
            // (a) has completion turned on.
            // (b) is not the same as current course-module.
            if ($cm->completion && $cm->modname != 'roadmap') {
                $completionoptions[$index] = ['id' => $index, 'coursemoduleid' => $cm->id, 'name' => $cm->name];
                $index += 1;
            }
        }
        asort($completionoptions);
    }

    return $completionoptions;
}