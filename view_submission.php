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
 * View a single submission from the gallery.
 *
 * @package    mod_dialoguebuilder
 * @copyright  2026 Matheus Mathias
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$id = required_param('id', PARAM_INT);
$subid = required_param('subid', PARAM_INT);

$cm = get_coursemodule_from_id('dialoguebuilder', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$dialoguebuilder = $DB->get_record('dialoguebuilder', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

$PAGE->set_url(new moodle_url('/mod/dialoguebuilder/view_submission.php', ['id' => $cm->id, 'subid' => $subid]));
$PAGE->set_context($context);

// Fetch submission.
$submission = $DB->get_record(
    'dialoguebuilder_subs',
    ['id' => $subid, 'dialoguebuilderid' => $dialoguebuilder->id],
    '*',
    MUST_EXIST
);
$user = $DB->get_record('user', ['id' => $submission->userid], '*', MUST_EXIST);

$PAGE->set_title(get_string('viewdialogue', 'mod_dialoguebuilder'));
$PAGE->set_heading(fullname($user));

// Gallery visibility checks.
$canview = false;
$isteacher = has_capability('mod/dialoguebuilder:grade', $context);
if ($isteacher) {
    $canview = true;
} else if (isset($dialoguebuilder->gallerymode) && $dialoguebuilder->gallerymode > 0) {
    if ($submission->status === 'submitted') { // Can only view submitted works in gallery.
        $now = time();
        $mysubmission = $DB->get_record('dialoguebuilder_subs', [
            'dialoguebuilderid' => $dialoguebuilder->id,
            'userid' => $USER->id,
        ]);
        $hassubmitted = ($mysubmission && $mysubmission->status === 'submitted');

        if ($dialoguebuilder->gallerymode == 1) { // Free.
            $canview = true;
        } else if ($dialoguebuilder->gallerymode == 2) { // Post before view.
            if ($hassubmitted) {
                $canview = true;
            }
        } else if ($dialoguebuilder->gallerymode == 3) { // After deadline.
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
echo $OUTPUT->heading(get_string('dialoguefor', 'mod_dialoguebuilder', fullname($user)));

echo html_writer::start_tag('div', ['class' => 'mb-3']);
$backurl = new moodle_url('/mod/dialoguebuilder/gallery.php', ['id' => $cm->id]);
echo html_writer::link($backurl, get_string('back', 'core'), ['class' => 'btn btn-secondary']);
echo html_writer::end_tag('div');

// Fetch characters.
$characters = $DB->get_records('dialoguebuilder_chars', ['submissionid' => $submission->id], 'id ASC');
$charmap = [];
$firstcharid = null;
$fs = get_file_storage();

foreach ($characters as $char) {
    $avatarurl = '';
    $files = $fs->get_area_files($context->id, 'mod_dialoguebuilder', 'avatar', $char->id, 'id DESC', false);
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
    if (empty($avatarurl)) {
        $avatarurl = $OUTPUT->image_url('u/f2')->out(false);
    }

    $charmap[$char->id] = [
        'name' => $char->name,
        'avatarurl' => $avatarurl,
    ];
    if ($firstcharid === null) {
        $firstcharid = $char->id;
    }
}

// Fetch lines.
$lines = $DB->get_records('dialoguebuilder_lines', ['submissionid' => $submission->id], 'sortorder ASC');

echo html_writer::start_tag('div', ['class' => 'row justify-content-center']);
echo html_writer::start_tag('div', ['class' => 'col-lg-8 col-md-12']);

if (empty($lines)) {
    echo $OUTPUT->notification(get_string('nodialoguefound', 'mod_dialoguebuilder'), 'info');
} else {
    $templatedata = ['lines' => [], 'chatid' => 'chat-' . uniqid(), 'is_static' => false];
    $lastcharid = null;
    foreach ($lines as $line) {
        $charinfo = isset($charmap[$line->characterid]) ?
            $charmap[$line->characterid] :
            ['name' => get_string('unknown', 'mod_dialoguebuilder'), 'avatarurl' => ''];
        $templatedata['lines'][] = [
            'charname' => $charinfo['name'],
            'avatarurl' => $charinfo['avatarurl'],
            'text' => format_text($line->text_content, FORMAT_MOODLE),
            'is_self' => ($line->characterid == $firstcharid),
            'same_as_prev' => ($line->characterid == $lastcharid),
        ];
        $lastcharid = $line->characterid;
    }
    $templatedata['cmid'] = $cm->id;
    $templatedata['subid'] = $subid;
    $templatedata['characters'] = array_values($charmap);
    $theme = optional_param('chattheme', 'modern', PARAM_ALPHA);
    $templatename = ($theme === 'msn') ? 'mod_dialoguebuilder/chat_view_msn' : 'mod_dialoguebuilder/chat_view';
    echo $OUTPUT->render_from_template($templatename, $templatedata);
    echo $OUTPUT->render_from_template('mod_dialoguebuilder/player', ['chatid' => $templatedata['chatid'], 'is_static' => false]);
}

echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

echo $OUTPUT->footer();
