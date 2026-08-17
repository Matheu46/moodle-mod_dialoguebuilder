<?php
/**
 * Report page for teachers to view submissions.
 *
 * @package    mod_dialoguebuilder
 * @copyright  2026 Matheus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

$id = required_param('id', PARAM_INT);
$action = optional_param('action', 'list', PARAM_ALPHA);
$subid = optional_param('subid', 0, PARAM_INT);

$cm = get_coursemodule_from_id('dialoguebuilder', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$dialoguebuilder = $DB->get_record('dialoguebuilder', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

// Teacher capability check
require_capability('moodle/course:manageactivities', $context); // Use viewall capability in the future if we add it

$PAGE->set_url(new moodle_url('/mod/dialoguebuilder/report.php', ['id' => $cm->id]));
$PAGE->set_context($context);

if ($action === 'grade' && data_submitted() && confirm_sesskey()) {
    $grade = optional_param('grade', null, PARAM_FLOAT);
    $feedback = optional_param('feedback', '', PARAM_RAW);
    $submission = $DB->get_record('dialoguebuilder_subs', ['id' => $subid, 'dialoguebuilderid' => $dialoguebuilder->id], '*', MUST_EXIST);
    
    $submission->grade = $grade;
    $submission->feedback = $feedback;
    $submission->timemodified = time();
    $DB->update_record('dialoguebuilder_subs', $submission);
    
    dialoguebuilder_update_grades($dialoguebuilder, $submission->userid);
    
    redirect(
        new moodle_url('/mod/dialoguebuilder/report.php', ['id' => $cm->id]), 
        get_string('gradesaved', 'mod_dialoguebuilder'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

if ($action === 'view' && $subid) {
    // View a specific submission.
    $submission = $DB->get_record('dialoguebuilder_subs', ['id' => $subid, 'dialoguebuilderid' => $dialoguebuilder->id], '*', MUST_EXIST);
    $user = $DB->get_record('user', ['id' => $submission->userid], '*', MUST_EXIST);
    
    $PAGE->set_title(get_string('viewdialogue', 'mod_dialoguebuilder'));
    $PAGE->set_heading(fullname($user));
    
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('dialoguefor', 'mod_dialoguebuilder', fullname($user)));
    
    // Back button.
    $backurl = new moodle_url('/mod/dialoguebuilder/report.php', ['id' => $cm->id]);
    echo html_writer::link($backurl, get_string('backtosubmissions', 'mod_dialoguebuilder'), ['class' => 'btn btn-secondary mb-3']);
    
    // Fetch characters.
    $characters = $DB->get_records('dialoguebuilder_chars', ['submissionid' => $submission->id], 'id ASC');
    $char_map = [];
    $first_char_id = null;
    foreach ($characters as $char) {
        $char_map[$char->id] = $char->name;
        if ($first_char_id === null) {
            $first_char_id = $char->id;
        }
    }
    
    // Fetch lines.
    $lines = $DB->get_records('dialoguebuilder_lines', ['submissionid' => $submission->id], 'sortorder ASC');
    
    if (empty($lines)) {
        echo $OUTPUT->notification("Nenhum diálogo encontrado para esta submissão.", 'info');
    } else {
        $templatedata = ['lines' => []];
        foreach ($lines as $line) {
            $templatedata['lines'][] = [
                'charname' => isset($char_map[$line->characterid]) ? $char_map[$line->characterid] : 'Desconhecido',
                'text' => format_text($line->text_content, FORMAT_MOODLE),
                'is_self' => ($line->characterid == $first_char_id)
            ];
        }
        echo $OUTPUT->render_from_template('mod_dialoguebuilder/chat_view', $templatedata);
    }
    
    // Grading Form
    if ($dialoguebuilder->grade > 0) {
        echo $OUTPUT->box_start('generalbox mt-4 p-4', 'grading-box', ['style' => 'background: #f8f9fa; border-radius: 8px; border: 1px solid #ddd; max-width: 600px; margin: 0 auto;']);
        echo $OUTPUT->heading(get_string('grade', 'mod_dialoguebuilder'), 3);
        
        $gradeurl = new moodle_url('/mod/dialoguebuilder/report.php');
        echo html_writer::start_tag('form', ['action' => $gradeurl, 'method' => 'post']);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $cm->id]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'grade']);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'subid', 'value' => $subid]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        
        echo html_writer::start_tag('div', ['class' => 'form-group mb-3']);
        echo html_writer::label(get_string('grade', 'mod_dialoguebuilder') . ' (0 - ' . $dialoguebuilder->grade . '):', 'gradeinput', false, ['class' => 'd-block font-weight-bold']);
        echo html_writer::empty_tag('input', [
            'type' => 'number', 
            'name' => 'grade', 
            'id' => 'gradeinput', 
            'class' => 'form-control', 
            'style' => 'max-width: 150px;',
            'min' => '0', 
            'max' => $dialoguebuilder->grade, 
            'step' => '0.1', 
            'value' => isset($submission->grade) ? $submission->grade : ''
        ]);
        echo html_writer::end_tag('div');
        
        echo html_writer::start_tag('div', ['class' => 'form-group mb-3']);
        echo html_writer::label(get_string('feedback', 'mod_dialoguebuilder') . ':', 'feedbackinput', false, ['class' => 'd-block font-weight-bold']);
        echo html_writer::tag('textarea', s(isset($submission->feedback) ? $submission->feedback : ''), ['name' => 'feedback', 'id' => 'feedbackinput', 'class' => 'form-control w-100', 'rows' => '4']);
        echo html_writer::end_tag('div');
        
        echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('savechanges'), 'class' => 'btn btn-primary']);
        
        echo html_writer::end_tag('form');
        echo $OUTPUT->box_end();
    }
    
} else {
    // List all submissions.
    $PAGE->set_title(get_string('submissions', 'mod_dialoguebuilder'));
    $PAGE->set_heading(format_string($course->fullname));
    
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('submissions', 'mod_dialoguebuilder'));
    
    $sql = "SELECT ds.* 
            FROM {dialoguebuilder_subs} ds
            WHERE ds.dialoguebuilderid = :dbid
            ORDER BY ds.timecreated DESC";
    
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
            get_string('actions', 'mod_dialoguebuilder')
        ];
        $table->data = [];
        
        foreach ($submissions as $sub) {
            $user = $DB->get_record('user', ['id' => $sub->userid], '*', MUST_EXIST);
            $fullname = fullname($user);
            
            $charcount = $DB->count_records('dialoguebuilder_chars', ['submissionid' => $sub->id]);
            $linecount = $DB->count_records('dialoguebuilder_lines', ['submissionid' => $sub->id]);
            
            $viewurl = new moodle_url('/mod/dialoguebuilder/report.php', ['id' => $cm->id, 'action' => 'view', 'subid' => $sub->id]);
            $viewlink = html_writer::link($viewurl, get_string('viewdialogue', 'mod_dialoguebuilder'), ['class' => 'btn btn-primary btn-sm']);
            
            $grade_display = isset($sub->grade) ? format_float($sub->grade, 1) : '-';
            
            $table->data[] = [
                $fullname,
                $sub->status,
                $charcount,
                $linecount,
                $grade_display,
                userdate($sub->timecreated),
                $viewlink
            ];
        }
        
        echo html_writer::table($table);
    }
}

echo $OUTPUT->footer();
