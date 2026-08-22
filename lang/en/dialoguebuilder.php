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
 * English strings for dialoguebuilder.
 *
 * @package    mod_dialoguebuilder
 * @copyright  2026 Matheus Mathias
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'Dialogue Builder';
$string['modulename_help'] = 'The Dialogue Builder activity allows teachers to define guidelines for a dialogue (e.g., a historical debate) and students to submit a script of lines between characters.';
$string['modulenameplural'] = 'Dialogue Builders';
$string['pluginname'] = 'Dialogue Builder';
$string['pluginadministration'] = 'Dialogue Builder administration';

$string['name'] = 'Dialogue name';

$string['dialoguebuilder:addinstance'] = 'Add a new Dialogue Builder activity';
$string['dialoguebuilder:view'] = 'View Dialogue Builder';
$string['dialoguebuilder:submit'] = 'Submit a dialogue script';
$string['dialoguebuilder:grade'] = 'Grade dialogues';

$string['viewsubmissions'] = 'View submissions';
$string['submissions'] = 'Submissions';
$string['student'] = 'Student';
$string['status'] = 'Status';
$string['timecreated'] = 'Time created';
$string['timemodified'] = 'Last modified';
$string['actions'] = 'Actions';
$string['viewdialogue'] = 'View dialogue';
$string['nosubmissions'] = 'No submissions yet.';
$string['dialoguefor'] = 'Dialogue for {$a}';
$string['backtosubmissions'] = 'Back to submissions';
$string['lines'] = 'Lines';
$string['characters'] = 'Characters';
$string['grade'] = 'Grade';
$string['gradesaved'] = 'Grade saved successfully';
$string['feedback'] = 'Feedback';
$string['viewgallery'] = 'View Gallery';
$string['gallerysettings'] = 'Gallery settings';
$string['gallerymode'] = 'Gallery access mode';
$string['gallerymode_help'] = 'Determines when and if students can view other students\' submitted dialogues.';
$string['gallerymode_disabled'] = 'Disabled (Gallery off)';
$string['gallerymode_free'] = 'Free access (Any time)';
$string['gallerymode_postfirst'] = 'Post before viewing (Must submit first)';
$string['gallerymode_afterclose'] = 'Visible only after deadline';
$string['availability'] = 'Availability';
$string['timeopen'] = 'Allow submissions from';
$string['timeclose'] = 'Due date';
$string['notopenyet'] = 'This activity is not open for submissions yet. It will open on {$a}.';
$string['submissionsclosed'] = 'Submissions for this activity closed on {$a}.';

$string['addcharacter'] = 'Add character';
$string['dialoguescript'] = 'Dialogue script';
$string['addline'] = 'Add line';
$string['emptyfieldwarning'] = 'There are empty fields in your script. Please fill them out before saving.';
$string['emptylinesmsg'] = 'Add characters first to be able to create lines for the script.';
$string['savescript'] = 'Save script';
$string['savedraft'] = 'Save draft';
$string['submittask'] = 'Submit task';
$string['status_draft'] = 'Draft';
$string['status_submitted'] = 'Submitted';
$string['tasksubmitted'] = 'Your task has been successfully submitted!';
$string['draftsaved'] = 'Your draft has been successfully saved!';
$string['changeavatar'] = 'Click to change picture';
$string['characternameplaceholder'] = 'Character name...';
$string['unnamed'] = 'Unnamed';
$string['writelineplaceholder'] = 'Write the line here...';
$string['character'] = 'Character';

$string['play'] = 'Play';
$string['pause'] = 'Pause';
$string['replay'] = 'Replay';
$string['saveandnext'] = 'Save and show next';
$string['next'] = 'Next';
$string['previous'] = 'Previous';

$string['teachersummary'] = 'Teacher summary: {$a} students have submitted dialogues so far.';
$string['studentguidelines'] = 'Read the teacher\'s guidelines above and create your script.';
$string['unknown'] = 'Unknown';
$string['yourdialogue'] = 'Your dialogue';
$string['editdialogue'] = 'Edit dialogue';
$string['startdialogue'] = 'Start dialogue';

$string['gradingsummary'] = 'Grading summary';
$string['hiddenfromstudents'] = 'Hidden from students';
$string['participants'] = 'Participants';
$string['submitted'] = 'Submitted';
$string['needsgrading'] = 'Needs grading';
$string['timeremaining'] = 'Time remaining';
$string['assignmentisdue'] = 'Assignment is due';
$string['nodialoguefound'] = 'No dialogue found for this submission.';
$string['nolinesrecorded'] = 'No lines recorded yet.';
$string['downloadasimage'] = 'Download as image';

$string['privacy:metadata:dialoguebuilder_subs'] = 'Information about student submissions in the Dialogue Builder activity.';
$string['privacy:metadata:dialoguebuilder_subs:dialoguebuilderid'] = 'The ID of the Dialogue Builder activity.';
$string['privacy:metadata:dialoguebuilder_subs:userid'] = 'The ID of the user who made the submission.';
$string['privacy:metadata:dialoguebuilder_subs:status'] = 'The status of the submission (e.g., draft or submitted).';
$string['privacy:metadata:dialoguebuilder_subs:timecreated'] = 'The time when the submission was first created.';
$string['privacy:metadata:dialoguebuilder_subs:grade'] = 'The grade assigned to the submission by the teacher.';
$string['privacy:metadata:dialoguebuilder_subs:feedback'] = 'The feedback provided by the teacher.';
$string['privacy:metadata:dialoguebuilder_subs:timemodified'] = 'The time when the submission was last modified.';

$string['privacy:metadata:dialoguebuilder_chars'] = 'Information about the characters created within a submission.';
$string['privacy:metadata:dialoguebuilder_chars:submissionid'] = 'The ID of the submission to which the character belongs.';
$string['privacy:metadata:dialoguebuilder_chars:name'] = 'The name of the character.';

$string['privacy:metadata:dialoguebuilder_lines'] = 'Information about the dialogue lines created within a submission.';
$string['privacy:metadata:dialoguebuilder_lines:submissionid'] = 'The ID of the submission to which the line belongs.';
$string['privacy:metadata:dialoguebuilder_lines:characterid'] = 'The ID of the character speaking the line.';
$string['privacy:metadata:dialoguebuilder_lines:text_content'] = 'The actual text content of the dialogue line.';
