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
 * @copyright  2026 Matheus
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
echo $OUTPUT->heading(format_string($dialoguebuilder->name));

// Display the introduction if present.
if (!empty($dialoguebuilder->intro)) {
    echo $OUTPUT->box(format_module_intro('dialoguebuilder', $dialoguebuilder, $cm->id), 'generalbox mod_introbox', 'intro');
}

// Capability-based display: Teacher vs Student.
// Using 'moodle/course:manageactivities' as a proxy for teacher capability until custom roles are fully defined.
if (has_capability('moodle/course:manageactivities', $context)) {
    // Teacher view.
    $submissioncount = $DB->count_records('dialoguebuilder_subs', ['dialoguebuilderid' => $dialoguebuilder->id]);

    echo $OUTPUT->box(
        "Resumo para Professores: $submissioncount alunos submeteram diálogos até o momento.",
        'generalbox teacher-view-box'
    );

    $reporturl = new moodle_url('/mod/dialoguebuilder/report.php', ['id' => $cm->id]);
    echo $OUTPUT->single_button($reporturl, get_string('viewsubmissions', 'mod_dialoguebuilder'), 'get', ['primary' => true]);
} else {
    // Student view.
    echo $OUTPUT->box('Leia as diretrizes do professor acima e crie o seu roteiro de falas.', 'generalbox student-view-box');

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
        foreach ($characters as $char) {
            $charmap[$char->id] = $char->name;
            if ($firstcharid === null) {
                $firstcharid = $char->id; // First character created is considered "self".
            }
        }

        $lines = $DB->get_records('dialoguebuilder_lines', ['submissionid' => $submission->id], 'sortorder ASC');

        $templatedata = ['lines' => [], 'chatid' => 'chat-' . uniqid()];
        foreach ($lines as $line) {
            $templatedata['lines'][] = [
                'charname' => isset($charmap[$line->characterid]) ? $charmap[$line->characterid] : 'Desconhecido',
                'text' => format_text($line->text_content, FORMAT_MOODLE),
                'is_self' => ($line->characterid == $firstcharid),
            ];
        }

        echo $OUTPUT->heading('Seu Diálogo', 3);
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
        $btntext = $submission ? 'Editar Diálogo' : 'Iniciar Diálogo';
        echo $OUTPUT->single_button($url, $btntext, 'get', ['primary' => true]);
    }
}

// Finish the page.
echo $OUTPUT->footer();
