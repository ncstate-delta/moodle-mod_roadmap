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

namespace mod_roadmap\form;

use moodleform;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/roadmap/locallib.php');

/**
 * Class to configure a course roadmap.
 *
 * @package   mod_roadmap
 * @copyright 2020 NC State DELTA {@link http://delta.ncsu.edu}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class configuration extends moodleform {


    /**
     * Form definition
     *
     * @return void
     */
    public function definition() {
        global $CFG, $PAGE, $COURSE;

        $mform =& $this->_form;
        $roadmap = $this->_customdata['roadmap'];
        $colorpatterns = $this->_customdata['colorpatterns'];

        $mform->addElement('header', 'header_learningobjectives', get_string('courselearningobjectives', 'roadmap'));

        $displayposition = [
            0 => 'Above Roadmap (Default)',
            1 => 'Below Roadmap',
            2 => 'Do Not Display CLOs',
        ];
        $mform->addElement('select', 'displayposition', get_string('displayposition', 'roadmap'), $displayposition);
        $mform->setDefault('displayposition', $roadmap->clodisplayposition);
        $mform->setType('displayposition', PARAM_INT);

        $cyclealignment = [
            0 => 'Left (Default)',
            1 => 'Center',
            2 => 'Right',
        ];
        $mform->addElement('select', 'cyclealignment', get_string('cyclealignment', 'roadmap'), $cyclealignment);
        $mform->setDefault('cyclealignment', $roadmap->cloalignment);
        $mform->setType('cyclealignment', PARAM_INT);

        $cycledecoration = [
            0 => 'None (Default)',
            1 => 'Line',
            2 => 'Bracket',
        ];
        $mform->addElement('select', 'cycledecoration', get_string('cycledecoration', 'roadmap'), $cycledecoration);
        $mform->setDefault('cycledecoration', $roadmap->clodecoration);
        $mform->setType('cycledecoration', PARAM_INT);

        $mform->addElement('text', 'cloprefix', get_string('cloprefix', 'roadmap'), 'size="48"');
        $mform->setType('cloprefix', PARAM_TEXT);
        $mform->addRule('cloprefix', get_string('maximumchars', '', 10), 'maxlength', 10, 'client');
        $mform->setDefault('cloprefix', $roadmap->cloprefix);

        // Learning Objectives.
        $mform->addElement('html', '<div id="learningobjectives_panel"></div>', get_string('learningobjectives', 'roadmap'));

        $mform->addElement('hidden', 'learningobjectivesconfiguration');
        $mform->setType('learningobjectivesconfiguration', PARAM_RAW);
        $mform->setDefault('learningobjectivesconfiguration', $roadmap->learningobjectives);

        $mform->addElement('header', 'header_editroadmap', get_string('editroadmap', 'roadmap'));

        // Roadmap Configuration.
        $mform->addElement('hidden', 'roadmapconfiguration');
        $mform->setType('roadmapconfiguration', PARAM_RAW);
        $mform->setDefault('roadmapconfiguration', roadmap_configuration_edit($roadmap->id));

        $mform->addElement('html', '<div id="roadmapconfiguration"></div>', get_string('phases', 'roadmap'));

        // Icon Selection.
        $mform->addElement('hidden', 'icon_data');
        $mform->setType('icon_data', PARAM_RAW);
        $mform->setDefault('icon_data', json_encode(roadmap_list_icons()));

        $mform->addElement('hidden', 'icon_url');
        $mform->setType('icon_url', PARAM_RAW);
        $mform->setDefault('icon_url', $CFG->wwwroot . '/mod/roadmap/icon.php');

        $mform->addElement('hidden', 'activity_data');
        $mform->setType('activity_data', PARAM_RAW);
        $mform->setDefault('activity_data', json_encode(['activities' => roadmap_list_activities($COURSE, false)]));

        $mform->addElement('header', 'header_appearance', get_string('appearance', 'roadmap'));

        // Phase color pattern drown selector.
        $mform->addElement('select', 'phasecolorpattern', get_string('phasecolorpattern', 'roadmap'), $colorpatterns);
        $mform->setDefault('phasecolorpattern', $roadmap->colors);
        $mform->setType('phasecolorpattern', PARAM_INT);

        // Add js.
        $PAGE->requires->js_call_amd('mod_roadmap/configuration', 'init',
            ['#learningobjectives_panel',
                'input[name="learningobjectivesconfiguration"]',
                '#roadmapconfiguration',
                'input[name="roadmapconfiguration"]']
        );

        $this->add_action_buttons(true, 'Save Configuration');
    }

    /**
     * Form validation
     *
     * @param array $data data from the form.
     * @param array $files files uploaded.
     * @return array of errors.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        return $errors;
    }
}
