<?php
require(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('completiontimed', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$completiontimed = $DB->get_record('completiontimed', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/completiontimed:addinstance', $context);

$PAGE->set_url('/mod/completiontimed/report.php', ['id' => $cm->id]);
$PAGE->set_title(get_string('results', 'mod_completiontimed'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('results', 'mod_completiontimed'));

// Map answers back to the actual text strings, adding the Option Letter for clarity
$optionsmap = [
    0 => 'Option A: ' . $completiontimed->mcqoptiona,
    1 => 'Option B: ' . $completiontimed->mcqoptionb,
    2 => 'Option C: ' . $completiontimed->mcqoptionc,
    3 => 'Option D: ' . $completiontimed->mcqoptiond
];

// Fetch all attempts joined with user data (Now fetching strict Moodle name fields to clear the debug warning)
$sql = "SELECT a.*, u.firstname, u.lastname, u.email, u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename
        FROM {completiontimed_attempts} a 
        JOIN {user} u ON a.userid = u.id 
        WHERE a.completiontimedid = ? 
        ORDER BY a.timecreated DESC";
$attempts = $DB->get_records_sql($sql, [$completiontimed->id]);

if (empty($attempts)) {
    echo $OUTPUT->notification(get_string('noattempts', 'mod_completiontimed'), 'info');
} else {
    $table = new html_table();
    $table->head = [
        get_string('resultstable_user', 'mod_completiontimed'),
        get_string('resultstable_answer', 'mod_completiontimed'),
        get_string('resultstable_status', 'mod_completiontimed'),
        get_string('resultstable_time', 'mod_completiontimed')
    ];

    foreach ($attempts as $a) {
        // Safely build the user object with all required fields for Moodle's fullname() function
        $userobj = (object)[
            'firstname' => $a->firstname, 
            'lastname' => $a->lastname,
            'firstnamephonetic' => $a->firstnamephonetic,
            'lastnamephonetic' => $a->lastnamephonetic,
            'middlename' => $a->middlename,
            'alternatename' => $a->alternatename
        ];
        $fullname = fullname($userobj);
        
        $answertext = isset($optionsmap[$a->answer]) ? $optionsmap[$a->answer] : 'Unknown';
        
        $status = $a->correct ? '<span class="badge badge-success bg-success text-white">'.get_string('correct', 'mod_completiontimed').'</span>' 
                              : '<span class="badge badge-danger bg-danger text-white">'.get_string('incorrect', 'mod_completiontimed').'</span>';

        $table->data[] = [
            $fullname,
            s($answertext),
            $status,
            userdate($a->timecreated)
        ];
    }
    
    echo html_writer::table($table);
}

echo $OUTPUT->footer();