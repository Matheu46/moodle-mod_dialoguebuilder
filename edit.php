<?php
/**
 * Editor for students to create their dialogue.
 *
 * @package    mod_dialoguebuilder
 * @copyright  2026 Matheus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save') {
    require_sesskey();
    
    $dialoguedata_raw = required_param('dialoguedata', PARAM_RAW);
    $dialoguedata = json_decode($dialoguedata_raw);

    if ($dialoguedata) {
        // Find existing submission or create new.
        $submission = $DB->get_record('dialoguebuilder_subs', [
            'dialoguebuilderid' => $dialoguebuilder->id,
            'userid' => $USER->id
        ]);

        if (!$submission) {
            $submission = new stdClass();
            $submission->dialoguebuilderid = $dialoguebuilder->id;
            $submission->userid = $USER->id;
            $submission->status = 'submitted'; // or draft
            $submission->timecreated = time();
            $submission->timemodified = time();
            $submission->id = $DB->insert_record('dialoguebuilder_subs', $submission);
        } else {
            $submission->timemodified = time();
            $submission->status = 'submitted';
            $DB->update_record('dialoguebuilder_subs', $submission);
            
            // Clean old chars and lines to rewrite (simple approach).
            $DB->delete_records('dialoguebuilder_lines', ['submissionid' => $submission->id]);
            $DB->delete_records('dialoguebuilder_chars', ['submissionid' => $submission->id]);
        }

        // Save Characters.
        $char_map = []; // Map frontend temp ID to DB ID.
        if (!empty($dialoguedata->characters)) {
            foreach ($dialoguedata->characters as $char) {
                $newchar = new stdClass();
                $newchar->submissionid = $submission->id;
                $newchar->name = clean_param($char->name, PARAM_TEXT);
                $newchar->avatar_itemid = 0; // Not implemented yet
                
                $char_map[$char->id] = $DB->insert_record('dialoguebuilder_chars', $newchar);
            }
        }

        // Save Lines.
        if (!empty($dialoguedata->lines)) {
            $sortorder = 0;
            foreach ($dialoguedata->lines as $line) {
                if (!isset($char_map[$line->characterid])) {
                    continue; // Character was deleted or invalid.
                }
                
                $newline = new stdClass();
                $newline->submissionid = $submission->id;
                $newline->characterid = $char_map[$line->characterid];
                $newline->text_content = clean_param($line->text, PARAM_TEXT);
                $newline->sortorder = $sortorder++;
                
                $DB->insert_record('dialoguebuilder_lines', $newline);
            }
        }
        
        // Redirect to view.php with success message.
        redirect(
            new moodle_url('/mod/dialoguebuilder/view.php', ['id' => $cm->id]),
            'Seu roteiro de diálogo foi salvo com sucesso!',
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }
}

// Load existing data if editing.
$submission = $DB->get_record('dialoguebuilder_subs', [
    'dialoguebuilderid' => $dialoguebuilder->id,
    'userid' => $USER->id
]);

$characters = [];
$lines = [];

if ($submission) {
    $db_chars = $DB->get_records('dialoguebuilder_chars', ['submissionid' => $submission->id]);
    foreach ($db_chars as $c) {
        $characters[] = [
            'id' => $c->id,
            'name' => $c->name
        ];
    }
    
    $db_lines = $DB->get_records('dialoguebuilder_lines', ['submissionid' => $submission->id], 'sortorder ASC');
    foreach ($db_lines as $l) {
        $lines[] = [
            'characterid' => $l->characterid,
            'text' => $l->text_content
        ];
    }
}

$initialdata = json_encode([
    'characters' => $characters,
    'lines' => $lines
]);

// Prepare data for the template.
$templatedata = [
    'cmid' => $cm->id,
    'sesskey' => sesskey(),
    'initialdata' => $initialdata
];

// Require the AMD module.
$PAGE->requires->js_call_amd('mod_dialoguebuilder/editor', 'init', [$cm->id, $initialdata]);

// Output the page.
echo $OUTPUT->header();
echo $OUTPUT->heading('Editor de Diálogo');

echo $OUTPUT->render_from_template('mod_dialoguebuilder/editor', $templatedata);

echo $OUTPUT->footer();
