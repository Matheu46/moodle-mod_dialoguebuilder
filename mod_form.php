<?php
/**
 * The mod_form class for the dialoguebuilder activity.
 *
 * @package    mod_dialoguebuilder
 * @copyright  2026 Matheus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

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

        // Standard course module elements.
        $this->standard_coursemodule_elements();

        // Add standard action buttons.
        $this->add_action_buttons();
    }
}
