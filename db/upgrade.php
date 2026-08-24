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

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade the dialoguebuilder module.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_dialoguebuilder_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // Upgrade steps will go here in future versions.
    // Example:
    // if ($oldversion < 2026082000) {
    //     // Upgrade code here.
    //     upgrade_mod_savepoint(true, 2026082000, 'dialoguebuilder');
    // }

    return true;
}
