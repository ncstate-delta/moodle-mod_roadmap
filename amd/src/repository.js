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
 * Course Roadmap configuration data fetching.
 *
 * @module     mod_roadmap/configurationdata
 * @copyright  2024 Steve Bader <smbader@ncsu.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import {call as fetchMany} from 'core/ajax';
import moodleConfig from 'core/config';

/**
 * Fetch course modules with activity completion and their associated
 * expected completion dates.
 *
 * @return {Promise}
 */
export const fetchCourseModules = () => fetchMany([{
    methodname: 'mod_roadmap_fetch_course_modules_for_steps',
    args: {
        context: moodleConfig.contextid,
        course: moodleConfig.courseId,
        pageurl: window.location.href,
    }
}])[0];

/**
 * Fetch roadmap color patterns by color set id.
 *
 * @param {Integer} colorid The color set id.
 * @return {Promise}
 */
export const fetchColorPattern = (colorid) => fetchMany([{
    methodname: 'mod_roadmap_fetch_color_pattern',
    args: {
        colorid: colorid,
        courseid: moodleConfig.courseId,
    }
}])[0];