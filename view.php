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
 * Prints a particular instance of dialoguebuilder.
 *
 * @package    mod_dialoguebuilder
 * @copyright  2026 Matheus Mathias
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

// Retrieve the required parameter 'id' (course module ID).
$id = required_param('id', PARAM_INT);

// Get the course module, course, and activity instance records.
$cm = get_coursemodule_from_id('dialoguebuilder', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$dialoguebuilder = $DB->get_record('dialoguebuilder', ['id' => $cm->instance], '*', MUST_EXIST);

// Security checks.
require_login($course, true, $cm);
$context = context_module::instance($cm->id);

// Note: the view capability should be added in db/access.php in the future.
// Right now, any logged-in user who can access the course module can view this.
require_capability('mod/dialoguebuilder:view', $context);

// Page setup.
$PAGE->set_url('/mod/dialoguebuilder/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($dialoguebuilder->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Output starts here.
echo $OUTPUT->header();

// Capability-based display: Teacher vs Student.
// Using 'moodle/course:manageactivities' as a proxy for teacher capability until custom roles are fully defined.
if (has_capability('moodle/course:manageactivities', $context)) {
    // Teacher view: Grading summary.
    require_once($CFG->dirroot . '/lib/enrollib.php');

    echo $OUTPUT->box_start('generalbox boxaligncenter text-center mt-4 mb-4', 'gradingsummarybox');
    echo $OUTPUT->heading(get_string('gradingsummary', 'mod_dialoguebuilder'), 3);

    $participants = count_enrolled_users($context, 'mod/dialoguebuilder:submit');
    $submissioncount = $DB->count_records('dialoguebuilder_subs', ['dialoguebuilderid' => $dialoguebuilder->id, 'status' => 'submitted']);
    $needsgrading = $DB->count_records_select('dialoguebuilder_subs', 'dialoguebuilderid = ? AND grade IS NULL AND status = ?', [$dialoguebuilder->id, 'submitted']);

    $timeremaining = '';
    if ($dialoguebuilder->timeclose > 0) {
        $now = time();
        if ($dialoguebuilder->timeclose > $now) {
            $timeremaining = format_time($dialoguebuilder->timeclose - $now);
        } else {
            $timeremaining = html_writer::tag('span', get_string('assignmentisdue', 'mod_dialoguebuilder'), ['class' => 'text-danger']);
        }
    }

    $table = new html_table();
    $table->attributes['class'] = 'generaltable grading-summary-table mt-3 mx-auto';
    $table->attributes['style'] = 'max-width: 600px; width: 100%;';

    $hidden = ($cm->visible == 0) ? get_string('yes') : get_string('no');
    $table->data[] = [get_string('hiddenfromstudents', 'mod_dialoguebuilder'), $hidden];
    $table->data[] = [get_string('participants', 'mod_dialoguebuilder'), $participants];
    $table->data[] = [get_string('submitted', 'mod_dialoguebuilder'), $submissioncount];
    $table->data[] = [get_string('needsgrading', 'mod_dialoguebuilder'), $needsgrading];

    if ($dialoguebuilder->timeclose > 0) {
        $table->data[] = [get_string('timeremaining', 'mod_dialoguebuilder'), $timeremaining];
    }

    echo html_writer::table($table);

    $reporturl = new moodle_url('/mod/dialoguebuilder/report.php', ['id' => $cm->id]);
    echo html_writer::start_tag('div', ['class' => 'text-center mt-3 mb-2']);
    echo $OUTPUT->single_button($reporturl, get_string('viewsubmissions', 'mod_dialoguebuilder'), 'get', ['type' => \single_button::BUTTON_PRIMARY]);
    echo html_writer::end_tag('div');

    echo $OUTPUT->box_end();
} else {
    // Student view.
    echo $OUTPUT->box(get_string('studentguidelines', 'mod_dialoguebuilder'), 'generalbox student-view-box');

    // Check if the student has already submitted.
    $submission = $DB->get_record('dialoguebuilder_subs', [
        'dialoguebuilderid' => $dialoguebuilder->id,
        'userid' => $USER->id,
    ]);

    if ($submission) {
        // Fetch characters to map IDs to names.
        $characters = $DB->get_records('dialoguebuilder_chars', ['submissionid' => $submission->id], 'id ASC');
        $charmap = [];
        $firstcharid = null;
        $fs = get_file_storage();

        foreach ($characters as $char) {
            $avatarurl = '';
            $files = $fs->get_area_files($context->id, 'mod_dialoguebuilder', 'avatar', $char->id, 'id DESC', false);
            if (!empty($files)) {
                $file = reset($files);
                $avatarurl = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename())->out(false);
            }
            if (empty($avatarurl)) {
                $avatarurl = $OUTPUT->image_url('u/f2')->out(false);
            }

            $charmap[$char->id] = [
                'name' => $char->name,
                'avatarurl' => $avatarurl,
            ];
            if ($firstcharid === null) {
                $firstcharid = $char->id; // First character created is considered "self".
            }
        }

        $lines = $DB->get_records('dialoguebuilder_lines', ['submissionid' => $submission->id], 'sortorder ASC');

        $templatedata = ['lines' => [], 'chatid' => 'chat-' . uniqid()];
        $lastcharid = null;
        foreach ($lines as $line) {
            $charinfo = isset($charmap[$line->characterid]) ? $charmap[$line->characterid] : ['name' => get_string('unknown', 'mod_dialoguebuilder'), 'avatarurl' => ''];
            $templatedata['lines'][] = [
                'charname' => $charinfo['name'],
                'avatarurl' => $charinfo['avatarurl'],
                'text' => format_text($line->text_content, FORMAT_MOODLE),
                'is_self' => ($line->characterid == $firstcharid),
                'same_as_prev' => ($line->characterid == $lastcharid),
            ];
            $lastcharid = $line->characterid;
        }

        $statusstr = ($submission->status === 'submitted') ? get_string('status_submitted', 'mod_dialoguebuilder') : get_string('status_draft', 'mod_dialoguebuilder');
        $badgeclass = ($submission->status === 'submitted') ? 'badge badge-success bg-success' : 'badge badge-warning bg-warning';

        echo html_writer::tag(
            'div',
            html_writer::tag('strong', get_string('status', 'mod_dialoguebuilder') . ': ') .
            html_writer::tag('span', $statusstr, ['class' => $badgeclass]),
            ['class' => 'mb-3 text-center']
        );

        echo $OUTPUT->heading(get_string('yourdialogue', 'mod_dialoguebuilder'), 3, 'text-center');
        echo $OUTPUT->render_from_template('mod_dialoguebuilder/chat_view', $templatedata);
        echo $OUTPUT->render_from_template('mod_dialoguebuilder/player', ['chatid' => $templatedata['chatid']]);
    }

    // Check dates.
    $now = time();
    $isopen = true;
    if ($dialoguebuilder->timeopen > 0 && $now < $dialoguebuilder->timeopen) {
        $isopen = false;
        echo $OUTPUT->notification(get_string('notopenyet', 'mod_dialoguebuilder', userdate($dialoguebuilder->timeopen)), 'info');
    } else if ($dialoguebuilder->timeclose > 0 && $now > $dialoguebuilder->timeclose) {
        $isopen = false;
        echo $OUTPUT->notification(
            get_string('submissionsclosed', 'mod_dialoguebuilder', userdate($dialoguebuilder->timeclose)),
            'warning'
        );
    }

    // Render a button to start/edit the dialogue only if open.
    if ($isopen) {
        $url = new moodle_url('/mod/dialoguebuilder/edit.php', ['id' => $cm->id]);
        $btntext = $submission ? get_string('editdialogue', 'mod_dialoguebuilder') : get_string('startdialogue', 'mod_dialoguebuilder');
        echo html_writer::start_tag('div', ['class' => 'text-center mt-3 mb-3']);
        echo $OUTPUT->single_button($url, $btntext, 'get', ['type' => \single_button::BUTTON_PRIMARY]);
        echo html_writer::end_tag('div');
    }
}

// Gallery button logic.
$showgallery = false;
$isteacher = has_capability('moodle/course:manageactivities', $context);
if (isset($dialoguebuilder->gallerymode) && $dialoguebuilder->gallerymode > 0) {
    if ($isteacher) {
        $showgallery = true;
    } else {
        $now = time();
        $has_submitted = (isset($submission) && $submission->status === 'submitted');

        if ($dialoguebuilder->gallerymode == 1) { // Free
            $showgallery = true;
        } else if ($dialoguebuilder->gallerymode == 2) { // Post before view
            if ($has_submitted) {
                $showgallery = true;
            }
        } else if ($dialoguebuilder->gallerymode == 3) { // After deadline
            if ($dialoguebuilder->timeclose > 0 && $now > $dialoguebuilder->timeclose) {
                $showgallery = true;
            }
        }
    }
}

if ($showgallery) {
    $galleryurl = new moodle_url('/mod/dialoguebuilder/gallery.php', ['id' => $cm->id]);
    echo html_writer::start_tag('div', ['class' => 'text-center mt-4 mb-3']);
    echo $OUTPUT->single_button($galleryurl, get_string('viewgallery', 'mod_dialoguebuilder'), 'get', ['type' => \single_button::BUTTON_SECONDARY, 'class' => 'btn']);
    echo html_writer::end_tag('div');
}

// Finish the page.
echo $OUTPUT->footer();
