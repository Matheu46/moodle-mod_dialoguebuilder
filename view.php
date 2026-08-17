<?php
/**
 * Prints a particular instance of dialoguebuilder.
 *
 * @package    mod_dialoguebuilder
 * @copyright  2026 Matheus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

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
    
    echo $OUTPUT->box("Resumo para Professores: $submissioncount alunos submeteram diálogos até o momento.", 'generalbox teacher-view-box');
    
    $reporturl = new moodle_url('/mod/dialoguebuilder/report.php', ['id' => $cm->id]);
    echo $OUTPUT->single_button($reporturl, get_string('viewsubmissions', 'mod_dialoguebuilder'), 'get', ['primary' => true]);
} else {
    // Student view.
    echo $OUTPUT->box('Leia as diretrizes do professor acima e crie o seu roteiro de falas.', 'generalbox student-view-box');
    
    // Render a button to start/edit the dialogue.
    $url = new moodle_url('/mod/dialoguebuilder/edit.php', ['id' => $cm->id]);
    echo $OUTPUT->single_button($url, 'Iniciar/Editar Diálogo', 'get', ['primary' => true]);
}

// Finish the page.
echo $OUTPUT->footer();
