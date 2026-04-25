<?php
require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once($CFG->libdir . '/completionlib.php'); 

global $USER;

$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('completiontimed', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$completiontimed = $DB->get_record('completiontimed', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

$PAGE->set_url('/mod/completiontimed/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($completiontimed->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->activityheader->set_description('');

echo $OUTPUT->header();

$completion = new completion_info($course);
$completiondata = $completion->get_data($cm, false, $USER->id);

$fs = get_file_storage();
$files = $fs->get_area_files($context->id, 'mod_completiontimed', 'video', 0, 'itemid, filepath, filename', false);
$localvideourl = '';
if (!empty($files)) {
    $file = reset($files);
    $localvideourl = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename())->out();
}

$hasvideo = ($localvideourl || !empty($completiontimed->youtubeurl)) ? true : false;
$autocalc = !empty($completiontimed->autocalculatetime) ? true : false;

// Gather MCQ Data to pass to JS
$mcqdata = [
    'enabled' => ($completiontimed->mcqtime > 0 && !empty($completiontimed->mcqquestion)),
    'time' => (int)$completiontimed->mcqtime
];

// === RENDER TIMER UI ===
if ($completiondata->completionstate == COMPLETION_COMPLETE) {
    echo '<div class="alert alert-success mt-3 mb-4" style="font-size: 1.1em; padding: 15px 20px;">';
    echo 'You have successfully completed the time requirement for this activity.';
    echo '</div>';
} else {
    echo '<div class="alert alert-info mt-3 mb-4 shadow-sm" id="timer-status" style="font-size: 1.1em; display: flex; align-items: center; justify-content: space-between; padding: 15px 20px;">';
    
    if ($autocalc && $hasvideo) {
        echo '<span>You must view this page for the full duration of the video.</span>';
        echo '<span id="time-left" class="badge badge-primary bg-primary text-white" style="font-size: 1.1em; padding: 8px 16px; border-radius: 20px; font-weight: 500;">calculating...</span>';
    } else {
        $init_mins = str_pad(floor($completiontimed->requiredtime / 60), 2, '0', STR_PAD_LEFT);
        $init_secs = str_pad($completiontimed->requiredtime % 60, 2, '0', STR_PAD_LEFT);
        echo '<span>You must view this page for the required time to complete it.</span>';
        echo '<span id="time-left" class="badge badge-primary bg-primary text-white" style="font-size: 1.1em; padding: 8px 16px; border-radius: 20px; font-weight: 500;">' . $init_mins . 'min ' . $init_secs . 'sec</span>';
    }
    
    echo '</div>';
    $PAGE->requires->js_call_amd('mod_completiontimed/timer', 'init', [$cm->id, $completiontimed->requiredtime, $USER->id, $hasvideo, $autocalc]);
}

// === RENDER VIDEO WRAPPER & MCQ MODAL ===
if ($hasvideo) {
    echo '<div class="text-center mb-4 mt-4">';
    echo '<div id="video-container" style="position: relative; max-width: 800px; margin: 0 auto; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.15); background: #000;">';

    // The hidden MCQ Popup Overlay
    if ($mcqdata['enabled']) {
        echo '<div id="mcq-overlay" style="display: none; position: absolute; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.85); z-index: 10; align-items: center; justify-content: center; padding: 20px;">';
        echo '<div style="background: #fff; padding: 30px; border-radius: 8px; max-width: 500px; width: 100%; text-align: left;">';
        echo '<h4 style="margin-top:0;">Attention Check</h4>';
        echo '<p style="font-size: 1.1em;">' . s($completiontimed->mcqquestion) . '</p>';
        echo '<div id="mcq-options" style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px;">';
        
        $options = [$completiontimed->mcqoptiona, $completiontimed->mcqoptionb, $completiontimed->mcqoptionc, $completiontimed->mcqoptiond];
        foreach ($options as $i => $opt) {
            if (!empty($opt)) {
                echo '<label style="background: #f8f9fa; padding: 10px; border: 1px solid #ddd; border-radius: 4px; cursor: pointer;">';
                echo '<input type="radio" name="mcq_answer" value="'.$i.'" style="margin-right: 10px;">' . s($opt);
                echo '</label>';
            }
        }
        
        echo '</div>';
        echo '<div id="mcq-feedback-container" class="mt-3 text-center" style="display:none; font-weight:bold; font-size:1.2em;"></div>';
        echo '<button id="btn-mcq-submit" class="btn btn-primary w-100 mt-3" disabled>' . get_string('mcqresume', 'mod_completiontimed') . '</button>';
        echo '</div></div>';
    }

    if ($localvideourl) {
        // Local HTML5 Video
        $storageKey = 'completiontimed_vid_progress_cm_' . $cm->id . '_user_' . $USER->id;
        echo '<div style="position: relative; width: 100%; pointer-events: none;">';
        echo '<video id="custom-html5-player" src="' . $localvideourl . '" playsinline style="width: 100%; display: block;"></video>';
        echo '</div>';
        echo '<div id="custom-controls" style="background: #f8f9fa; padding: 15px; border-top: 1px solid #ddd; display: flex; justify-content: center; gap: 15px;">';
        echo '<button id="btn-vid-play" class="btn btn-primary" style="min-width: 100px;">▶ Play</button>';
        echo '<button id="btn-vid-pause" class="btn btn-secondary" style="min-width: 100px;">⏸ Pause</button>';
        echo '</div>';
        
        $PAGE->requires->js_call_amd('mod_completiontimed/player', 'initLocal', [$cm->id, $storageKey, $mcqdata]);

    } elseif (!empty($completiontimed->youtubeurl)) {
        // YouTube Video
        preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/\s]{11})%i', $completiontimed->youtubeurl, $match);
        $youtubeid = $match[1] ?? '';

        if ($youtubeid) {
            $storageKey = 'completiontimed_yt_progress_cm_' . $cm->id . '_user_' . $USER->id;
            echo '<div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; pointer-events: none;">';
            echo '<div id="custom-yt-player" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;"></div>';
            echo '</div>';
            echo '<div id="custom-controls" style="background: #f8f9fa; padding: 15px; border-top: 1px solid #ddd; display: flex; justify-content: center; gap: 15px;">';
            echo '<button id="btn-yt-play" class="btn btn-primary" style="min-width: 100px;">▶ Play</button>';
            echo '<button id="btn-yt-pause" class="btn btn-secondary" style="min-width: 100px;">⏸ Pause</button>';
            echo '</div>';

            $PAGE->requires->js_call_amd('mod_completiontimed/player', 'initYouTube', [$cm->id, $youtubeid, $storageKey, $mcqdata]);
        }
    }
    
    echo '</div></div>';
}

echo $OUTPUT->footer();