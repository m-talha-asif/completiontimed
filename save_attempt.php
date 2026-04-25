<?php
require(__DIR__ . '/../../config.php');

$cmid = required_param('cmid', PARAM_INT);
$answer = required_param('answer', PARAM_INT);

$cm = get_coursemodule_from_id('completiontimed', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$completiontimed = $DB->get_record('completiontimed', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, false, $cm);
require_sesskey();

global $USER, $DB;

$iscorrect = ($answer == $completiontimed->mcqcorrect) ? 1 : 0;

// Save attempt
$attempt = new stdClass();
$attempt->completiontimedid = $completiontimed->id;
$attempt->userid = $USER->id;
$attempt->answer = $answer;
$attempt->correct = $iscorrect;
$attempt->timecreated = time();

$DB->insert_record('completiontimed_attempts', $attempt);

echo json_encode(['status' => 'success', 'correct' => (bool)$iscorrect]);