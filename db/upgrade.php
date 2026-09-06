<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Upgrade code for mod_dialoguebuilder.
 *
 * @package    mod_dialoguebuilder
 * @copyright  2026 Matheus Mathias
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade the dialoguebuilder module.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_dialoguebuilder_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026090500) {

        // Define index course (not unique) to be added to dialoguebuilder.
        $table = new xmldb_table('dialoguebuilder');
        $index = new xmldb_index('course', XMLDB_INDEX_NOTUNIQUE, ['course']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index dialoguebuilderid (not unique) to be added to dialoguebuilder_subs.
        $table = new xmldb_table('dialoguebuilder_subs');
        $index = new xmldb_index('dialoguebuilderid', XMLDB_INDEX_NOTUNIQUE, ['dialoguebuilderid']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index userid (not unique) to be added to dialoguebuilder_subs.
        $index = new xmldb_index('userid', XMLDB_INDEX_NOTUNIQUE, ['userid']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index submissionid (not unique) to be added to dialoguebuilder_chars.
        $table = new xmldb_table('dialoguebuilder_chars');
        $index = new xmldb_index('submissionid', XMLDB_INDEX_NOTUNIQUE, ['submissionid']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index submissionid (not unique) to be added to dialoguebuilder_lines.
        $table = new xmldb_table('dialoguebuilder_lines');
        $index = new xmldb_index('submissionid', XMLDB_INDEX_NOTUNIQUE, ['submissionid']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index characterid (not unique) to be added to dialoguebuilder_lines.
        $index = new xmldb_index('characterid', XMLDB_INDEX_NOTUNIQUE, ['characterid']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Dialoguebuilder savepoint reached.
        upgrade_mod_savepoint(true, 2026090500, 'dialoguebuilder');
    }

    return true;
}
