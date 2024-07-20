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
 * @copyright 2020 NC State DELTA {@link http://delta.ncsu.edu}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->dirroot/mod/roadmap/lib.php");

/**
 * Prepare phase, cycle, and step data for mustache templates.
 *
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
                $step->completionexpectedcmid = $step->completionexpectedcmid;
                $step->completionexpecteddatetime = $step->completionexpecteddatetime;
                if ($step->completionexpectedcmid == 0) {
                    $step->completionexpectedreadable = 'No expected completion date set.';
                } else if ($step->completionexpectedcmid > 0) {
                    $step->completionexpectedreadable = ($step->completionexpecteddatetime == 0 ? 0 : userdate(
                        $step->completionexpecteddatetime,
                        get_string('strftimedatetimeshortaccurate', 'core_langconfig')
                    ));
                } else {
                    $step->completionexpectedreadable = ($step->completionexpecteddatetime == 0 ? 0 : userdate(
                        $step->completionexpecteddatetime,
                        get_string('strftimedatetimeshortaccurate', 'core_langconfig')
                    ));
                }

                $step->linksingleactivity = (bool)$step->linksingleactivity;
                $step->iconurl = $CFG->wwwroot . '/mod/roadmap/icon.php?name=' . $step->stepicon . '&percent=100&flags=n';
                $step->sort = (int)$step->sort;

                $cycle->steps[] = $step;
            }
            $phase->cycles[] = $cycle;
        }
        $data->phases[] = $phase;
    }

    return json_encode($data);
}

/**
 * Delete a phase and the tree of elements that belong to that phase.
 *
 * @param integer $phaseid
 * @return void
 */
function roadmap_delete_phase($phaseid) {
    global $DB;

    // Delete cycles and cascade delete steps.
    $cycles = $DB->get_records('roadmap_cycle', ['phaseid' => $phaseid]);
    foreach ($cycles as $cycle) {
        roadmap_delete_cycle($cycle->id);
    }

    $DB->delete_records('roadmap_phase', ['id' => $phaseid]);
}

/**
 * Delete a cycle and the tree of elements that belong to that phase.
 *
 * @param integer $cycleid
 * @return void
 */
function roadmap_delete_cycle($cycleid) {
    global $DB;

    // Delete all child steps of the cycle.
    $DB->delete_records('roadmap_step', ['cycleid' => $cycleid]);
    // Delete the cycle itself.
    $DB->delete_records('roadmap_cycle', ['id' => $cycleid]);
}

/**
 * Save the configuration passed in from the form.
 *
 * @param string $configjson json data structure of all roadmap data
 * @param integer $roadmapid the roadmap to save the data to
 * @param boolean $conversion convert from old format of roadmap
 * @return boolean result of save.
 */
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
            'title' => (empty($phase->title) ? 'Phase ' . ($phasesort + 1) : $phase->title),
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
                'title' => (empty($cycle->title) ? 'Cycle ' . ($cyclesort + 1) : $cycle->title),
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

                // Save the phase specific data.
                $stepdata = [
                    'rollovertext' => (is_null($step->rollovertext) ? '' : $step->rollovertext),
                    'stepicon' => $step->stepicon,
                    'completionmodules' => $step->completionmodules,
                    'linksingleactivity' => $step->linksingleactivity,
                    'pagelink' => $step->pagelink,
                    'completionexpectedcmid' => $step->completionexpectedcmid,
                    'completionexpecteddatetime' => $step->completionexpecteddatetime,
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


/**
 * Retrieve color set for the roadmap
 *
 * @param integer $id optional
 * @return array of colors in colorset
 */
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
 * Load roadmap icons used for step icon selection.
 *
 * @return array of icons
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
 * @param stdClass $course - Course object to pull activities from.
 * @param boolean $includesections - Should section information be included in list.
 * @return array $elements - return the elements to be added to the form.
 */
function roadmap_list_activities($course, $includesections = true) {
    $results = [];
    $index = 0;

    $completion = new completion_info($course);

    if ($completion->is_enabled()) {

        $modinfo = get_fast_modinfo($course);
        $cms = $modinfo->get_cms();

        if ($includesections) {
            foreach ($modinfo->get_sections() as $sectionnum => $section) {
                $sectioninfo = [];
                $sectioninfo['name'] = get_section_name($course, $sectionnum);
                $sectioninfo['id'] = $sectionnum;

                $coursemodules = [];
                foreach ($section as $cmid) {
                    $coursemodule = [];
                    $cm = $cms[$cmid];
                    // Add each course-module if it:
                    // (a) has completion turned on.
                    // (b) is not the same as current course-module.
                    if ($cm->completion && $cm->modname != 'roadmap' && $cm->deletioninprogress == 0) {
                        $coursemodule = [
                            'id' => $cm->id,
                            'name' => $cm->name,
                            'completionexpecteddatetime' => $cm->completionexpected,
                            'completionexpectedreadable' => $cm->completionexpected == 0 ? 0 :
                                userdate($cm->completionexpected, get_string('strftimedatetimeshortaccurate', 'core_langconfig')),
                        ];
                        $index += 1;
                        $coursemodules[] = $coursemodule;
                    }
                }
                asort($coursemodules);
                $sectioninfo['coursemodules'] = $coursemodules;
                $results[] = $sectioninfo;
            }
        } else {
            foreach ($modinfo->cms as $id => $cm) {
                if ($cm->completion && $cm->modname != 'roadmap') {
                    $coursemodule = [
                        'id' => $cm->id,
                        'name' => $cm->name,
                        'completionexpecteddatetime' => $cm->completionexpected,
                        'completionexpectedreadable' => $cm->completionexpected == 0 ? 0 :
                            userdate($cm->completionexpected, get_string('strftimedatetimeshortaccurate', 'core_langconfig')),
                    ];
                    $index += 1;
                    $results[] = $coursemodule;
                }
            }
        }
        return $results;
    }

}
