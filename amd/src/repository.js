/**
 * Course Roadmap configuration data fetching.
 *
 * @module     mod_roadmap/configurationdata
 * @copyright  2024 Steve Bader <smbader@ncsu.edu>
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
    }
}])[0];