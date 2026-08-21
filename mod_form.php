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
 * The mod_form class for the dialoguebuilder activity.
 *
 * @package    mod_dialoguebuilder
 * @copyright  2026 Matheus Mathias
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Module instance settings form.
 */
class mod_dialoguebuilder_mod_form extends moodleform_mod {
    /**
     * Defines forms elements.
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        // General settings section.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Activity name.
        $mform->addElement('text', 'name', get_string('name', 'mod_dialoguebuilder'), ['size' => '64']);
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        // Activity introduction.
        $this->standard_intro_elements();
        
        $mform->addElement('header', 'gallerysettings', get_string('gallerysettings', 'mod_dialoguebuilder'));
        $galleryoptions = [
            0 => get_string('gallerymode_disabled', 'mod_dialoguebuilder'),
            1 => get_string('gallerymode_free', 'mod_dialoguebuilder'),
            2 => get_string('gallerymode_postfirst', 'mod_dialoguebuilder'),
            3 => get_string('gallerymode_afterclose', 'mod_dialoguebuilder')
        ];
        $mform->addElement('select', 'gallerymode', get_string('gallerymode', 'mod_dialoguebuilder'), $galleryoptions);
        $mform->addHelpButton('gallerymode', 'gallerymode', 'mod_dialoguebuilder');
        $mform->setDefault('gallerymode', 2);

        // Availability dates.
        $mform->addElement('header', 'availability', get_string('availability', 'mod_dialoguebuilder'));

        $mform->addElement('date_time_selector', 'timeopen', get_string('timeopen', 'mod_dialoguebuilder'), ['optional' => true]);
        $mform->setDefault('timeopen', 0);

        $mform->addElement('date_time_selector', 'timeclose', get_string('timeclose', 'mod_dialoguebuilder'), ['optional' => true]);
        $mform->setDefault('timeclose', 0);

        // Standard grading elements.
        $this->standard_grading_coursemodule_elements();

        // Standard course module elements.
        $this->standard_coursemodule_elements();

        // Add standard action buttons.
        $this->add_action_buttons();
    }
}
