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
 * Prepare phase, cycle, and step data for mustache templates.
 *
 * @global object
 * @param integer $roadmapid
 * @return string json encoded data for the configuration form.
 */
function roadmap_configuration_edit($roadmapid) {
    global $DB, $CFG;
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
                $step->expectedcomplete = (bool)$step->expectedcomplete;
                $step->linksingleactivity = (bool)$step->linksingleactivity;
                $step->iconurl = $CFG->wwwroot . '/mod/roadmap/icon.php?name=' . $step->stepicon . '&percent=100&flags=n';
                $step->sort = (int)$step->sort;

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

    // Delete all child steps of the cycle.
    $DB->delete_records('roadmap_step', ['cycleid' => $cycleid]);
    // Delete the cycle itself.
    $DB->delete_records('roadmap_cycle', ['id' => $cycleid]);
}



function roadmap_configuration_save($configjson, $roadmapid, $conversion = false) {
    global $DB;

    $data = json_decode($configjson);

    if (!isset($data->phases)) {
        $data->phases = [];
    }

    // Delete phases that previously existed. that were deleted this submission.
    if (property_exists($data, 'phaseDeletes')) {
        $phasedeletes = explode(',', $data->phaseDeletes);
        foreach ($phasedeletes as $phasedelete) {
            // Cascade deletes to cycles and steps below.
            roadmap_delete_phase($phasedelete);
        }
    }

    // Delete cycles that previously existed that were deleted this submission.
    if (property_exists($data, 'cycleDeletes')) {
        $cycledeletes = explode(',', $data->cycleDeletes);
        foreach ($cycledeletes as $cycledelete) {
            // Cascade deletes to cycles and steps below.
            roadmap_delete_cycle($cycledelete);
        }
    }

    // Delete steps that previously existed that were deleted this submission.
    if (property_exists($data, 'stepDeletes')) {
        $stepdeletes = explode(',', $data->stepDeletes);
        foreach ($stepdeletes as $stepdelete) {
            $DB->delete_records('roadmap_step', ['id' => $stepdelete]);
        }
    }

    $phasesort = 0;
    foreach ($data->phases as $phase) {
        if (!isset($phase->cycles)) {
            $phase->cycles = [];
        }

        // Save the phase specific data.
        $phasedata = [
            'title' => $phase->title,
            'sort' => $phasesort,
            'roadmapid' => $roadmapid,
        ];

        // Check to see if the step id exists for this course.
        $sql = "SELECT rp.*
                      FROM {roadmap_phase} rp
                     WHERE rp.roadmapid = ? AND rp.id = ?";

        $origphase = $DB->get_record_sql($sql, [$roadmapid, $phase->id]);

        if ($origphase && !$conversion) {
            // Update the phase.
            $phasedata['id'] = $phase->id;
            $DB->update_record('roadmap_phase', $phasedata);
        } else {
            // Add the phase.
            $phase->id = $DB->insert_record('roadmap_phase', $phasedata);
        }

        $cyclesort = 0;
        foreach ($phase->cycles as $cycle) {
            if (!isset($cycle->steps)) {
                $cycle->steps = [];
            }

            // Save the phase specific data.
            $cycledata = [
                'title' => $cycle->title,
                'subtitle' => $cycle->subtitle,
                'pagelink' => $cycle->pagelink,
                'learningobjectives' => $cycle->learningobjectives,
                'sort' => $cyclesort,
                'phaseid' => $phase->id,
            ];

            // Check to see if the step id exists for this course.
            $sql = "SELECT rc.*
                      FROM {roadmap_cycle} rc
                      JOIN {roadmap_phase} rp ON rp.id = rc.phaseid
                     WHERE rp.roadmapid = ? AND rc.id = ?";

            $origcycle = $DB->get_record_sql($sql, [$roadmapid, $cycle->id]);

            if ($origcycle && !$conversion) {
                // Update the cycle.
                $cycledata['id'] = $cycle->id;
                $DB->update_record('roadmap_cycle', $cycledata);
            } else {
                // Add the cycle.
                $cycle->id = $DB->insert_record('roadmap_cycle', $cycledata);
            }

            $stepsort = 0;
            foreach ($cycle->steps as $step) {

                if (
                    property_exists($step, 'completionexpectedmonth') &&
                    property_exists($step, 'completionexpectedday') &&
                    property_exists($step, 'completionexpectedyear') &&
                    property_exists($step, 'completionexpectedhour') &&
                    property_exists($step, 'completionexpectedminute')
                ) {
                    $strdatetime = sprintf("%02d", $step->completionexpectedmonth) . '/' .
                        sprintf("%02d", $step->completionexpectedday) . '/' .
                        sprintf("%04d", $step->completionexpectedyear) . ' ' .
                        sprintf("%02d", $step->completionexpectedhour) . ':' .
                        sprintf("%02d", $step->completionexpectedminute) . ':00';
                    $step->completionexpected_datetime = strtotime($strdatetime);
                }

                // Save the phase specific data.
                $stepdata = [
                    'rollovertext' => $step->rollovertext,
                    'stepicon' => $step->stepicon,
                    'completionmodules' => $step->completionmodules,
                    'linksingleactivity' => $step->linksingleactivity,
                    'pagelink' => $step->pagelink,
                    'expectedcomplete' => $step->expectedcomplete,
                    'completionexpected_datetime' => $step->completionexpected_datetime,
                    'sort' => $stepsort,
                    'cycleid' => $cycle->id,
                ];

                // Check to see if the step id exists for this course.
                $sql = "SELECT rs.*
                          FROM {roadmap_step} rs
                          JOIN {roadmap_cycle} rc ON rc.id = rs.cycleid
                          JOIN {roadmap_phase} rp ON rp.id = rc.phaseid
                         WHERE rp.roadmapid = ? AND rs.id = ?";

                $origstep = $DB->get_record_sql($sql, [$roadmapid, $step->id]);

                if ($origstep && !$conversion) {
                    // Update the step.
                    $stepdata['id'] = $step->id;
                    $DB->update_record('roadmap_step', $stepdata);
                } else {
                    // Add the step.
                    $step->id = $DB->insert_record('roadmap_step', $stepdata);
                }
                $stepsort++;
            }
            $cyclesort++;
        }
        $phasesort++;
    }

    return true;
}

