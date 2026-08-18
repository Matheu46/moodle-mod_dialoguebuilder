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

    $id = $DB->insert_record('dialoguebuilder', $dialoguebuilder);
    $dialoguebuilder->id = $id;
    dialoguebuilder_grade_item_update($dialoguebuilder);

    return $id;
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

    $result = $DB->update_record('dialoguebuilder', $dialoguebuilder);
    dialoguebuilder_grade_item_update($dialoguebuilder);
    dialoguebuilder_update_grades($dialoguebuilder);

    return $result;
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
        [$insql, $inparams] = $DB->get_in_or_equal($subids);

        // Delete lines and characters.
        $DB->delete_records_select('dialoguebuilder_lines', "submissionid $insql", $inparams);
        $DB->delete_records_select('dialoguebuilder_chars', "submissionid $insql", $inparams);

        // Delete submissions.
        $DB->delete_records_select('dialoguebuilder_subs', "dialoguebuilderid = ?", [$id]);
    }

    // Finally, delete the activity instance itself.
    $DB->delete_records('dialoguebuilder', ['id' => $id]);

    dialoguebuilder_grade_item_delete($dialoguebuilder);

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
            return true;
        case FEATURE_GRADE_OUTCOMES:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        default:
            return null;
    }
}

/**
 * Update grade item for the dialoguebuilder.
 *
 * @param stdClass $dialoguebuilder object
 * @param mixed $grades array or null
 * @return int grade update status
 */
function dialoguebuilder_grade_item_update($dialoguebuilder, $grades = null) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    $item = [];
    $item['itemname'] = clean_param($dialoguebuilder->name, PARAM_NOTAGS);
    $item['gradetype'] = GRADE_TYPE_VALUE;
    $item['grademax']  = $dialoguebuilder->grade;
    $item['grademin']  = 0;

    if ($dialoguebuilder->grade == 0) {
        $item['gradetype'] = GRADE_TYPE_NONE;
    } else if ($dialoguebuilder->grade < 0) {
        $item['gradetype'] = GRADE_TYPE_SCALE;
        $item['scaleid'] = -$dialoguebuilder->grade;
    }

    return grade_update('mod/dialoguebuilder', $dialoguebuilder->course, 'mod', 'dialoguebuilder', $dialoguebuilder->id, 0, $grades, $item);
}

/**
 * Update grades for a given dialoguebuilder instance.
 *
 * @param stdClass $dialoguebuilder object
 * @param int $userid optional user ID
 * @param bool $nullifnone return null if grade does not exist
 */
function dialoguebuilder_update_grades($dialoguebuilder, $userid = 0, $nullifnone = true) {
    global $CFG, $DB;
    require_once($CFG->libdir . '/gradelib.php');

    if ($dialoguebuilder->grade == 0) {
        dialoguebuilder_grade_item_update($dialoguebuilder);
    } else {
        $sql = "SELECT userid, grade, feedback, timemodified AS datesubmitted
                  FROM {dialoguebuilder_subs}
                 WHERE dialoguebuilderid = :id
                   AND grade IS NOT NULL";
        $params = ['id' => $dialoguebuilder->id];

        if ($userid) {
            $sql .= " AND userid = :userid";
            $params['userid'] = $userid;
        }

        if ($rs = $DB->get_recordset_sql($sql, $params)) {
            $grades = [];
            foreach ($rs as $sub) {
                $grades[$sub->userid] = new stdClass();
                $grades[$sub->userid]->userid = $sub->userid;
                $grades[$sub->userid]->rawgrade = $sub->grade;
                if (!empty($sub->feedback)) {
                    $grades[$sub->userid]->feedback = $sub->feedback;
                    $grades[$sub->userid]->feedbackformat = FORMAT_MOODLE;
                }
                $grades[$sub->userid]->datesubmitted = $sub->datesubmitted;
            }
            $rs->close();
            dialoguebuilder_grade_item_update($dialoguebuilder, $grades);
        } else {
            dialoguebuilder_grade_item_update($dialoguebuilder);
        }
    }
}

/**
 * Delete grade item for given dialoguebuilder instance.
 *
 * @param stdClass $dialoguebuilder object
 * @return int grade deletion status
 */
function dialoguebuilder_grade_item_delete($dialoguebuilder) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    return grade_update('mod/dialoguebuilder', $dialoguebuilder->course, 'mod', 'dialoguebuilder', $dialoguebuilder->id, 0, null, ['deleted' => 1]);
}
