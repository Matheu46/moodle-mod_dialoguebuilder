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
 * Editor for students to create their dialogue.
 *
 * @package    mod_dialoguebuilder
 * @copyright  2026 Matheus Mathias
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$id = required_param('id', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHANUMEXT);

$cm = get_coursemodule_from_id('dialoguebuilder', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$dialoguebuilder = $DB->get_record('dialoguebuilder', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/dialoguebuilder:submit', $context);

$PAGE->set_url('/mod/dialoguebuilder/edit.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($dialoguebuilder->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Check availability dates.
$now = time();
if ($dialoguebuilder->timeopen > 0 && $now < $dialoguebuilder->timeopen) {
    throw new \moodle_exception('notopenyet', 'mod_dialoguebuilder', '', userdate($dialoguebuilder->timeopen));
}
if ($dialoguebuilder->timeclose > 0 && $now > $dialoguebuilder->timeclose) {
    throw new \moodle_exception('submissionsclosed', 'mod_dialoguebuilder', '', userdate($dialoguebuilder->timeclose));
}

// Process form submission.
if (data_submitted() && ($action === 'save_draft' || $action === 'submit')) {
    require_sesskey();

    $dialoguedataraw = required_param('dialoguedata', PARAM_RAW);
    $dialoguedata = json_decode($dialoguedataraw);

    if ($dialoguedata) {
        // Find existing submission or create new.
        $submission = $DB->get_record('dialoguebuilder_subs', [
            'dialoguebuilderid' => $dialoguebuilder->id,
            'userid' => $USER->id,
        ]);

        if ($submission && $submission->status === 'submitted') {
            // Prevent reverting to draft if it was already submitted.
            $newstatus = 'submitted';
        } else {
            $newstatus = ($action === 'submit') ? 'submitted' : 'draft';
        }

        if (!$submission) {
            $submission = new stdClass();
            $submission->dialoguebuilderid = $dialoguebuilder->id;
            $submission->userid = $USER->id;
            $submission->status = $newstatus;
            $submission->timecreated = time();
            $submission->timemodified = time();
            $submission->id = $DB->insert_record('dialoguebuilder_subs', $submission);
        } else {
            $submission->timemodified = time();
            $submission->status = $newstatus;
            $DB->update_record('dialoguebuilder_subs', $submission);

            // Clean old lines only. Characters must be updated to preserve avatars.
            $DB->delete_records('dialoguebuilder_lines', ['submissionid' => $submission->id]);
        }

        // Save Characters.
        $charmap = []; // Map frontend temp ID to DB ID.
        $submittedcharids = []; // To track which characters still exist.

        // Get the standard Moodle upload limit for the course.
        $maxbytes = get_max_upload_file_size($CFG->maxbytes, $course->maxbytes);

        if (!empty($dialoguedata->characters)) {
            $fs = get_file_storage();
            $context = context_module::instance($cm->id);
            $existingchars = $DB->get_records('dialoguebuilder_chars', ['submissionid' => $submission->id]);

            foreach ($dialoguedata->characters as $char) {
                // If it's an existing character (id matches an existing DB record).
                if (isset($existingchars[$char->id])) {
                    $dbchar = $existingchars[$char->id];
                    $dbchar->name = clean_param($char->name, PARAM_TEXT);
                    $DB->update_record('dialoguebuilder_chars', $dbchar);
                    $charid = $dbchar->id;
                } else {
                    $newchar = new stdClass();
                    $newchar->submissionid = $submission->id;
                    $newchar->name = clean_param($char->name, PARAM_TEXT);
                    $newchar->avatar_itemid = 0;
                    $charid = $DB->insert_record('dialoguebuilder_chars', $newchar);
                }

                $charmap[$char->id] = $charid;
                $submittedcharids[] = $charid;

                // Handle avatar upload if present.
                if (
                    isset($_FILES['avatars']) &&
                    isset($_FILES['avatars']['tmp_name'][$char->id]) &&
                    $_FILES['avatars']['error'][$char->id] === UPLOAD_ERR_OK
                ) {
                    $tmpname = $_FILES['avatars']['tmp_name'][$char->id];
                    $filesize = filesize($tmpname);

                    if ($filesize <= $maxbytes) {
                        $fileinfo = [
                            'contextid' => $context->id,
                            'component' => 'mod_dialoguebuilder',
                            'filearea' => 'avatar',
                            'itemid' => $charid,
                            'filepath' => '/',
                            'filename' => 'avatar.png', // Or extract original extension.
                        ];
                        // Delete old avatar if exists.
                        $fs->delete_area_files($context->id, 'mod_dialoguebuilder', 'avatar', $charid);
                        // Save new avatar.
                        $fs->create_file_from_pathname($fileinfo, $tmpname);
                    }
                }
            }

            // Delete removed characters and their avatars.
            if (!empty($existingchars)) {
                foreach ($existingchars as $ec) {
                    if (!in_array($ec->id, $submittedcharids)) {
                        $DB->delete_records('dialoguebuilder_chars', ['id' => $ec->id]);
                        $fs->delete_area_files($context->id, 'mod_dialoguebuilder', 'avatar', $ec->id);
                    }
                }
            }
        }

        // Save Lines.
        if (!empty($dialoguedata->lines)) {
            $sortorder = 0;
            foreach ($dialoguedata->lines as $line) {
                if (!isset($charmap[$line->characterid])) {
                    continue; // Character was deleted or invalid.
                }

                $newline = new stdClass();
                $newline->submissionid = $submission->id;
                $newline->characterid = $charmap[$line->characterid];
                $newline->text_content = clean_param($line->text, PARAM_TEXT);
                $newline->sortorder = $sortorder++;

                $DB->insert_record('dialoguebuilder_lines', $newline);
            }
        }

        $msg = ($action === 'submit') ?
            get_string('tasksubmitted', 'mod_dialoguebuilder') :
            get_string('draftsaved', 'mod_dialoguebuilder');

        // Redirect to view.php with success message.
        redirect(
            new moodle_url('/mod/dialoguebuilder/view.php', ['id' => $cm->id]),
            $msg,
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }
}

// Load existing data if editing.
$submission = $DB->get_record('dialoguebuilder_subs', [
    'dialoguebuilderid' => $dialoguebuilder->id,
    'userid' => $USER->id,
]);

$characters = [];
$lines = [];

if ($submission) {
    $dbchars = $DB->get_records('dialoguebuilder_chars', ['submissionid' => $submission->id]);
    $fs = get_file_storage();
    $context = context_module::instance($cm->id);

    foreach ($dbchars as $c) {
        $avatarurl = '';
        $files = $fs->get_area_files($context->id, 'mod_dialoguebuilder', 'avatar', $c->id, 'id DESC', false);
        if (!empty($files)) {
            $file = reset($files);
            $avatarurl = moodle_url::make_pluginfile_url(
                $file->get_contextid(),
                $file->get_component(),
                $file->get_filearea(),
                $file->get_itemid(),
                $file->get_filepath(),
                $file->get_filename()
            )->out(false);
        }

        $characters[] = [
            'id' => $c->id,
            'name' => $c->name,
            'avatarurl' => $avatarurl,
        ];
    }

    $dblines = $DB->get_records('dialoguebuilder_lines', ['submissionid' => $submission->id], 'sortorder ASC');
    foreach ($dblines as $l) {
        $lines[] = [
            'characterid' => $l->characterid,
            'text' => $l->text_content,
        ];
    }
}

$initialdata = json_encode([
    'characters' => $characters,
    'lines' => $lines,
]);

// Prepare data for the template.
$templatedata = [
    'cmid' => $cm->id,
    'sesskey' => sesskey(),
    'initialdata' => $initialdata,
    'is_submitted' => ($submission && $submission->status === 'submitted'),
];

// Require the AMD module.
$PAGE->requires->js_call_amd('mod_dialoguebuilder/editor', 'init', [$cm->id]);

// Output the page.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('dialoguescript', 'mod_dialoguebuilder'));

echo $OUTPUT->render_from_template('mod_dialoguebuilder/editor', $templatedata);

echo $OUTPUT->footer();
