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

    /**
     * Define the structure for the roadmap activity
     * @return void
     */
    protected function define_structure() {

        // Define each element separated.
        $roadmap = new backup_nested_element('roadmap', array('id'), array(
            'name', 'intro', 'introformat', 'timemodified', 'configuration', 'learningobjectives', 'colors',
            'clodisplayposition', 'cloalignment', 'clodecoration', 'cloprefix'));

        $phases = new backup_nested_element('phases');

        $phase = new backup_nested_element('phase', array('id'), array(
           'title', 'sort', 'roadmapid'
        ));

        $cycles = new backup_nested_element('cycles');

        $cycle = new backup_nested_element('cycle', array('id'), array(
            'title', 'subtitle', 'pagelink', 'learningobjectives', 'sort', 'phaseid'
        ));

        $steps = new backup_nested_element('steps');

        $step = new backup_nested_element('step', array('id'), array(
            'rollovertext', 'stepicon', 'completionmodules', 'linksingleactivity', 'pagelink', 'expectedcomplete',
            'completionexpected_datetime', 'sort', 'cycleid'
        ));

        $roadmap->add_child($phases);
        $phases->add_child($phase);
        $phase->add_child($cycles);
        $cycles->add_child($cycle);
        $cycle->add_child($steps);
        $steps->add_child($step);

        // Define sources.
        $roadmap->set_source_table('roadmap', array('id' => backup::VAR_ACTIVITYID));
        $phase->set_source_table('roadmap_phase', array('roadmapid' => backup::VAR_PARENTID));
        $cycle->set_source_table('roadmap_cycle', array('phaseid' => backup::VAR_PARENTID));
        $step->set_source_table('roadmap_step', array('stepid' => backup::VAR_PARENTID));

        // Define file annotations.
        $roadmap->annotate_files('mod_roadmap', 'intro', null);

        // Return the root element (roadmap), wrapped into standard activity structure.
        return $this->prepare_activity_structure($roadmap);
    }
}
