<?php
namespace mod_completiontimed\completion;

use core_completion\activity_custom_completion;

class custom_completion extends activity_custom_completion {

    public function get_state(string $rule): int {
        if ($rule === 'completionrequiredtime') {
            
            // Check our independent source of truth!
            $done = get_user_preferences('completiontimed_completed_' . $this->cm->id, 0, $this->userid);
            
            if ($done) {
                return COMPLETION_COMPLETE;
            }
            return COMPLETION_INCOMPLETE;
        }

        return COMPLETION_UNKNOWN;
    }

    public static function get_defined_custom_rules(): array {
        return ['completionrequiredtime'];
    }

    public function get_custom_rule_descriptions(): array {
        return [
            'completionrequiredtime' => get_string('completionrequiredtime_desc', 'completiontimed')
        ];
    }
    
    public function get_sort_order(): array {
        return ['completionrequiredtime'];
    }
}