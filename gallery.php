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
 * Gallery view of submitted dialogues.
 *
 * @package    mod_dialoguebuilder
 * @copyright  2026 Matheus Mathias
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$id = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('dialoguebuilder', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$dialoguebuilder = $DB->get_record('dialoguebuilder', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

$PAGE->set_url(new moodle_url('/mod/dialoguebuilder/gallery.php', ['id' => $cm->id]));
$PAGE->set_context($context);
$PAGE->set_title(get_string('viewgallery', 'mod_dialoguebuilder'));
$PAGE->set_heading(format_string($course->fullname));

// Gallery visibility checks
$canview = false;
$isteacher = has_capability('moodle/course:manageactivities', $context);
if (isset($dialoguebuilder->gallerymode) && $dialoguebuilder->gallerymode > 0) {
    if ($isteacher) {
        $canview = true;
    } else {
        $now = time();
        $submission = $DB->get_record('dialoguebuilder_subs', [
            'dialoguebuilderid' => $dialoguebuilder->id,
            'userid' => $USER->id,
        ]);
        $has_submitted = ($submission && $submission->status === 'submitted');

        if ($dialoguebuilder->gallerymode == 1) { // Free
            $canview = true;
        } else if ($dialoguebuilder->gallerymode == 2) { // Post before view
            if ($has_submitted) {
                $canview = true;
            }
        } else if ($dialoguebuilder->gallerymode == 3) { // After deadline
            if ($dialoguebuilder->timeclose > 0 && $now > $dialoguebuilder->timeclose) {
                $canview = true;
            }
        }
    }
}

if (!$canview) {
    throw new \moodle_exception('nopermissions', 'error', new moodle_url('/mod/dialoguebuilder/view.php', ['id' => $cm->id]));
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('viewgallery', 'mod_dialoguebuilder'));

echo html_writer::start_tag('div', ['class' => 'd-flex justify-content-between mb-3']);
echo html_writer::link(new moodle_url('/mod/dialoguebuilder/view.php', ['id' => $cm->id]), get_string('back', 'core'), ['class' => 'btn btn-secondary']);
echo html_writer::end_tag('div');

// Fetch all submitted submissions
$userfieldsapi = \core_user\fields::for_name();
$userfields = $userfieldsapi->get_sql('u', false, '', '', false)->selects;

$sql = "SELECT ds.*, $userfields
        FROM {dialoguebuilder_subs} ds
        JOIN {user} u ON u.id = ds.userid
        WHERE ds.dialoguebuilderid = :dbid AND ds.status = :status
        ORDER BY ds.timecreated DESC, ds.id DESC";

$submissions = $DB->get_records_sql($sql, ['dbid' => $dialoguebuilder->id, 'status' => 'submitted']);

if (empty($submissions)) {
    echo $OUTPUT->notification(get_string('nosubmissions', 'mod_dialoguebuilder'), 'info');
} else {
    $cards = [];
    $fs = get_file_storage();

    foreach ($submissions as $sub) {
        $fullname = fullname($sub);

        // Find characters to get avatars and title (first line)
        $characters = $DB->get_records('dialoguebuilder_chars', ['submissionid' => $sub->id], 'id ASC');
        $avatars = [];
        $preview = '';

        if (!empty($characters)) {
            $char_count = 0;
            foreach ($characters as $char) {
                if ($char_count >= 2) {
                    break;
                }

                $avatar_url = $OUTPUT->image_url('u/f2')->out(false);
                $files = $fs->get_area_files($context->id, 'mod_dialoguebuilder', 'avatar', $char->id, 'id DESC', false);
                if (!empty($files)) {
                    $file = reset($files);
                    $avatar_url = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename())->out(false);
                }

                $avatars[] = [
                    'url' => $avatar_url,
                    'is_second' => ($char_count === 1),
                ];
                $char_count++;
            }

            // Get first line for preview
            $firstline = $DB->get_record('dialoguebuilder_lines', ['submissionid' => $sub->id], '*', IGNORE_MULTIPLE, 'sortorder ASC');
            if ($firstline) {
                $preview = shorten_text(strip_tags($firstline->text_content), 60);
            }
        } else {
            // Fallback if no characters
            $avatars[] = [
                'url' => $OUTPUT->image_url('u/f2')->out(false),
                'is_second' => false,
            ];
        }

        $cards[] = [
            'authorname' => $fullname,
            'avatars' => $avatars,
            'preview' => $preview,
            'viewurl' => (new moodle_url('/mod/dialoguebuilder/view_submission.php', ['id' => $cm->id, 'subid' => $sub->id], 'region-main'))->out(false),
        ];
    }

    echo $OUTPUT->render_from_template('mod_dialoguebuilder/gallery', ['cards' => $cards]);
}

echo $OUTPUT->footer();
