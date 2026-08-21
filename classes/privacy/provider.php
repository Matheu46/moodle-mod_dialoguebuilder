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
 * Privacy provider for mod_dialoguebuilder.
 *
 * @package    mod_dialoguebuilder
 * @copyright  2026 Matheus Mathias
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_dialoguebuilder\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\helper;
use core_privacy\local\request\writer;
use core_privacy\local\request\userlist;
use core_privacy\local\request\approved_userlist;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider for mod_dialoguebuilder.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Returns metadata.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('dialoguebuilder_subs', [
            'dialoguebuilderid' => 'privacy:metadata:dialoguebuilder_subs:dialoguebuilderid',
            'userid' => 'privacy:metadata:dialoguebuilder_subs:userid',
            'status' => 'privacy:metadata:dialoguebuilder_subs:status',
            'timecreated' => 'privacy:metadata:dialoguebuilder_subs:timecreated',
            'grade' => 'privacy:metadata:dialoguebuilder_subs:grade',
            'feedback' => 'privacy:metadata:dialoguebuilder_subs:feedback',
            'timemodified' => 'privacy:metadata:dialoguebuilder_subs:timemodified',
        ], 'privacy:metadata:dialoguebuilder_subs');

        $collection->add_database_table('dialoguebuilder_chars', [
            'submissionid' => 'privacy:metadata:dialoguebuilder_chars:submissionid',
            'name' => 'privacy:metadata:dialoguebuilder_chars:name',
        ], 'privacy:metadata:dialoguebuilder_chars');

        $collection->add_database_table('dialoguebuilder_lines', [
            'submissionid' => 'privacy:metadata:dialoguebuilder_lines:submissionid',
            'characterid' => 'privacy:metadata:dialoguebuilder_lines:characterid',
            'text_content' => 'privacy:metadata:dialoguebuilder_lines:text_content',
        ], 'privacy:metadata:dialoguebuilder_lines');

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist $contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        $sql = "SELECT c.id
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {dialoguebuilder} db ON db.id = cm.instance
            INNER JOIN {dialoguebuilder_subs} ds ON ds.dialoguebuilderid = db.id
                 WHERE ds.userid = :userid";

        $params = [
            'modname' => 'dialoguebuilder',
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $userid,
        ];

        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $sql = "SELECT ds.userid
                  FROM {course_modules} cm
            INNER JOIN {dialoguebuilder} db ON db.id = cm.instance
            INNER JOIN {dialoguebuilder_subs} ds ON ds.dialoguebuilderid = db.id
                 WHERE cm.id = :cmid";

        $params = ['cmid' => $context->instanceid];

        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;

        [$insql, $inparams] = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);
        $sql = "SELECT c.id AS contextid, cm.id AS cmid, db.name AS dbname, ds.id AS subid, ds.status, ds.timecreated, ds.grade, ds.feedback, ds.timemodified
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {dialoguebuilder} db ON db.id = cm.instance
            INNER JOIN {dialoguebuilder_subs} ds ON ds.dialoguebuilderid = db.id
                 WHERE c.id {$insql} AND ds.userid = :userid";

        $params = array_merge($inparams, [
            'modname' => 'dialoguebuilder',
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $userid,
        ]);

        $submissions = $DB->get_recordset_sql($sql, $params);

        foreach ($submissions as $submission) {
            $context = \context::instance_by_id($submission->contextid);
            $subdata = (object)[
                'status' => $submission->status,
                'grade' => $submission->grade,
                'feedback' => $submission->feedback,
                'timecreated' => \core_privacy\local\request\transform::datetime($submission->timecreated),
                'timemodified' => \core_privacy\local\request\transform::datetime($submission->timemodified),
            ];

            writer::with_context($context)->export_data([get_string('pluginname', 'mod_dialoguebuilder'), get_string('submission', 'mod_dialoguebuilder')], $subdata);

            // Fetch characters and lines for this submission.
            $characters = $DB->get_records('dialoguebuilder_chars', ['submissionid' => $submission->subid]);
            $lines = $DB->get_records('dialoguebuilder_lines', ['submissionid' => $submission->subid], 'sortorder ASC');

            if (!empty($characters)) {
                $chardata = [];
                foreach ($characters as $char) {
                    $chardata[$char->id] = $char->name;
                }

                if (!empty($lines)) {
                    $linedata = [];
                    foreach ($lines as $line) {
                        $charname = isset($chardata[$line->characterid]) ? $chardata[$line->characterid] : 'Unknown';
                        $linedata[] = (object)[
                            'character' => $charname,
                            'text' => $line->text_content,
                        ];
                    }
                    writer::with_context($context)->export_related_data([get_string('pluginname', 'mod_dialoguebuilder'), get_string('submission', 'mod_dialoguebuilder')], 'dialogue', $linedata);
                }
            }
        }
        $submissions->close();
    }

    /**
     * Delete all use data which matches the specified context.
     *
     * @param \context $context A user context.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $cm = get_coursemodule_from_id('dialoguebuilder', $context->instanceid);
        if (!$cm) {
            return;
        }

        $submissions = $DB->get_records('dialoguebuilder_subs', ['dialoguebuilderid' => $cm->instance]);
        if (empty($submissions)) {
            return;
        }

        $fs = get_file_storage();
        foreach ($submissions as $sub) {
            $characters = $DB->get_records('dialoguebuilder_chars', ['submissionid' => $sub->id]);
            foreach ($characters as $char) {
                // Delete avatars.
                $fs->delete_area_files($context->id, 'mod_dialoguebuilder', 'avatar', $char->id);
            }
            $DB->delete_records('dialoguebuilder_lines', ['submissionid' => $sub->id]);
            $DB->delete_records('dialoguebuilder_chars', ['submissionid' => $sub->id]);
        }
        $DB->delete_records('dialoguebuilder_subs', ['dialoguebuilderid' => $cm->instance]);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;

        $fs = get_file_storage();
        foreach ($contextlist as $context) {
            if ($context->contextlevel != CONTEXT_MODULE) {
                continue;
            }

            $cm = get_coursemodule_from_id('dialoguebuilder', $context->instanceid);
            if (!$cm) {
                continue;
            }

            $submissions = $DB->get_records('dialoguebuilder_subs', [
                'dialoguebuilderid' => $cm->instance,
                'userid' => $userid,
            ]);

            foreach ($submissions as $sub) {
                $characters = $DB->get_records('dialoguebuilder_chars', ['submissionid' => $sub->id]);
                foreach ($characters as $char) {
                    // Delete avatars.
                    $fs->delete_area_files($context->id, 'mod_dialoguebuilder', 'avatar', $char->id);
                }
                $DB->delete_records('dialoguebuilder_lines', ['submissionid' => $sub->id]);
                $DB->delete_records('dialoguebuilder_chars', ['submissionid' => $sub->id]);
                $DB->delete_records('dialoguebuilder_subs', ['id' => $sub->id]);
            }
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $cm = get_coursemodule_from_id('dialoguebuilder', $context->instanceid);
        if (!$cm) {
            return;
        }

        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }

        [$insql, $inparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $params = array_merge(['dbid' => $cm->instance], $inparams);
        $sql = "SELECT id FROM {dialoguebuilder_subs} WHERE dialoguebuilderid = :dbid AND userid {$insql}";
        $submissions = $DB->get_records_sql($sql, $params);

        $fs = get_file_storage();
        foreach ($submissions as $sub) {
            $characters = $DB->get_records('dialoguebuilder_chars', ['submissionid' => $sub->id]);
            foreach ($characters as $char) {
                $fs->delete_area_files($context->id, 'mod_dialoguebuilder', 'avatar', $char->id);
            }
            $DB->delete_records('dialoguebuilder_lines', ['submissionid' => $sub->id]);
            $DB->delete_records('dialoguebuilder_chars', ['submissionid' => $sub->id]);
            $DB->delete_records('dialoguebuilder_subs', ['id' => $sub->id]);
        }
    }
}
