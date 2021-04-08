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
 * File containing the form definition for the roadmap configuration.
 *
 * @package   mod_roadmap
 * @copyright 2020 NC State DELTA {@link http://delta.ncsu.edu}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/mod/roadmap/locallib.php');

/**
 * Class to configure a roadmap.
 *
 * @package   mod_roadmap
 * @copyright 2020 NC State DELTA {@link http://delta.ncsu.edu}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_roadmap_configuration_form extends moodleform {


    /**
     * Form definition
     *
     * @return void
     */
    function definition() {
        global $CFG, $OUTPUT, $PAGE, $COURSE;

        $mform =& $this->_form;


        $mform->addElement('header', 'header_appearance', get_string('appearance', 'roadmap'));

        // Phase color pattern drown selector
        $colorpatterns = [
            0 => 'All Secondary Brand Colors (Default)',
            -1 => 'Custom',
        ];
        $mform->addElement('select', 'phasecolorpattern', get_string('phasecolorpattern', 'roadmap'), $colorpatterns);
        //$mform->setDefault('phasecolorpattern', ??);
        $mform->setType('phasecolorpattern', PARAM_INT);

        $mform->addElement('header', 'header_learningobjectives', get_string('courselearningobjectives', 'roadmap'));

        
        $displayposition = [
            0 => 'Above Roadmap (Default)',
            1 => 'Below Roadmap',
            2 => 'Do Not Display CLOs',
        ];
        $mform->addElement('select', 'displayposition', get_string('displayposition', 'roadmap'), $displayposition);
        //$mform->setDefault('displayposition', ??);
        $mform->setType('displayposition', PARAM_INT);

        
        $cyclealignment = [
            0 => 'Center (Default)',
            1 => 'Left',
            2 => 'Right',
        ];
        $mform->addElement('select', 'cyclealignment', get_string('cyclealignment', 'roadmap'), $cyclealignment);
        //$mform->setDefault('cyclealignment', ??);
        $mform->setType('cyclealignment', PARAM_INT);


        $cycledecoration = [
            0 => 'None (Default)',
            1 => 'Line',
            2 => 'Bracket',
        ];
        $mform->addElement('select', 'cycledecoration', get_string('cycledecoration', 'roadmap'), $cycledecoration);
        //$mform->setDefault('cycledecoration', ??);
        $mform->setType('cycledecoration', PARAM_INT);

        
        $mform->addElement('text', 'cloprefix', get_string('cloprefix', 'roadmap'), 'size="48"');
        $mform->setType('cloprefix', PARAM_TEXT);
        $mform->addRule('cloprefix', get_string('maximumchars', '', 10), 'maxlength', 10, 'client');
        
        // Learning Objectives
        $mform->addElement('text', 'learningobjectivesconfiguration', get_string('learningobjectives', 'roadmap'));
        $mform->setType('learningobjectivesconfiguration', PARAM_RAW);
        $mform->setDefault('learningobjectivesconfiguration', '{"learningobjectives":[{"id":0,"number":1,"name":"Steve"},{"id":1,"number":2,"name":"Krista"},{"id":2,"number":3,"name":"Ryan"},{"id":3,"number":4,"name":"Addison"}]}');

        $mform->addElement('header', 'header_editroadmap', get_string('editroadmap', 'roadmap'));

        // Roadmap Configuration
        $mform->addElement('hidden', 'roadmapconfiguration');
        $mform->setType('roadmapconfiguration', PARAM_RAW);
        $mform->setDefault('roadmapconfiguration', '{"phases":[{"id":0,"index":0,"number":1,"title":"Steve","cycles":[{"id":0,"index":0,"number":1,"name":"Ryan"},{"id":1,"index":1,"number":2,"name":"Addison","steps":[{"id":0,"index":0,"number":1,"name":"Ryan"},{"id":1,"index":1,"number":2,"name":"Addison"}]}]},{"id":4,"index":1,"number":2,"title":"Krista","cycles":[{"id":2,"index":0,"number":1,"name":"Steve"},{"id":3,"index":1,"number":2,"name":"Addison"}]},{"id":7,"index":2,"number":3,"title":"Ryan"},{"id":13,"index":3,"number":4,"title":"Addison","cycles":[{"id":4,"index":0,"number":1,"name":"Steve"},{"id":5,"index":1,"number":2,"name":"Ryan"}]}]}');

        $mform->addElement('html', '<div id="roadmapconfiguration"></div>', get_string('phases', 'roadmap'));
        
        // Icon Selection
        $mform->addElement('hidden', 'icon_data');
        $mform->setType('icon_data', PARAM_RAW);
        $mform->setDefault('icon_data', json_encode(array('icons' => roadmap_list_icons())));
        
        $mform->addElement('hidden', 'activity_data');
        $mform->setType('activity_data', PARAM_RAW);
        $mform->setDefault('activity_data', json_encode(array('activities' => roadmap_list_activities($COURSE))));

        // Add js.
        $PAGE->requires->js_call_amd('mod_roadmap/configuration', 'init', array('#id_learningobjectivesconfiguration', 'input[name="learningobjectivesconfiguration"]', '#roadmapconfiguration', 'input[name="roadmapconfiguration"]'));

        $this->add_action_buttons(true, 'Save Configuration');
    }

    /**
     * Form validation
     *
     * @param array $data data from the form.
     * @param array $files files uploaded.
     * @return array of errors.
     */
    function validation($data, $files) {
        $errors = parent::validation($data, $files);


        return $errors;
    }
}