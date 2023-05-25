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
 * List of features supported in Roadmap module
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function roadmap_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_NO_VIEW_LINK:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_INTERFACE;
        default:
            return false;
    }
}

/**
 * Add roadmap instance.
 * @param object $data
 * @param object $mform
 * @return int new folder instance id
 */
function roadmap_add_instance($data, $mform) {
    global $DB;

    $data->timemodified = time();
    $data->cloprefix = 'CLO';

    $data->id = $DB->insert_record('roadmap', $data);

    return $data->id;
}


/**
 * Update roadmap instance.
 * @param object $data
 * @param object $mform
 * @return bool true
 */
function roadmap_update_instance($data, $mform) {
    global $DB;

    $data->timemodified = time();
    $data->id = $data->instance;

    $DB->update_record('roadmap', $data);

    return true;
}

/**
 * Delete roadmap instance.
 * @param int $id
 * @return bool true
 */
function roadmap_delete_instance($id) {
    global $DB;

    if (!$roadmap = $DB->get_record('roadmap', array('id' => $id))) {
        return false;
    }

    $DB->delete_records('roadmap', array('id' => $roadmap->id));

    return true;
}

/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 * See get_array_of_activities() in course/lib.php
 *
 * @global object
 * @param object $coursemodule
 * @return cached_cm_info|null
 */

