<?php
namespace mod_thinklet\completion;

defined('MOODLE_INTERNAL') || die();

/** Custom rule for explicit end-of-sequence confirmation. */
class custom_completion extends \core_completion\activity_custom_completion {
    public static function get_defined_custom_rules(): array {
        return ['completionfinish'];
    }

    public function get_state(string $rule): int {
        global $DB;
        $this->validate_rule($rule);
        return $DB->record_exists('thinklet_completed', [
            'thinkletid' => $this->cm->instance,
            'userid' => $this->userid,
        ]) ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
    }

    public function get_custom_rule_descriptions(): array {
        return ['completionfinish' => get_string('completionfinish', 'thinklet')];
    }

    public function get_sort_order(): array {
        return ['completionfinish'];
    }
}
