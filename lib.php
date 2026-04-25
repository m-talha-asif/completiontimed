<?php
defined('MOODLE_INTERNAL') || die();

function completiontimed_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_INTRO: return false; 
        case FEATURE_SHOW_DESCRIPTION: return false; 
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_COMPLETION_HAS_RULES: return true; 
        case FEATURE_BACKUP_MOODLE2: return true;
        default: return null;
    }
}

function completiontimed_add_instance($completiontimed, $mform = null) {
    global $DB;
    $completiontimed->timecreated = time();
    $completiontimed->timemodified = $completiontimed->timecreated;
    
    // Safe fallback to prevent "Cannot be null" database crashes without deleting user data
    if (!isset($completiontimed->intro)) {
        $completiontimed->intro = '';
        $completiontimed->introformat = FORMAT_HTML;
    }
    
    if (!isset($completiontimed->completionrequiredtime)) { $completiontimed->completionrequiredtime = 0; }
    $completiontimed->autocalculatetime = !empty($completiontimed->autocalculatetime) ? 1 : 0;

    if (!isset($completiontimed->requiredtime)) {
        $completiontimed->requiredtime = 60;
    }

    // Clean up data based on dropdown selection
    if (isset($completiontimed->videosource) && $completiontimed->videosource != 2) {
        $completiontimed->youtubeurl = '';
    }

    $completiontimed->id = $DB->insert_record('completiontimed', $completiontimed);

    // Only save files if "Local Video" is explicitly chosen
    if (isset($completiontimed->videosource) && $completiontimed->videosource == 1 && isset($completiontimed->localvideo)) {
        file_save_draft_area_files($completiontimed->localvideo, context_module::instance($completiontimed->coursemodule)->id, 'mod_completiontimed', 'video', 0, ['subdirs' => 0, 'maxfiles' => 1]);
    }

    return $completiontimed->id;
}

function completiontimed_update_instance($completiontimed, $mform = null) {
    global $DB;
    $completiontimed->timemodified = time();
    $completiontimed->id = $completiontimed->instance;
    
    // Safe fallback to prevent "Cannot be null" database crashes without deleting user data
    if (!isset($completiontimed->intro)) {
        $completiontimed->intro = '';
        $completiontimed->introformat = FORMAT_HTML;
    }
    
    if (!isset($completiontimed->completionrequiredtime)) { $completiontimed->completionrequiredtime = 0; }
    $completiontimed->autocalculatetime = !empty($completiontimed->autocalculatetime) ? 1 : 0;

    if (!isset($completiontimed->requiredtime)) {
        $existing = $DB->get_record('completiontimed', ['id' => $completiontimed->id], 'requiredtime');
        $completiontimed->requiredtime = $existing ? $existing->requiredtime : 60;
    }

    // Clean up data based on dropdown selection
    if (isset($completiontimed->videosource) && $completiontimed->videosource != 2) {
        $completiontimed->youtubeurl = '';
    }

    $DB->update_record('completiontimed', $completiontimed);

    // Save files if Local, otherwise delete any existing files from the server
    $context = context_module::instance($completiontimed->coursemodule);
    if (isset($completiontimed->videosource) && $completiontimed->videosource == 1 && isset($completiontimed->localvideo)) {
        file_save_draft_area_files($completiontimed->localvideo, $context->id, 'mod_completiontimed', 'video', 0, ['subdirs' => 0, 'maxfiles' => 1]);
    } elseif (isset($completiontimed->videosource)) {
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'mod_completiontimed', 'video', 0);
    }

    return true;
}

function completiontimed_delete_instance($id) {
    global $DB;
    if (!$completiontimed = $DB->get_record('completiontimed', ['id' => $id])) {
        return false;
    }
    $DB->delete_records('completiontimed', ['id' => $completiontimed->id]);
    return true;
}

function completiontimed_get_completion_state($course, $cm, $userid, $type) {
    $done = get_user_preferences('completiontimed_completed_' . $cm->id, 0, $userid);
    return $done ? true : false;
}

function completiontimed_get_coursemodule_info($coursemodule) {
    global $DB;
    
    $completiontimed = $DB->get_record('completiontimed', ['id' => $coursemodule->instance], 'id, name, intro, introformat, completionrequiredtime');
    if (!$completiontimed) { return null; }
    
    $info = new cached_cm_info();
    $info->name = $completiontimed->name;
    
    if ($coursemodule->completion == COMPLETION_TRACKING_AUTOMATIC) {
        $rules = [];
        if (!empty($completiontimed->completionrequiredtime)) {
            $rules['completionrequiredtime'] = $completiontimed->completionrequiredtime;
        }
        if (!empty($rules)) {
            $info->customdata = ['customcompletionrules' => $rules];
        }
    }
    return $info;
}

function mod_completiontimed_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    if ($context->contextlevel != CONTEXT_MODULE) { return false; }
    require_login($course, true, $cm);
    if ($filearea !== 'video') { return false; }
    
    $itemid = array_shift($args);
    
    $fs = get_file_storage();
    $filename = array_pop($args);
    $filepath = $args ? '/'.implode('/', $args).'/' : '/';
    $file = $fs->get_file($context->id, 'mod_completiontimed', $filearea, $itemid, $filepath, $filename);
    
    if (!$file || $file->is_directory()) { return false; }
    send_stored_file($file, 0, 0, $forcedownload, $options);
}

/**
 * Extends the settings navigation to add the Results tab.
 */
function completiontimed_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $completiontimednode) {
    global $PAGE;
    
    // Only show the Results tab to teachers/managers
    if (has_capability('mod/completiontimed:addinstance', $PAGE->context)) {
        $url = new moodle_url('/mod/completiontimed/report.php', ['id' => $PAGE->cm->id]);
        $node = navigation_node::create(get_string('results', 'mod_completiontimed'), $url, navigation_node::TYPE_SETTING, null, 'results');
        $completiontimednode->add_node($node);
    }
}