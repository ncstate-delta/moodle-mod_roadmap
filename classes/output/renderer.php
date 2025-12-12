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
 * Renderer class for mod_roadmap
 *
 * @package   mod_roadmap
 * @copyright 2020 NC State DELTA {@link http://delta.ncsu.edu}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_roadmap\output;

use plugin_renderer_base;
use renderable;

/**
 * Renderer class for roadmap configuration form
 *
 * @package   mod_roadmap
 * @copyright 2020 NC State DELTA {@link http://delta.ncsu.edu}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {
    /**
     * Defer to template.
     *
     * @param configuration $page
     * @return string html for the page
     */
    public function render_configuration(configuration $page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('mod_roadmap/configuration', $data);
    }
}
