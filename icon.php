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
 * This file generates icon files used in the roadmap dynamically.
 *
 * @package   mod_roadmap
 * @copyright 2024 NC State DELTA {@link http://delta.ncsu.edu}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
global $CFG;

require_login();

$name = required_param('name', PARAM_TEXT);
$percent = required_param('percent', PARAM_FLOAT);
$color = optional_param('color', '#666', PARAM_TEXT);
$flags = optional_param('flags', '', PARAM_ALPHA);

/*
 * Flags:
 *   a or A  = Alert
 *   s or S  = Star
 *   n or N  = No Progress Bar
 */

// Based on parameters passed in, generate the correct icon.

$iconfilename = $CFG->dirroot . '/mod/roadmap/pix/icons/' . $name . '.svg';
if (file_exists($iconfilename)) {
    $color = verify_hex($color);

    if ($percent < 100) {
        $bgcolor = '#aaa';
    } else {
        $bgcolor = $color;
    }

    $iconfilecontents = file_get_contents($iconfilename);

    $output = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40">
    <circle cx="20" cy="20" r="15" fill="' . $bgcolor . '"/>
    <g transform="translate(5, 5)" fill="#fff">
        ' . trim($iconfilecontents) . '
    </g>
    <g class="icon-extras">
        <g fill="none" transform="rotate(-90 20 20)">';

    // Show the progress bar.
    if (stripos($flags, 'n') === false) {
        $radius = 17.5;
        // Circumference = 2 * pi * radius.
        $dasharray = round(2 * M_PI * $radius, 2);
        $dashoffset = $dasharray * (100 - $percent) / 100;

        $output .= '
            <circle class="circle-bg" stroke="#ccc" stroke-width="3" cx="20" cy="20" r="' . $radius . '"/>
            <circle class="roadmap-circle-progress"
                stroke="' . $color . '" stroke-width="3"
                stroke-dasharray="' . $dasharray . '" stroke-dashoffset="' . $dashoffset . '"
                cx="20" cy="20" r="' . $radius . '"/>';
    }

    $output .= '
            <circle class="circle-bg" stroke="#fff" cx="20" cy="20" r="15.5"/>
        </g>';

    if (stripos($flags, 'a') !== false) {
        // Show an alert.
        $output .= '
        <g transform="translate(28.5, 0) scale(.8 .8)">
            <circle fill="#c00" cx="7" cy="7" r="7"/>
            <circle fill="#fff" cx="7" cy="10.99" r="1.12"/>
            <path fill="#fff" d="M5.88 3.38c0-.82.28-1.49 1.12-1.49s1.12.67 1.12
                1.49c0 1.69-.5 4.88-1.12 4.88S5.88 5.07 5.88 3.38Z"/>
        </g>';
    } else if (stripos($flags, 's') !== false) {
        // Show a star.
        $output .= '
        <polygon points="4.29 9.3 1.07 6.17 5.51 5.52 7.5 1.5 9.48 5.52 13.93
            6.17 10.71 9.3 11.47 13.72 7.5 11.63 3.53 13.72 4.29 9.3"
            fill="#ff9000"
            stroke="#fff"
            stroke-width=".5"
            transform="translate(24, -2) scale(1.2 1.2)"/>';
    }

    $output .= '
    </g>
</svg>';

    // Add the proper header.
    header('Content-Type: image/svg+xml');

    $secondstocache = 3600;
    $ts = gmdate("D, d M Y H:i:s", time() + $secondstocache) . " GMT";

    header("Expires: $ts");
    header("Pragma: cache");
    header("Cache-Control: max-age=$secondstocache");

    echo $output;
}

/**
 * Verify string is a valid hex color using preg_match
 *
 * @param string $color The expected hex color.
 * @return string a cleaned up ready-to-use hex color string.
 */
function verify_hex($color) {
    if (substr($color, 0, 1) !== '#') {
        $color = '#' . $color;
    }

    if (preg_match('/^#([A-Fa-f0-9]{3}){1,2}$/', $color)) {
        return $color;
    }

    return '#c00';
}
