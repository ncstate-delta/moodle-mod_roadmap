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
 * Define all the backup steps that will be used by the backup_roadmap_activity_task
 */
class backup_roadmap_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // Define each element separated.
        $roadmap = new backup_nested_element('roadmap', array('id'), array(
            'name', 'intro', 'introformat', 'timemodified', 'configuration', 'learningobjectives', 'colors',
            'clodisplayposition', 'cloalignment', 'clodecoration', 'cloprefix'));

        // Define sources.
        $roadmap->set_source_table('roadmap', array('id' => backup::VAR_ACTIVITYID));

        // Define file annotations.
        $roadmap->annotate_files('mod_roadmap', 'intro', null);

        // Return the root element (roadmap), wrapped into standard activity structure.
        return $this->prepare_activity_structure($roadmap);
    }
}
