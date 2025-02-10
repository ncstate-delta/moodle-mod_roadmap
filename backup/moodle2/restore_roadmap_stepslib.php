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
 * This file is used for data restoration during an activity restore.
 *
 * @package   mod_roadmap
 * @copyright 2020 NC State DELTA {@link http://delta.ncsu.edu}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that will be used by the restore_roadmap_activity_task
 */

/**
 * Structure step to restore one course roadmap activity
 */
class restore_roadmap_activity_structure_step extends restore_activity_structure_step {

    /**
     * Define the structure for the course roadmap activity
     * @return array paths of activity structure.
     */
    protected function define_structure() {

        $paths = [];
        $paths[] = new restore_path_element('roadmap', '/activity/roadmap');
        $paths[] = new restore_path_element('roadmap_phase', '/activity/roadmap/phases/phase');
        $paths[] = new restore_path_element('roadmap_cycle', '/activity/roadmap/phases/phase/cycles/cycle');
        $paths[] = new restore_path_element('roadmap_step', '/activity/roadmap/phases/phase/cycles/cycle/steps/step');

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process roadmap table data from restore file.
     *
     * @param object $data The data in object form
     * @return void
     */
    protected function process_roadmap($data) {
        global $DB;

        $data = (object)$data;
        $data->course = $this->get_courseid();

        $newitemid = $DB->insert_record('roadmap', $data);
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Process roadmap phase table data from restore file.
     *
     * @param object $data The data in object form
     * @return void
     */
    protected function process_roadmap_phase($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->roadmapid = $this->get_new_parentid('roadmap');

        $newitemid = $DB->insert_record('roadmap_phase', $data);

        $this->set_mapping('roadmap_phase', $oldid, $newitemid, false);
    }

    /**
     * Process roadmap cycle table data from restore file.
     *
     * @param object $data The data in object form
     * @return void
     */
    protected function process_roadmap_cycle($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->phaseid = $this->get_new_parentid('roadmap_phase');

        $newitemid = $DB->insert_record('roadmap_cycle', $data);
        $this->set_mapping('roadmap_cycle', $oldid, $newitemid, false);
    }

    /**
     * Process roadmap step table data from restore file.
     *
     * @param object $data The data in object form
     * @return void
     */
    protected function process_roadmap_step($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->cycleid = $this->get_new_parentid('roadmap_cycle');

        if (property_exists($data, 'expectedcomplete')) {
            if ($data->expectedcomplete == 1) {
                $data->completionexpectedcmid = -1;
            } else {
                $data->completionexpectedcmid = $data->expectedcomplete;
            }
        }
        if (property_exists($data, 'completionexpected_datetime')) {
            $data->completionexpecteddatetime = $data->completionexpected_datetime;
        }

        $newitemid = $DB->insert_record('roadmap_step', $data);
        $this->set_mapping('roadmap_step', $oldid, $newitemid, false);
    }

    /**
     * Map course module ids to new values in the restored course.
     * @return void
     */
    protected function after_restore() {
        global $DB;

        // Add roadmap related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_roadmap', 'intro', null);

        $activityid = $this->task->get_activityid();
        $roadmap = $DB->get_record('roadmap', ['id' => $activityid]);

        // Convert roadmap configuration if exists.
        if (!empty($roadmap->configuration)) {
            $data = json_decode($roadmap->configuration);

            foreach ($data->phases as $phase) {

                if (isset($phase->cycles)) {
                    foreach ($phase->cycles as $cycle) {

                        if (isset($cycle->steps)) {
                            foreach ($cycle->steps as $step) {

                                $newcmids = [];
                                if (!isset($step->completionmodules)) {
                                    $step->completionmodules = '';
                                }

                                $cmids = explode(',', $step->completionmodules);
                                foreach ($cmids as $cmid) {

                                    $mapping = $this->get_mappingid('course_module', (int)$cmid);
                                    if ($mapping) {
                                        $newcmids[] = $mapping;
                                    }
                                }

                                $step->completionmodules = implode(',', $newcmids);
                            }
                        }
                    }
                }
            }
            $roadmap->configuration = json_encode($data);
            $DB->update_record('roadmap', $roadmap);

        } else {

            // Restore from 4.0 and later.
            $phases = $DB->get_records('roadmap_phase', ['roadmapid' => $roadmap->id]);

            foreach ($phases as $phase) {
                $cycles = $DB->get_records('roadmap_cycle', ['phaseid' => $phase->id]);

                foreach ($cycles as $cycle) {
                    $steps = $DB->get_records('roadmap_step', ['cycleid' => $cycle->id]);

                    foreach ($steps as $step) {

                        $newcmids = [];
                        if (!isset($step->completionmodules)) {
                            $step->completionmodules = '';
                        }

                        $cmids = explode(',', $step->completionmodules);
                        foreach ($cmids as $cmid) {

                            if (!$mapping = $this->get_mappingid('course_module', (int)$cmid)) {
                                continue;
                            }

                            if ($mapping) {
                                $newcmids[] = $mapping;
                            }
                        }
                        $step->completionmodules = implode(',', $newcmids);

                        if ((int)$step->completionexpectedcmid > 0) {
                            $step->completionexpectedcmid = $this->get_mappingid(
                                'course_module',
                                (int)$step->completionexpectedcmid
                            );
                        }

                        $DB->update_record('roadmap_step', $step);
                    }
                }
            }

        }
    }

}
