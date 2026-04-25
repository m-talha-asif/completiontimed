<?php
require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/completionlib.php');

$cmid = required_param('cmid', PARAM_INT);
$cm = get_coursemodule_from_id('completiontimed', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

require_login($course, false, $cm);
require_sesskey();

global $USER;

// 1. Save the independent "Source of Truth" to the user's Moodle profile
set_user_preference('completiontimed_completed_' . $cm->id, 1, $USER->id);

$completion = new completion_info($course);
if ($completion->is_enabled($cm)) {
    
    // Satisfy standard view rule (if checked)
    $completion->set_module_viewed($cm); 
    
    // Trigger Moodle to recalculate our custom rules!
    $completion->update_state($cm, COMPLETION_COMPLETE); 
    
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'disabled']);
}