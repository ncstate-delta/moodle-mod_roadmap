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

        $colorset = roadmap_color_sets($roadmap->colors);
        $colorcount = count($colorset);

        $data = json_decode($roadmap->configuration);
        $completion = new completion_info($COURSE);

        $colorindex = 0;
        foreach ($data->phases as $phase) {
            $phase->color = $colorset[$colorindex];

            if ($colorindex == $colorcount - 1) {
                $colorindex = 0;
            } else {
                $colorindex += 1;
            }

            if (isset($phase->cycles)) {
                foreach ($phase->cycles as $cycle) {

                    if (isset($cycle->learningobjectives) && $cycle->learningobjectives !== '') {
                        $learningobjectivenumbers = [];
                        foreach (explode(",", $cycle->learningobjectives) as $loids) {
                            $learningobjectivenumbers[] = $loids + 1;
                        }
                        $cycle->learningobjectives = implode(", ", $learningobjectivenumbers);
                    }

                    if (isset($cycle->steps)) {
                        foreach ($cycle->steps as $step) {
                            if (!isset($step->completionmodules)) {
                                $step->completionmodules = '';
                            }
                            $cmids = explode(',', $step->completionmodules);

                            $step->completedontime = false;
                            $step->incomplete = false;

                            if (!empty($step->completionmodules)) {
                                $expectedcompletetime = strtotime($step->completionexpected_month . '/' .
                                    $step->completionexpected_day . '/' . $step->completionexpected_year . ' ' .
                                    $step->completionexpected_hour . ':' . $step->completionexpected_minute);

                                foreach ($cmids as $cmid) {
                                    $cminspect = new stdClass();
                                    $cminspect->id = (int)$cmid;
                                    $completiondata = $completion->get_data($cminspect);

                                    if ($completiondata->completionstate == COMPLETION_INCOMPLETE ||
                                        $completiondata->completionstate == COMPLETION_COMPLETE_FAIL
                                    ) {
                                        $step->incomplete = true;
                                    }
                                }
                                $step->completedontime = ($step->expectedcomplete == 1 &&
                                                          !$step->incomplete &&
                                                          $completiondata->timemodified < $expectedcompletetime);

                                // Step-link Logic.
                                if ($step->linksingleactivity == 1 && count($cmids) == 1) {
                                    // Check for linksingleactivity and create link.
                                    $step->stepurl = get_activity_url((int)$cmids[0], $COURSE->id);
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
                                    $iconfilecontents = file_get_contents($iconfilename);
                                    $step->stepiconsvg = '<span class="step-icon-' . $phase->id . '">' .
                                        $iconfilecontents .
                                        '</span>';
                                }
                            }
                        }
                    }
                }
            }
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

        if ($roadmap->clodisplayposition == 0) {
            $content .= $clocontent;
        }
        $content .= $OUTPUT->render_from_template('mod_roadmap/view_phases', $data);
        if ($roadmap->clodisplayposition == 1) {
            $content .= $clocontent;
        }
    }

    // Show configuration link if editing is on.
    if (has_capability('mod/roadmap:configure', $context)) {
        $content .= '<div>' .
            '<a class="btn btn-primary" href="' . $CFG->wwwroot . '/mod/roadmap/configuration.php?id=' . $cm->id . '">' .
            'Configure Roadmap</a></div>';
    }

    $cm->set_content($content);

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