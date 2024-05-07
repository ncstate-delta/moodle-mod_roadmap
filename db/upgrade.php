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
 * Upgrade code for install
 *
 * @package   mod_roadmap
 * @copyright  2023 Steve Bader
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade function used to manage the database through plugin version changes.
 *
 * @param int $oldversion The old version of the assign module
 * @return bool
 */
function xmldb_roadmap_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2022040700) {

        // Adding Phase table to database.
        $table = new xmldb_table('roadmap_phase');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('roadmapid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('title', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('sort', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Adding Cycle table to database.
        $table = new xmldb_table('roadmap_cycle');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('phaseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('title', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('subtitle', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('pagelink', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('learningobjectives', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('sort', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Adding Cycle table to database.
        $table = new xmldb_table('roadmap_step');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('cycleid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('rollovertext', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('stepicon', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('completionmodules', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('linksingleactivity', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('pagelink', XMLDB_TYPE_CHAR, '511', null, null, null, null);
        $table->add_field('expectedcomplete', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('completionexpected_datetime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('sort', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Roadmap savepoint reached.
        upgrade_mod_savepoint(true, 2022040700, 'roadmap');
    }

    if ($oldversion < 2024050600) {

        // Change name of completionexpected field to completionexpectedcmid.
        $table = new xmldb_table('roadmap_step');
        $field = new xmldb_field('expectedcomplete');
        if ($dbman->field_exists($table, $field)) {
            $field->set_attributes(XMLDB_TYPE_INTEGER, 11, null, XMLDB_NOTNULL, null, '0', 'pagelink');
            $dbman->rename_field($table, $field, 'completionexpectedcmid');
        }

        // Change name of completionexpected_datetime field to completionexpecteddatetime.
        $field = new xmldb_field('completionexpected_datetime');
        if ($dbman->field_exists($table, $field)) {
            $field->set_attributes(XMLDB_TYPE_INTEGER, 11, null, XMLDB_NOTNULL, null, '0', 'completionexpectedcmid');
            $dbman->rename_field($table, $field, 'completionexpecteddatetime');
        }

        /*
         * Convert column completionexpectedcmid to new convention.
         * -1: Custom expected completion datetime.
         *  0: No expected completion datetime for this step.
         * >0: Indicates the course module id datetime is associated with.
         */
        $sql = "SELECT s.id, s.completionexpectedcmid FROM {roadmap_step} s";
        $recordset = $DB->get_recordset_sql($sql);
        foreach ($recordset as $record) {
            if ($record->completionexpectedcmid == 1) {
                // If the previous value was 1, expected completion was enabled.
                // This will translate into a custom value.
                $record->completionexpectedcmid = -1;
            }
            // A previous value of 0 will remain 0 in the new convention.
            // Any other values is unexpected and should remain the same.
            $DB->update_record('roadmap_step', $record);
        }
        $recordset->close();

        // Roadmap savepoint reached.
        upgrade_mod_savepoint(true, 2024050600, 'roadmap');
    }

    return true;
}
