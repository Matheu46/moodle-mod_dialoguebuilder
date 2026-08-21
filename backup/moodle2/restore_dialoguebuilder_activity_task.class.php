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
 * Dialoguebuilder restore task.
 *
 * @package    mod_dialoguebuilder
 * @copyright  2026 Matheus Mathias
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/dialoguebuilder/backup/moodle2/restore_dialoguebuilder_stepslib.php');

/**
 * Dialoguebuilder restore task that provides all the settings and steps to perform one complete restore of the activity.
 */
class restore_dialoguebuilder_activity_task extends restore_activity_task {

    /**
     * Define (add) particular settings this activity can have.
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Define (add) particular steps this activity can have.
     */
    protected function define_my_steps() {
        // Dialoguebuilder only has one structure step.
        $this->add_step(new restore_dialoguebuilder_activity_structure_step('dialoguebuilder_structure', 'dialoguebuilder.xml'));
    }

    /**
     * Define the contents in the activity that must be processed by the link decoder.
     */
    static public function define_decode_contents() {
        $contents = [];

        $contents[] = new restore_decode_content('dialoguebuilder', ['intro'], 'dialoguebuilder');
        $contents[] = new restore_decode_content('dialoguebuilder_lines', ['text_content'], 'dialoguebuilder_lines');

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging to the activity to be executed by the link decoder.
     */
    static public function define_decode_rules() {
        $rules = [];

        $rules[] = new restore_decode_rule('DIALOGUEBUILDERINDEX', '/mod/dialoguebuilder/index.php?id=$1', 'course');
        $rules[] = new restore_decode_rule('DIALOGUEBUILDERVIEWBYID', '/mod/dialoguebuilder/view.php?id=$1', 'course_module');

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied by the
     * {@link restore_logs_processor} when restoring dialoguebuilder logs.
     *
     * @return restore_log_rule[]
     */
    static public function define_restore_log_rules() {
        $rules = [];
        return $rules;
    }
}
