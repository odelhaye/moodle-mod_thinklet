<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Defines the Thinklet custom completion rule.
 *
 * @package    mod_thinklet
 * @copyright  2026 Olivier Delhaye
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
