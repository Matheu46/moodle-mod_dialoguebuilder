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
 * Dialoguebuilder backup stepslib.
 *
 * @package    mod_dialoguebuilder
 * @copyright  2026 Matheus Mathias
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Define the complete dialoguebuilder structure for backup, with file and id annotations.
 */
class backup_dialoguebuilder_activity_structure_step extends backup_activity_structure_step {
    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $dialoguebuilder = new backup_nested_element('dialoguebuilder', ['id'], [
            'name', 'intro', 'introformat', 'grade',
            'timeopen', 'timeclose', 'timecreated', 'timemodified', 'gallerymode',
        ]);

        $submissions = new backup_nested_element('submissions');
        $submission = new backup_nested_element('submission', ['id'], [
            'userid', 'status', 'timecreated', 'grade', 'feedback', 'timemodified',
        ]);

        $characters = new backup_nested_element('characters');
        $character = new backup_nested_element('character', ['id'], [
            'name', 'avatar_itemid',
        ]);

        $lines = new backup_nested_element('lines');
        $line = new backup_nested_element('line', ['id'], [
            'characterid', 'text_content', 'sortorder',
        ]);

        // Build the tree.
        $dialoguebuilder->add_child($submissions);
        $submissions->add_child($submission);

        $submission->add_child($characters);
        $characters->add_child($character);

        $submission->add_child($lines);
        $lines->add_child($line);

        // Define sources.
        $dialoguebuilder->set_source_table('dialoguebuilder', ['id' => backup::VAR_ACTIVITYID]);

        if ($userinfo) {
            $submission->set_source_table('dialoguebuilder_subs', ['dialoguebuilderid' => backup::VAR_PARENTID]);
            $character->set_source_table('dialoguebuilder_chars', ['submissionid' => backup::VAR_PARENTID]);
            $line->set_source_table('dialoguebuilder_lines', ['submissionid' => backup::VAR_PARENTID]);
        }

        // Define id annotations.
        $dialoguebuilder->annotate_ids('scale', 'grade');

        if ($userinfo) {
            $submission->annotate_ids('user', 'userid');
        }

        // Define file annotations.
        $dialoguebuilder->annotate_files('mod_dialoguebuilder', 'intro', null); // This file area hasn't itemid.

        if ($userinfo) {
            $character->annotate_files('mod_dialoguebuilder', 'avatar', 'id');
        }

        // Return the root element (dialoguebuilder), wrapped into standard activity structure.
        return $this->prepare_activity_structure($dialoguebuilder);
    }
}
