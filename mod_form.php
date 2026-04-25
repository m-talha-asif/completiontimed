<?php
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/course/moodleform_mod.php');

class mod_completiontimed_mod_form extends moodleform_mod {
    function definition() {
        global $CFG;
        $mform = $this->_form;

        // General section.
        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('completiontimedname', 'mod_completiontimed'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        
        // MEDIA SECTION
        $mform->addElement('header', 'video_settings', get_string('videosettings', 'mod_completiontimed'));
        $options = [
            0 => get_string('videosource_none', 'mod_completiontimed'),
            1 => get_string('videosource_local', 'mod_completiontimed'),
            2 => get_string('videosource_youtube', 'mod_completiontimed')
        ];
        $mform->addElement('select', 'videosource', get_string('videosource', 'mod_completiontimed'), $options);
        $mform->setDefault('videosource', 0);

        $mform->addElement('filemanager', 'localvideo', get_string('localvideo', 'mod_completiontimed'), null,
            ['subdirs' => 0, 'maxbytes' => $CFG->maxbytes, 'maxfiles' => 1, 'accepted_types' => ['.mp4', '.webm']]
        );
        $mform->addHelpButton('localvideo', 'localvideo', 'mod_completiontimed');
        $mform->hideIf('localvideo', 'videosource', 'neq', 1);

        $mform->addElement('text', 'youtubeurl', get_string('youtubeurl', 'mod_completiontimed'), ['size' => '64']);
        $mform->setType('youtubeurl', PARAM_URL);
        $mform->addHelpButton('youtubeurl', 'youtubeurl', 'mod_completiontimed');
        $mform->hideIf('youtubeurl', 'videosource', 'neq', 2);

        // MCQ SETTINGS SECTION
        $mform->addElement('header', 'mcq_settings', get_string('mcqsettings', 'mod_completiontimed'));
        
        $mform->addElement('text', 'mcqtime', get_string('mcqtime', 'mod_completiontimed'));
        $mform->setType('mcqtime', PARAM_INT);
        $mform->setDefault('mcqtime', 0);
        $mform->addHelpButton('mcqtime', 'mcqtime', 'mod_completiontimed');

        $mform->addElement('textarea', 'mcqquestion', get_string('mcqquestion', 'mod_completiontimed'), 'wrap="virtual" rows="3" cols="50"');
        $mform->setType('mcqquestion', PARAM_TEXT);

        $mform->addElement('text', 'mcqoptiona', get_string('mcqoptiona', 'mod_completiontimed'), ['size' => '64']);
        $mform->setType('mcqoptiona', PARAM_TEXT);
        
        $mform->addElement('text', 'mcqoptionb', get_string('mcqoptionb', 'mod_completiontimed'), ['size' => '64']);
        $mform->setType('mcqoptionb', PARAM_TEXT);
        
        $mform->addElement('text', 'mcqoptionc', get_string('mcqoptionc', 'mod_completiontimed'), ['size' => '64']);
        $mform->setType('mcqoptionc', PARAM_TEXT);
        
        $mform->addElement('text', 'mcqoptiond', get_string('mcqoptiond', 'mod_completiontimed'), ['size' => '64']);
        $mform->setType('mcqoptiond', PARAM_TEXT);

        // Add Correct Answer Dropdown
        $correctoptions = [
            0 => get_string('mcqoptiona', 'mod_completiontimed'),
            1 => get_string('mcqoptionb', 'mod_completiontimed'),
            2 => get_string('mcqoptionc', 'mod_completiontimed'),
            3 => get_string('mcqoptiond', 'mod_completiontimed')
        ];
        $mform->addElement('select', 'mcqcorrect', get_string('mcqcorrect', 'mod_completiontimed'), $correctoptions);
        $mform->setDefault('mcqcorrect', 0);

        // SETTINGS SECTION
        $mform->addElement('header', 'settings', get_string('settings'));
        $mform->addElement('checkbox', 'autocalculatetime', get_string('autocalculatetime', 'mod_completiontimed'));
        $mform->addHelpButton('autocalculatetime', 'autocalculatetime', 'mod_completiontimed');
        $mform->setType('autocalculatetime', PARAM_BOOL);
        $mform->setDefault('autocalculatetime', 0); 

        $mform->addElement('text', 'requiredtime', get_string('requiredtime', 'mod_completiontimed'));
        $mform->setType('requiredtime', PARAM_INT);
        $mform->setDefault('requiredtime', 60);
        $mform->disabledIf('requiredtime', 'autocalculatetime', 'checked');

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    public function data_preprocessing(&$default_values) {
        if ($this->current->instance) {
            $default_values['videosource'] = 0;
            if (!empty($default_values['youtubeurl'])) {
                $default_values['videosource'] = 2;
            }

            $draftitemid = file_get_submitted_draft_itemid('localvideo');
            file_prepare_draft_area($draftitemid, $this->context->id, 'mod_completiontimed', 'video', 0, ['subdirs' => 0, 'maxfiles' => 1]);
            $default_values['localvideo'] = $draftitemid;

            $fs = get_file_storage();
            $files = $fs->get_area_files($this->context->id, 'mod_completiontimed', 'video', 0, 'id', false);
            if (count($files) > 0) {
                $default_values['videosource'] = 1;
            }
        }
    }

    public function add_completion_rules() {
        $mform = $this->_form;
        $mform->addElement('checkbox', 'completionrequiredtime', get_string('completionrequiredtime', 'completiontimed'), get_string('completionrequiredtime_text', 'completiontimed'));
        $mform->disabledIf('completionview', 'completionrequiredtime', 'checked');
        return ['completionrequiredtime'];
    }

    public function completion_rule_enabled($data) {
        return !empty($data['completionrequiredtime']);
    }
}