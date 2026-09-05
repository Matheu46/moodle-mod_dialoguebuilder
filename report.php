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
 * Report page for teachers to view submissions.
 *
 * @package    mod_dialoguebuilder
 * @copyright  2026 Matheus Mathias
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$id = required_param('id', PARAM_INT);
$action = optional_param('action', 'list', PARAM_ALPHA);
$subid = optional_param('subid', 0, PARAM_INT);

$cm = get_coursemodule_from_id('dialoguebuilder', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$dialoguebuilder = $DB->get_record('dialoguebuilder', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

// Teacher capability check.
require_capability('mod/dialoguebuilder:grade', $context);

$PAGE->set_url(new moodle_url('/mod/dialoguebuilder/report.php', ['id' => $cm->id]));
$PAGE->set_context($context);

$PAGE->activityheader->set_description('');

if ($action === 'grade' && data_submitted() && confirm_sesskey()) {
    $grade = optional_param('grade', null, PARAM_FLOAT);
    $feedback = optional_param('feedback', '', PARAM_RAW);
    $saveandnext = optional_param('saveandnext', false, PARAM_BOOL);
    $nextsubid = optional_param('nextsubid', 0, PARAM_INT);
    $submission = $DB->get_record(
        'dialoguebuilder_subs',
        ['id' => $subid, 'dialoguebuilderid' => $dialoguebuilder->id],
        '*',
        MUST_EXIST
    );

    $submission->grade = $grade;
    $submission->feedback = $feedback;
    $submission->timemodified = time();
    $DB->update_record('dialoguebuilder_subs', $submission);

    dialoguebuilder_update_grades($dialoguebuilder, $submission->userid);

    if ($saveandnext && $nextsubid) {
        redirect(
            new moodle_url('/mod/dialoguebuilder/report.php', ['id' => $cm->id, 'action' => 'view', 'subid' => $nextsubid]),
            get_string('gradesaved', 'mod_dialoguebuilder'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    } else {
        redirect(
            new moodle_url('/mod/dialoguebuilder/report.php', ['id' => $cm->id]),
            get_string('gradesaved', 'mod_dialoguebuilder'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }
}

if ($action === 'view' && $subid) {
    // View a specific submission.
    $submission = $DB->get_record(
        'dialoguebuilder_subs',
        ['id' => $subid, 'dialoguebuilderid' => $dialoguebuilder->id],
        '*',
        MUST_EXIST
    );
    $user = $DB->get_record('user', ['id' => $submission->userid], '*', MUST_EXIST);

    $PAGE->set_title(get_string('viewdialogue', 'mod_dialoguebuilder'));
    $PAGE->set_heading(fullname($user));
    $PAGE->set_pagelayout('popup');

    echo $OUTPUT->header();

    echo $OUTPUT->heading(get_string('dialoguefor', 'mod_dialoguebuilder', fullname($user)));

    // Navigation logic.
    $allsubids = $DB->get_fieldset_sql(
        "SELECT id FROM {dialoguebuilder_subs} WHERE dialoguebuilderid = :dbid ORDER BY timecreated DESC, id DESC",
        ['dbid' => $dialoguebuilder->id]
    );
    $currentindex = array_search($subid, $allsubids);
    $prevsubid = ($currentindex > 0) ? $allsubids[$currentindex - 1] : null;
    $nextsubid = ($currentindex !== false && $currentindex < count($allsubids) - 1) ? $allsubids[$currentindex + 1] : null;

    // Navigation row.
    echo html_writer::start_tag('div', ['class' => 'd-flex justify-content-between align-items-center mb-3']);

    $backurl = new moodle_url('/mod/dialoguebuilder/report.php', ['id' => $cm->id]);
    echo html_writer::link($backurl, get_string('backtosubmissions', 'mod_dialoguebuilder'), ['class' => 'btn btn-secondary']);

    echo html_writer::start_tag('div');
    if ($prevsubid) {
        $prevurl = new moodle_url('/mod/dialoguebuilder/report.php', ['id' => $cm->id, 'action' => 'view', 'subid' => $prevsubid]);
        echo html_writer::link(
            $prevurl,
            '&laquo; ' . get_string('previous', 'mod_dialoguebuilder'),
            ['class' => 'btn btn-outline-primary mr-2']
        );
    }

    if ($currentindex !== false) {
        echo html_writer::tag('span', ' ' . ($currentindex + 1) . ' / ' . count($allsubids) . ' ', ['class' => 'mx-2 text-muted']);
    }

    if ($nextsubid) {
        $nexturl = new moodle_url('/mod/dialoguebuilder/report.php', ['id' => $cm->id, 'action' => 'view', 'subid' => $nextsubid]);
        echo html_writer::link(
            $nexturl,
            get_string('next', 'mod_dialoguebuilder') . ' &raquo;',
            ['class' => 'btn btn-outline-primary ml-2']
        );
    }
    echo html_writer::end_tag('div');

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

    echo html_writer::start_tag('div', ['class' => 'row']);
    echo html_writer::start_tag('div', ['class' => 'col-lg-8 col-md-12']);

    if (empty($lines)) {
        echo $OUTPUT->notification(get_string('nodialoguefound', 'mod_dialoguebuilder'), 'info');
    } else {
        $templatedata = ['lines' => [], 'chatid' => 'chat-' . uniqid(), 'is_static' => true];
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
        echo $OUTPUT->render_from_template('mod_dialoguebuilder/chat_view', $templatedata);
        echo $OUTPUT->render_from_template(
            'mod_dialoguebuilder/player',
            ['chatid' => $templatedata['chatid'], 'is_static' => true]
        );
    }

    echo html_writer::end_tag('div'); // End col-lg-8.

    // Grading form column.
    echo html_writer::start_tag('div', ['class' => 'col-lg-4 col-md-12']);

    // Grading form.
    if ($dialoguebuilder->grade > 0) {
        echo $OUTPUT->box_start(
            'generalbox mt-4 mt-lg-0 p-4',
            'grading-box',
            ['style' => 'background: #f8f9fa; border-radius: 8px; border: 1px solid #ddd;']
        );
        echo $OUTPUT->heading(get_string('grade', 'mod_dialoguebuilder'), 3);

        $gradeurl = new moodle_url('/mod/dialoguebuilder/report.php');
        echo html_writer::start_tag('form', ['action' => $gradeurl, 'method' => 'post']);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $cm->id]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'grade']);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'subid', 'value' => $subid]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'nextsubid', 'value' => $nextsubid ? $nextsubid : 0]);

        echo html_writer::start_tag('div', ['class' => 'form-group mb-3']);
        echo html_writer::label(
            get_string('grade', 'mod_dialoguebuilder') . ' (0 - ' . $dialoguebuilder->grade . '):',
            'gradeinput',
            false,
            ['class' => 'd-block font-weight-bold']
        );
        echo html_writer::empty_tag('input', [
            'type' => 'number',
            'name' => 'grade',
            'id' => 'gradeinput',
            'class' => 'form-control',
            'style' => 'max-width: 150px;',
            'min' => '0',
            'max' => $dialoguebuilder->grade,
            'step' => '0.1',
            'value' => isset($submission->grade) ? $submission->grade : '',
        ]);
        echo html_writer::end_tag('div');

        echo html_writer::start_tag('div', ['class' => 'form-group mb-3']);
        echo html_writer::label(
            get_string('feedback', 'mod_dialoguebuilder') . ':',
            'feedbackinput',
            false,
            ['class' => 'd-block font-weight-bold']
        );
        echo html_writer::tag(
            'textarea',
            s(isset($submission->feedback) ? $submission->feedback : ''),
            ['name' => 'feedback', 'id' => 'feedbackinput', 'class' => 'form-control w-100', 'rows' => '4']
        );
        echo html_writer::end_tag('div');

        echo html_writer::start_tag('div', ['class' => 'd-flex gap-2']);
        echo html_writer::empty_tag(
            'input',
            ['type' => 'submit', 'name' => 'save', 'value' => get_string('savechanges'), 'class' => 'btn btn-primary']
        );

        if ($nextsubid) {
            echo html_writer::empty_tag(
                'input',
                [
                    'type' => 'submit',
                    'name' => 'saveandnext',
                    'value' => get_string('saveandnext', 'mod_dialoguebuilder'),
                    'class' => 'btn btn-success',
                ]
            );
        }
        echo html_writer::end_tag('div');

        echo html_writer::end_tag('form');
        echo $OUTPUT->box_end();
    }

    echo html_writer::end_tag('div'); // End col-lg-4.
    echo html_writer::end_tag('div'); // End row.
} else {
    // List all submissions.
    $PAGE->set_title(get_string('submissions', 'mod_dialoguebuilder'));
    $PAGE->set_heading(format_string($course->fullname));

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('submissions', 'mod_dialoguebuilder'));

    $sql = "SELECT ds.*
            FROM {dialoguebuilder_subs} ds
            WHERE ds.dialoguebuilderid = :dbid
            ORDER BY ds.timecreated DESC, ds.id DESC";

    $submissions = $DB->get_records_sql($sql, ['dbid' => $dialoguebuilder->id]);

    if (empty($submissions)) {
        echo $OUTPUT->notification(get_string('nosubmissions', 'mod_dialoguebuilder'), 'info');
    } else {
        $table = new html_table();
        $table->head = [
            get_string('student', 'mod_dialoguebuilder'),
            get_string('status', 'mod_dialoguebuilder'),
            get_string('characters', 'mod_dialoguebuilder'),
            get_string('lines', 'mod_dialoguebuilder'),
            get_string('grade', 'mod_dialoguebuilder'),
            get_string('timecreated', 'mod_dialoguebuilder'),
            get_string('actions', 'mod_dialoguebuilder'),
        ];
        $table->data = [];

        // Bulk preload data.
        $subids = array_keys($submissions);
        $userids = [];
        foreach ($submissions as $sub) {
            $userids[$sub->userid] = $sub->userid;
        }

        [$uinsql, $uinparams] = $DB->get_in_or_equal($userids);
        $users = $DB->get_records_select('user', "id $uinsql", $uinparams);

        [$sinsql, $sinparams] = $DB->get_in_or_equal($subids);

        // Preload char counts.
        $charssql = "SELECT submissionid, COUNT(1) AS count
                       FROM {dialoguebuilder_chars}
                      WHERE submissionid $sinsql
                   GROUP BY submissionid";
        $charcounts = $DB->get_records_sql($charssql, $sinparams);

        // Preload line counts.
        $linessql = "SELECT submissionid, COUNT(1) AS count
                       FROM {dialoguebuilder_lines}
                      WHERE submissionid $sinsql
                   GROUP BY submissionid";
        $linecounts = $DB->get_records_sql($linessql, $sinparams);

        foreach ($submissions as $sub) {
            $user = $users[$sub->userid];
            $fullname = fullname($user);

            $charcount = isset($charcounts[$sub->id]) ? $charcounts[$sub->id]->count : 0;
            $linecount = isset($linecounts[$sub->id]) ? $linecounts[$sub->id]->count : 0;

            $viewurl = new moodle_url(
                '/mod/dialoguebuilder/report.php',
                ['id' => $cm->id, 'action' => 'view', 'subid' => $sub->id]
            );
            $viewlink = html_writer::link(
                $viewurl,
                get_string('viewdialogue', 'mod_dialoguebuilder'),
                ['class' => 'btn btn-primary btn-sm']
            );

            $gradedisplay = isset($sub->grade) ? format_float($sub->grade, 1) : '-';

            $statusstr = ($sub->status === 'submitted') ?
                get_string('status_submitted', 'mod_dialoguebuilder') :
                get_string('status_draft', 'mod_dialoguebuilder');

            $table->data[] = [
                $fullname,
                $statusstr,
                $charcount,
                $linecount,
                $gradedisplay,
                userdate($sub->timecreated),
                $viewlink,
            ];
        }

        echo html_writer::table($table);
    }
}

echo $OUTPUT->footer();
