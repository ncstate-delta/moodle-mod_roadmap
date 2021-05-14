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
 * @package   mod_roadmap
 * @copyright 2020 NC State DELTA {@link http://delta.ncsu.edu}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that will be used by the restore_roadmap_activity_task
 */

/**
 * Structure step to restore one roadmap activity
 */
class restore_roadmap_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $paths[] = new restore_path_element('roadmap', '/activity/roadmap');

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    protected function process_roadmap($data) {
        global $DB;

        $data = (object)$data;
        $data->course = $this->get_courseid();

        $newitemid = $DB->insert_record('roadmap', $data);
        $this->apply_activity_instance($newitemid);
    }

    protected function after_restore() {
        global $DB;

        // Add roadmap related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_roadmap', 'intro', null);

        $activityid = $this->task->get_activityid();
        $roadmap = $DB->get_record('roadmap', array('id' => $activityid));

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
        }
    }

}
