<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

 defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/thinklet/backup/moodle2/restore_thinklet_stepslib.php');

/**
 * Restore task for mod_thinklet.
 *
 * @package    mod_thinklet
 * @copyright  2026 Olivier Delhaye
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_thinklet_activity_task extends restore_activity_task {
    /**
     * Define activity-specific settings.
     */
    protected function define_my_settings() {
    }

    /**
     * Define activity-specific restore steps.
     */
    protected function define_my_steps() {
        $this->add_step(new restore_thinklet_activity_structure_step('thinklet_structure', 'thinklet.xml'));
    }

    /**
     * Define content decoding rules.
     *
     * @return array
     */
    public static function define_decode_contents() {
        $contents = [];
        $contents[] = new restore_decode_content('thinklet', ['intro'], 'thinklet');
        $contents[] = new restore_decode_content('thinklet_blocks', ['content', 'optionsjson'], 'thinklet_block');

        return $contents;
    }

    /**
     * Define link decoding rules.
     *
     * @return array
     */
    public static function define_decode_rules() {
        $rules = [];
        $rules[] = new restore_decode_rule('THINKLETVIEWBYID', '/mod/thinklet/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('THINKLETINDEX', '/mod/thinklet/index.php?id=$1', 'course');

        return $rules;
    }
}
