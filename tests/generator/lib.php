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
 * Course Roadmap module data generator class
 *
 * @package    mod_roadmap
 * @category   test
 * @copyright  2025 Steve Bader
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_roadmap_generator extends testing_module_generator {
    /**
     * Create a new instance of the roadmap module.
     *
     * @param array|null $record Data for the new instance.
     * @param array|null $options Additional options for instance creation.
     * @return stdClass The created instance record.
     * @throws coding_exception If the user lacks required capabilities or if output is initialized during module creation.
     */
    public function create_instance($record = null, ?array $options = null) {
        $record = (object)(array)$record;

        $defaultsettings = [
            'name' => 'Test Course Roadmap',
            'introformat' => 1,
            'timemodified' => time(),
            'clolearningobjectives' => '{"learningobjectives":[]}',
            'clodisplayposition' => 0,
            'cloalignment' => 0,
            'clodecoration' => 0,
            'cloprefix' => 'CLO',
        ];

        foreach ($defaultsettings as $name => $value) {
            if (!isset($record->{$name})) {
                $record->{$name} = $value;
            }
        }

        return parent::create_instance($record, $options);
    }
}
