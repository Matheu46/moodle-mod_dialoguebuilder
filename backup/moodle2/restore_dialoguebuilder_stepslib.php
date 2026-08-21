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
 * Dialoguebuilder restore stepslib.
 *
 * @package    mod_dialoguebuilder
 * @copyright  2026 Matheus Mathias
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Structure step to restore one dialoguebuilder activity.
 */
class restore_dialoguebuilder_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {
        $paths = [];

        $paths[] = new restore_path_element('dialoguebuilder', '/activity/dialoguebuilder');
        $paths[] = new restore_path_element('dialoguebuilder_sub', '/activity/dialoguebuilder/submissions/submission');
        $paths[] = new restore_path_element('dialoguebuilder_char', '/activity/dialoguebuilder/submissions/submission/characters/character');
        $paths[] = new restore_path_element('dialoguebuilder_line', '/activity/dialoguebuilder/submissions/submission/lines/line');

        return $this->prepare_activity_structure($paths);
    }

    protected function process_dialoguebuilder($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        $data->timeopen = $this->apply_date_offset($data->timeopen);
        $data->timeclose = $this->apply_date_offset($data->timeclose);

        // insert the dialoguebuilder record.
        $newitemid = $DB->insert_record('dialoguebuilder', $data);
        // immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    protected function process_dialoguebuilder_sub($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->dialoguebuilderid = $this->get_new_parentid('dialoguebuilder');
        $data->userid = $this->get_mappingid('user', $data->userid);

        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('dialoguebuilder_subs', $data);
        $this->set_mapping('dialoguebuilder_sub', $oldid, $newitemid);
    }

    protected function process_dialoguebuilder_char($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->submissionid = $this->get_new_parentid('dialoguebuilder_sub');

        $newitemid = $DB->insert_record('dialoguebuilder_chars', $data);
        $this->set_mapping('dialoguebuilder_char', $oldid, $newitemid);
    }

    protected function process_dialoguebuilder_line($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->submissionid = $this->get_new_parentid('dialoguebuilder_sub');
        $data->characterid = $this->get_mappingid('dialoguebuilder_char', $data->characterid);

        $newitemid = $DB->insert_record('dialoguebuilder_lines', $data);
        $this->set_mapping('dialoguebuilder_line', $oldid, $newitemid);
    }

    protected function after_execute() {
        // Add dialoguebuilder related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_dialoguebuilder', 'intro', null);
        $this->add_related_files('mod_dialoguebuilder', 'avatar', 'dialoguebuilder_char');
    }
}
