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
 * Class containing data for roadmap configuration page
 *
 * @package   mod_roadmap
 * @copyright 2020 NC State DELTA {@link http://delta.ncsu.edu}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_roadmap\output;

use context_module;
use renderable;
use core_user;
use templatable;
use renderer_base;
use moodle_url;
use stdClass;

/**
 * Class containing data for roadmap configuration page
 *
 * @package   mod_roadmap
 * @copyright 2020 NC State DELTA {@link http://delta.ncsu.edu}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class configuration implements renderable, templatable {

    /** @var context $context */
    protected $context;
    /** @var int $courseid */
    protected $courseid;
    /** @var int $moduleid */
    protected $coursemoduleid;

    /**
     * Construct this renderable.
     *
     * @param int $courseid The course id
     * @param int $userid The user id
     * @param int $moduleid The module id
     */
    public function __construct($courseid, $coursemoduleid) {
        $this->courseid = $courseid;
        $this->coursemoduleid = $coursemoduleid;
        $this->context = context_module::instance($coursemoduleid);
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $DB;

        $data = new stdClass();
        $data->courseid = $this->courseid;
        $data->coursemoduleid = $this->coursemoduleid;

        return $data;
    }
}
