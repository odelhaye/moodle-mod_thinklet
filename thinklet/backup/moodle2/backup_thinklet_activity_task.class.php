<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

 defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/thinklet/backup/moodle2/backup_thinklet_stepslib.php');

/**
 * Backup task for mod_thinklet.
 *
 * @package    mod_thinklet
 * @copyright  2026 Olivier Delhaye
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_thinklet_activity_task extends backup_activity_task {
    /**
     * Define activity-specific settings.
     */
    protected function define_my_settings() {
    }

    /**
     * Define activity-specific backup steps.
     */
    protected function define_my_steps() {
        $this->add_step(new backup_thinklet_activity_structure_step('thinklet_structure', 'thinklet.xml'));
    }

    /**
     * Encode content links.
     *
     * @param string $content Content to encode.
     * @return string
     */
    public static function encode_content_links($content) {
        return $content;
    }
}
