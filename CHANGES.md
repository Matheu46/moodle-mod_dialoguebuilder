# Moodle plugin: mod_dialoguebuilder

## 0.1.2 (2026-09-05)

### Performance & Database
- Added database indexes to `dialoguebuilder`, `dialoguebuilder_subs`, `dialoguebuilder_chars`, and `dialoguebuilder_lines` tables for improved query performance.
- Optimized data preloading for submissions, characters, and lines in gallery and reports, eliminating N+1 database queries (#5).

### Security
- Added capability check (`mod/dialoguebuilder:view`) to `dialoguebuilder_pluginfile`.
- Enhanced avatar upload security by validating binary image data (`getimagesizefromstring`) and extracting true file extensions.

### Features & Usability
- Displayed submission opening date (`timeopen`) and due date (`timeclose`) in student view table.
- Added native Moodle confirmation modal before submitting dialogues for evaluation.
- Added automatic submit button state handling (disabled when script has no lines).
- Added course reset support (`dialoguebuilder_reset_userdata`) to allow deleting all submissions and resetting grades.
- Automatic cleanup of orphaned avatar files via File API upon activity instance deletion (`dialoguebuilder_delete_instance`).

### Events & Logging
- Implemented `\mod_dialoguebuilder\event\course_module_viewed` event (#4).
- Implemented `\mod_dialoguebuilder\event\submission_created` event when dialogues are first created.
- Implemented `\mod_dialoguebuilder\event\submission_updated` event when dialogues are edited or submitted.
- Implemented `\mod_dialoguebuilder\event\submission_graded` event when a teacher grades a submission.

### Fixes
- Fixed initial character ID counter starting from 2 instead of 1 when adding the first character in the editor.

## 0.1.1 (2026-08-24)
- Initial release.
