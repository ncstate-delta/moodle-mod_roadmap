<?php
// This file is part of the Ncsubook group module for Moodle - http://moodle.org/
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
 * Course Roadmap module capability definition
 *
 * @package    mod_roadmap
 * @copyright  2023 Steve Bader
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$addons = [
    "mod_roadmap" => [
        "handlers" => [
            'courseroadmap' => [
                'displaydata' => [
                    'title' => 'pluginname',
                    'icon' => $CFG->wwwroot . '/mod/roadmap/pix/icon.svg',
                    'class' => '',
                ],
                'delegate' => 'CoreCourseModuleDelegate',
                'styles' => [
                    'url' => '/mod/roadmap/mobile/styles.css',
                    'version' => 2.1,
                ],
            ],
        ],
        'lang' => [
            ['pluginname', 'roadmap'],
        ],
    ],
];
