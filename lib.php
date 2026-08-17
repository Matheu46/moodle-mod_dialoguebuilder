<?php
/**
 * Library of functions and constants for module dialoguebuilder.
 *
 * @package    mod_dialoguebuilder
 * @copyright  2026 Matheus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Adds a new dialoguebuilder instance.
 *
 * @param stdClass $dialoguebuilder The submitted data from the form.
 * @param mod_dialoguebuilder_mod_form $mform The form instance.
 * @return int The ID of the newly inserted dialoguebuilder record.
 */
function dialoguebuilder_add_instance($dialoguebuilder, $mform = null) {
    global $DB;

    $dialoguebuilder->timecreated = time();
    $dialoguebuilder->timemodified = $dialoguebuilder->timecreated;

    return $DB->insert_record('dialoguebuilder', $dialoguebuilder);
}

/**
 * Updates an existing dialoguebuilder instance.
 *
 * @param stdClass $dialoguebuilder The submitted data from the form.
 * @param mod_dialoguebuilder_mod_form $mform The form instance.
 * @return bool True if successful, false otherwise.
 */
function dialoguebuilder_update_instance($dialoguebuilder, $mform = null) {
    global $DB;

    $dialoguebuilder->timemodified = time();
    $dialoguebuilder->id = $dialoguebuilder->instance;

    return $DB->update_record('dialoguebuilder', $dialoguebuilder);
}

/**
 * Deletes a dialoguebuilder instance and all its associated data.
 *
 * @param int $id The instance ID to delete.
 * @return bool True if successful, false otherwise.
 */
function dialoguebuilder_delete_instance($id) {
    global $DB;

    if (!$dialoguebuilder = $DB->get_record('dialoguebuilder', ['id' => $id])) {
        return false;
    }

    // Get all submissions for this dialoguebuilder.
    $submissions = $DB->get_records('dialoguebuilder_subs', ['dialoguebuilderid' => $id]);
    
    if ($submissions) {
        $subids = array_keys($submissions);
        list($insql, $inparams) = $DB->get_in_or_equal($subids);
        
        // Delete lines and characters.
        $DB->delete_records_select('dialoguebuilder_lines', "submissionid $insql", $inparams);
        $DB->delete_records_select('dialoguebuilder_chars', "submissionid $insql", $inparams);
        
        // Delete submissions.
        $DB->delete_records_select('dialoguebuilder_subs', "dialoguebuilderid = ?", [$id]);
    }

    // Finally, delete the activity instance itself.
    $DB->delete_records('dialoguebuilder', ['id' => $id]);

    return true;
}

/**
 * Indicates API features that the dialoguebuilder supports.
 *
 * @param string $feature The feature being checked.
 * @return bool|null True if supported, false if not, null if unknown.
 */
function dialoguebuilder_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return false; // Can be enabled later if grading is implemented.
        case FEATURE_BACKUP_MOODLE2:
            return true;
        default:
            return null;
    }
}