function roadmap_datetime_picker_data($step) {
    // This is the old, sad way.
    if (!property_exists($step, 'completionexpectedday')) {
        $step->completionexpectedday = date("d");
    }
    if (!property_exists($step, 'completionexpectedmonth')) {
        $step->completionexpectedmonth = date("m");
    }
    if (!property_exists($step, 'completionexpectedyear')) {
        $step->completionexpectedyear = date("Y");
    }
    if (!property_exists($step, 'completionexpectedhour')) {
        $step->completionexpectedhour = date("H");
    }
    if (!property_exists($step, 'completionexpectedminute')) {
        $step->completionexpectedminute = date("i");
    }
    // The new better way takes priority.
    if (property_exists($step, 'completionexpected_datetime')) {
        if ($step->completionexpected_datetime) {
            $step->completionexpectedday = date("d", $step->completionexpected_datetime);
            $step->completionexpectedmonth = date("m", $step->completionexpected_datetime);
            $step->completionexpectedyear = date("Y", $step->completionexpected_datetime);
            $step->completionexpectedhour = date("H", $step->completionexpected_datetime);
            $step->completionexpectedminute = date("i", $step->completionexpected_datetime);
        }
    }

    $step->days = [];
    for ($i = 1; $i <= 31; $i++) {
        $step->days[] = ['val' => sprintf("%02d", $i),
                         'txt' => sprintf("%02d", $i),
                         'sel' => ($step->completionexpectedday == sprintf("%02d", $i))];
    }
    $step->months = [];
    $step->months[] = ['val' => '01', 'txt' => 'January', 'sel' => ($step->completionexpectedmonth == '01')];
    $step->months[] = ['val' => '02', 'txt' => 'February', 'sel' => ($step->completionexpectedmonth == '02')];
    $step->months[] = ['val' => '03', 'txt' => 'March', 'sel' => ($step->completionexpectedmonth == '03')];
    $step->months[] = ['val' => '04', 'txt' => 'April', 'sel' => ($step->completionexpectedmonth == '04')];
    $step->months[] = ['val' => '05', 'txt' => 'May', 'sel' => ($step->completionexpectedmonth == '05')];
    $step->months[] = ['val' => '06', 'txt' => 'June', 'sel' => ($step->completionexpectedmonth == '06')];
    $step->months[] = ['val' => '07', 'txt' => 'July', 'sel' => ($step->completionexpectedmonth == '07')];
    $step->months[] = ['val' => '08', 'txt' => 'August', 'sel' => ($step->completionexpectedmonth == '08')];
    $step->months[] = ['val' => '09', 'txt' => 'September', 'sel' => ($step->completionexpectedmonth == '09')];
    $step->months[] = ['val' => '10', 'txt' => 'October', 'sel' => ($step->completionexpectedmonth == '10')];
    $step->months[] = ['val' => '11', 'txt' => 'November', 'sel' => ($step->completionexpectedmonth == '11')];
    $step->months[] = ['val' => '12', 'txt' => 'December', 'sel' => ($step->completionexpectedmonth == '12')];

    $currentyear = date("Y");
    $step->years = [];
    for ($i = $currentyear; $i <= $currentyear + 6; $i++) {
        $step->years[] = ['val' => $i, 'txt' => $i, 'sel' => ($step->completionexpectedyear == $i)];
    }
    $step->hours = [];
    for ($i = 0; $i <= 23; $i++) {
        $step->hours[] = ['val' => sprintf("%02d", $i),
            'txt' => sprintf("%02d", $i),
            'sel' => ($step->completionexpectedhour == sprintf("%02d", $i))];
    }
    $step->minutes = [];
    for ($i = 0; $i <= 59; $i++) {
        $step->minutes[] = ['val' => sprintf("%02d", $i),
                            'txt' => sprintf("%02d", $i),
                            'sel' => ($step->completionexpectedminute == sprintf("%02d", $i))];
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
        // Default color scheme.
        0 => ['#4156A1', '#427E93', '#008473', '#6F7D1C', '#D14905'],
    ];
    if (isset($colors[$id])) {
        return $colors[$id];
    }
    // Return default color scheme.
    return $colors[0];
}

/**
 */
function roadmap_list_icons() {
    global $CFG;
    $iconsfolder = $CFG->dirroot . '/mod/roadmap/pix/icons/';

    $selectedicon = new \stdClass();
    $selectedicon->file = 'icon-59';
    $selectedicon->name = 'Test';

    $result = [];
    $result['categories'] = [];

    $currentlyused = new \stdClass();
    $currentlyused->id = -1;
    $currentlyused->name = 'Currently Used';
    $currentlyused->icons = [];  // TODO: Load with currently used, when called dynamically.
    $result['categories'][] = $currentlyused;

    // TODO: Load this into cache or memory.
    $jsonmanifest = file_get_contents($iconsfolder . 'manifest.json');
    $iconmanifest = json_decode($jsonmanifest, false);

    foreach ($iconmanifest as $iconcategory) {
        foreach ($iconcategory->icons as $icon) {
            $icon->iconurl = $CFG->wwwroot . '/mod/roadmap/icon.php?name=' . $icon->file . '&percent=100&flags=n';
        }
        $result['categories'][] = $iconcategory;
    }

    $result['selectedicon'] = $selectedicon;

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
