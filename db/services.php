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
 * Web service declarations.
 *
 * @package   mod_roadmap
 * @copyright 2024 Steve Bader <smbader@ncsu.edu>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'mod_roadmap_fetch_course_modules_for_steps' => [
        'classname' => 'mod_roadmap\external',
        'methodname' => 'fetch_course_modules_for_steps',
        'classpath' => '',
        'description' => 'Return course modules within a course that has activity completion configured.',
        'type' => 'read',
        'ajax' => true,
    ],
    'mod_roadmap_fetch_color_pattern' => [
        'classname' => 'mod_roadmap\external',
        'methodname' => 'fetch_color_pattern',
        'classpath' => '',
        'description' => 'Return color pattern for roadmap.',
        'type' => 'read',
        'ajax' => true,
    ],
];
