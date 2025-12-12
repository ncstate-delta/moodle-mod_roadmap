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
 * Defines backup_roadmap_activity_task class
 *
 * @package   mod_roadmap
 * @copyright 2020 NC State DELTA {@link http://delta.ncsu.edu}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/roadmap/backup/moodle2/backup_roadmap_stepslib.php');

/**
 * Provides the steps to perform one complete backup of the Label instance
 */
class backup_roadmap_activity_task extends backup_activity_task {
    /**
     * No specific settings for this activity
     */
    protected function define_my_settings() {
    }

    /**
     * Defines a backup step to store the instance data in the label.xml file
     */
    protected function define_my_steps() {
        $this->add_step(new backup_roadmap_activity_structure_step('roadmap_structure', 'roadmap.xml'));
    }

    /**
     * No content encoding needed for this activity
     *
     * @param string $content some HTML text that eventually contains URLs to the activity instance scripts
     * @return string the same content with no changes
     */
    public static function encode_content_links($content) {
        return $content;
    }
}