function roadmap_cm_info_view(cm_info $cm) {
    global $DB, $CFG, $OUTPUT, $COURSE;
    require_once($CFG->libdir . '/completionlib.php');
    require_once($CFG->dirroot . '/mod/roadmap/locallib.php');

    if (!$roadmap = $DB->get_record('roadmap', array('id' => $cm->instance))) {
        return null;
    }

    $context = context_module::instance($cm->id);
    $content = '';
    $clocontent = '';

    if (!empty($roadmap->configuration)) {
        if (roadmap_configuration_save($roadmap->configuration, $roadmap->id, true)) {
            // After conversion from json data to database, clear the configuration field.
            $roadmap->configuration = '';
            $DB->update_record('roadmap', $roadmap);
        }
    }

    // Turn off filters
    $availablefilters = filter_get_available_in_context($context);
    foreach ($availablefilters as $filter => $filterinfo) {
        if ($filterinfo->localstate !== TEXTFILTER_OFF) {
            filter_set_local_state($filter, $context->id, TEXTFILTER_OFF);
        }
    }

    $colorset = roadmap_color_sets($roadmap->colors);
    $colorcount = count($colorset);

    $completion = new completion_info($COURSE);

    $colorindex = 0;

    $data = new \stdClass();
    $data->phases = [];
    $phases = $DB->get_records('roadmap_phase', ['roadmapid' => $roadmap->id], 'sort');
    foreach ($phases as $phase) {
        $phase->color = $colorset[$colorindex];

        if ($colorindex == $colorcount - 1) {
            $colorindex = 0;
        } else {
            $colorindex += 1;
        }

        $phase->cycles = [];
        $cycles = $DB->get_records('roadmap_cycle', ['phaseid' => $phase->id], 'sort');
        foreach ($cycles as $cycle) {
            $cycle->indicatorcolor = $phase->color;

            if (isset($cycle->learningobjectives) && $cycle->learningobjectives !== '') {
                $learningobjectivenumbers = [];
                foreach (explode(",", $cycle->learningobjectives) as $loids) {
                    $learningobjectivenumbers[] = $loids + 1;
                }
                $cycle->learningobjectives = implode(", ", $learningobjectivenumbers);
            }

            $cycle->steps = [];
            $steps = $DB->get_records('roadmap_step', ['cycleid' => $cycle->id], 'sort');
            foreach ($steps as $step) {
                $cmid_complete = 0;
                $cmid_total = 0;

                if (!isset($step->completionmodules)) {
                    $step->completionmodules = '';
                }
                $cmids = explode(',', $step->completionmodules);

                $step->completedontime = false;
                $step->incomplete = false;

                if (!empty($step->completionmodules)) {

                    if (property_exists($step, 'completionexpected_datetime')) {
                        $expectedcompletetime = (int)$step->completionexpected_datetime;
                    } else {
                        // Eventually this can be removed.  This is the old bad way.
                        $expectedcompletetime = strtotime($step->completionexpected_month . '/' .
                            $step->completionexpected_day . '/' . $step->completionexpected_year . ' ' .
                            $step->completionexpected_hour . ':' . $step->completionexpected_minute);
                    }

                    foreach ($cmids as $cmid) {
                        if (!$cm_check = $DB->get_record('course_modules', array('id' => $cmid))) {
                            continue;
                        }

                        $cminspect = new stdClass();
                        $cminspect->id = (int)$cmid;
                        $completiondata = $completion->get_data($cminspect);

                        if ($completiondata->completionstate == COMPLETION_INCOMPLETE ||
                            $completiondata->completionstate == COMPLETION_COMPLETE_FAIL
                        ) {
                            $step->incomplete = true;
                        } else {
                            $cmid_complete++;
                        }
                        $cmid_total++;
                    }
                    $step->completedontime = ($step->expectedcomplete == 1 &&
                                              !$step->incomplete &&
                                              $completiondata->timemodified < $expectedcompletetime);

                    $step->lowontime = ($step->expectedcomplete == 1 &&
                        $step->incomplete &&
                        time() + 86400 > $expectedcompletetime &&
                        time() < $expectedcompletetime);

                    if ($step->expectedcomplete == 1) {
                        $staricon = $CFG->wwwroot . '/mod/roadmap/pix/star.svg';
                        $step->rollovertext = (!empty($step->rollovertext) ? $step->rollovertext . PHP_EOL . '<br /> ' : '') .
                            '<img class="rollover-star" src="' . $staricon .  '" /> By: ' .
                            date("n/j/Y h:i A", $expectedcompletetime);
                    }

                    // Step-link Logic.
                    if ($step->linksingleactivity == 1 && count($cmids) == 1) {

                        if ($cm_check = $DB->get_record('course_modules', array('id' => (int)$cmids[0]))) {
                            // Check for linksingleactivity and create link.
                            $step->stepurl = get_activity_url((int)$cmids[0], $COURSE->id);
                        } else {
                            $step->stepurl = false;
                        }

                    } else if ($step->pagelink != '') {
                        // Or use provided link if available.
                        $step->stepurl = $step->pagelink;
                    } else {
                        // Or don't link at all.
                        $step->stepurl = false;
                    }
                } else {
                    $step->incomplete = true;
                }

                if (!empty($step->stepicon)) {
                    // Read icon and grab svg contents.
                    $iconfilename = $CFG->dirroot . '/mod/roadmap/pix/icons/' . $step->stepicon . '.svg';
                    if (file_exists($iconfilename)) {
                        $cmid_percent = 0;
                        if ($cmid_total > 0) {
                            $cmid_percent = ((int)($cmid_complete / $cmid_total * 100))/100;
                        }
                        $iconfilecontents = file_get_contents($iconfilename);
                        $step->stepiconsvg = '<span data-progress="' . $cmid_percent . '" class="bg step-icon-' . $phase->id . '">' .
                            $iconfilecontents .
                            '</span>';
                    }
                }

                if ($step->incomplete) {
                    $cycle->indicatorcolor = '#cccccc';
                }
                $cycle->steps[] = $step;
            }
            $cycle->prefix = $roadmap->cloprefix;
            $phase->cycles[] = $cycle;
        }
        $data->phases[] = $phase;
    }

    if (!empty($roadmap->learningobjectives)) {
        $clodata = json_decode($roadmap->learningobjectives);

        if (isset($clodata->learningobjectives)) {
            $number = 1;
            foreach ($clodata->learningobjectives as $learningobjective) {
                $learningobjective->prefix = $roadmap->cloprefix;
                $learningobjective->number = $number;
                $number += 1;
            }
            $clocontent = $OUTPUT->render_from_template('mod_roadmap/view_learningobjectives', $clodata);
        }
    }

    if ($roadmap->clodisplayposition == 0 && count($clodata->learningobjectives) >0) {
        $content .= $clocontent;
    }

    $content .= $OUTPUT->render_from_template('mod_roadmap/view_phases', $data);
    if ($roadmap->clodisplayposition == 1 && count($clodata->learningobjectives) >0) {
        $content .= $clocontent;
    }

    // Show configuration link if editing is on.
    if (has_capability('mod/roadmap:configure', $context)) {
        $content .= '<div>' .
            '<a class="btn btn-primary" href="' . $CFG->wwwroot . '/mod/roadmap/configuration.php?id=' . $cm->id . '">' .
            'Configure Roadmap</a></div>';
    }

    $cm->set_content($content);


    // Add js.
    global $PAGE;
    $PAGE->requires->js_call_amd('mod_roadmap/roadmap_view', 'init', array());
}

function get_activity_url($cmid, $courseid) {
    $modinfo = get_fast_modinfo($courseid);
    if (!empty($modinfo->cms)) {
        $cm = $modinfo->get_cm($cmid);

        if ($cm->visible and $cm->has_view() and $cm->uservisible) {
            return $cm->url->out(false);
        }
    }
    return '';
}