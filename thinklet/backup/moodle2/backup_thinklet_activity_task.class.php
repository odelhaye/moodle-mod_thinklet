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
 * Defines the Thinklet activity backup task.
 *
 * @package    mod_thinklet
 * @copyright  2026 Olivier Delhaye
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/thinklet/backup/moodle2/backup_thinklet_stepslib.php');

/**
 * Backup task for mod_thinklet.
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
