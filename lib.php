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
 * This file contains the moodle hooks for the roadmap module.
 *
 * @package   mod_roadmap
 * @copyright 2020 NC State DELTA {@link http://delta.ncsu.edu}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * List of features supported in Course Roadmap module
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function roadmap_supports($feature) {
    $features = [
        FEATURE_IDNUMBER                => false,
        FEATURE_GROUPS                  => false,
        FEATURE_GROUPINGS               => false,
        FEATURE_MOD_INTRO               => true,
        FEATURE_COMPLETION_TRACKS_VIEWS => false,
        FEATURE_GRADE_HAS_GRADE         => false,
        FEATURE_GRADE_OUTCOMES          => false,
        FEATURE_MOD_ARCHETYPE           => MOD_ARCHETYPE_RESOURCE,
        FEATURE_BACKUP_MOODLE2          => true,
        FEATURE_NO_VIEW_LINK            => true,
        FEATURE_MOD_PURPOSE             => MOD_PURPOSE_CONTENT,
    ];

    return $features[$feature] ?? null;
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

    if (!$roadmap = $DB->get_record('roadmap', ['id' => $id])) {
        return false;
    }

    $DB->delete_records('roadmap', ['id' => $roadmap->id]);

    return true;
}

/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 * See get_array_of_activities() in course/lib.php
 *
 * @param cm_info $cm course module info.
 * @return cached_cm_info|null
 */
function roadmap_cm_info_view(cm_info $cm) {
    global $DB, $CFG, $OUTPUT, $COURSE;
    require_once($CFG->libdir . '/completionlib.php');
    require_once($CFG->dirroot . '/mod/roadmap/locallib.php');

    if (!$roadmap = $DB->get_record('roadmap', ['id' => $cm->instance])) {
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

    // Turn off filters.
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

            $learningobjectivenumbers = [];
            if (isset($cycle->learningobjectives) && $cycle->learningobjectives !== '') {
                if (isset($clodata->learningobjectives)) {
                    foreach ($clodata->learningobjectives as $learningobjective) {
                        if (in_array($learningobjective->id, explode(',', $cycle->learningobjectives))) {
                            $learningobjectivenumbers[] = $learningobjective->index + 1;
                        }
                    }
                }
                $cycle->learningobjectives = implode(", ", $learningobjectivenumbers);
            }

            $cycle->steps = [];
            $steps = $DB->get_records('roadmap_step', ['cycleid' => $cycle->id], 'sort');
            foreach ($steps as $step) {
                $cmidcomplete = 0;
                $cmidtotal = 0;
                $flags = '';

                if (!isset($step->completionmodules)) {
                    $step->completionmodules = '';
                }
                $cmids = explode(',', $step->completionmodules);

                $step->incomplete = false;

                // Step-link Logic.
                if ($step->pagelink != '') {
                    $step->stepurl = $step->pagelink;
                } else {
                    $step->stepurl = false;
                }

                if (!empty($step->completionmodules)) {

                    if (property_exists($step, 'completionexpecteddatetime')) {
                        $expectedcompletetime = (int)$step->completionexpecteddatetime;
                    } else {
                        // Eventually this can be removed.  This is the old bad way.
                        $expectedcompletetime = strtotime($step->completionexpectedmonth . '/' .
                            $step->completionexpectedday . '/' . $step->completionexpectedyear . ' ' .
                            $step->completionexpectedhour . ':' . $step->completionexpectedminute);
                    }

                    foreach ($cmids as $cmid) {
                        if (!$cmcheck = $DB->get_record('course_modules', ['id' => $cmid])) {
                            continue;
                        }

                        $cminspect = new stdClass();
                        $cminspect->id = (int)$cmid;
                        $completiondata = $completion->get_data($cminspect);

                        if ($step->completionexpectedcmid > 0 && $step->completionexpectedcmid == $cmid) {
                            if ($cmcheck->completionexpected != $step->completionexpecteddatetime) {
                                $step->completionexpecteddatetime = $cmcheck->completionexpected;
                                $DB->update_record('roadmap_step',
                                    (object)['id' => $step->id, 'completionexpecteddatetime' => $cmcheck->completionexpected]);
                            }
                        }
                        if ($completiondata->completionstate == COMPLETION_INCOMPLETE ||
                            $completiondata->completionstate == COMPLETION_COMPLETE_FAIL
                        ) {
                            $step->incomplete = true;
                        } else {
                            $cmidcomplete++;
                        }
                        $cmidtotal++;
                    }

                    if ($step->completionexpectedcmid != 0 &&
                        !$step->incomplete &&
                        $completiondata->timemodified < $expectedcompletetime) {

                        $flags .= 's';
                    } else if ($step->completionexpectedcmid != 0 &&
                        $step->incomplete &&
                        time() + 86400 > $expectedcompletetime &&
                        time() < $expectedcompletetime) {

                        $flags .= 'a';
                    }

                    if ($step->completionexpectedcmid != 0) {
                        $staricon = $CFG->wwwroot . '/mod/roadmap/pix/star.svg';
                        $step->rollovertext = (!empty($step->rollovertext) ? $step->rollovertext . PHP_EOL . '<br /> ' : '') .
                            '<img class="rollover-star" src="' . $staricon .  '" /> By: ' .
                            date("n/j/Y h:i A", $expectedcompletetime);
                    }

                    // Check for linksingleactivity and create link.
                    if ($step->linksingleactivity == 1 && count($cmids) == 1) {
                        if ($cmcheck = $DB->get_record('course_modules', ['id' => (int)$cmids[0]])) {
                            $step->stepurl = get_activity_url((int)$cmids[0], $COURSE->id);
                        }
                    }

                } else {
                    $step->incomplete = true;
                }

                if (!empty($step->stepicon)) {

                    $cmidpercent = 0;
                    if ($cmidtotal > 0) {
                        $cmidpercent = (int)($cmidcomplete / $cmidtotal * 100);
                    }
                    $iconcolor = $phase->color;
                    if (substr($iconcolor, 0, 1) == '#') {
                        $iconcolor = substr($iconcolor, 1);
                    }
                    $step->iconurl = $CFG->wwwroot . '/mod/roadmap/icon.php?name=' . $step->stepicon .
                        '&percent=' . (int)$cmidpercent . '&color=' . $iconcolor . '&flags=' . $flags;
                }

                if ($step->incomplete) {
                    $cycle->indicatorcolor = '#cccccc';
                }

                $cycle->steps[] = $step;
            }
            $cycle->prefix = $roadmap->cloprefix;
            $cycle->cloalignment = ($roadmap->cloalignment == 0 ? 'left' :
                ($roadmap->cloalignment == 1 ? 'center' : 'right' ));
            $cycle->clodecoration = ($roadmap->clodecoration == 0 ? 'none' :
                ($roadmap->clodecoration == 1 ? 'line' : 'bracket' ));

            $phase->cycles[] = $cycle;
        }
        $data->phases[] = $phase;
    }

    if (!empty($roadmap->learningobjectives) && $roadmap->clodisplayposition == 0 && count($clodata->learningobjectives) > 0) {
        $content .= $clocontent;
    }

    $content .= $OUTPUT->render_from_template('mod_roadmap/view_phases', $data);
    if (!empty($roadmap->learningobjectives) && $roadmap->clodisplayposition == 1 && count($clodata->learningobjectives) > 0) {
        $content .= $clocontent;
    }

    // Show configuration link if editing is on.
    if (has_capability('mod/roadmap:configure', $context)) {
        $content .= '<div>' .
            '<a class="btn btn-primary" href="' . $CFG->wwwroot .
            '/mod/roadmap/configuration.php?id=' . $cm->id . '">' . 'Configure Roadmap</a></div>';
    }

    // Add js.
    global $PAGE;
    if ($PAGE->devicetypeinuse === 'mobile') {
        $cm->set_content($content);
    } else {
        $cm->set_content($content);
        $PAGE->requires->js_call_amd('mod_roadmap/roadmap_view', 'init', []);
    }
}

/**
 * Return the activity link for given cmid and course. Respect visibility and permissions.
 * @param int $cmid Id of the activity's course module record.
 * @param int $courseid Id of course where activity is located.
 * @return string URL to activity, blank if not allowed.
 */
function get_activity_url($cmid, $courseid) {
    $modinfo = get_fast_modinfo($courseid);
    if (!empty($modinfo->cms)) {
        $cm = $modinfo->get_cm($cmid);

        if ($cm->visible && $cm->has_view() && $cm->uservisible) {
            return $cm->url->out(false);
        }
    }
    return '';
}
