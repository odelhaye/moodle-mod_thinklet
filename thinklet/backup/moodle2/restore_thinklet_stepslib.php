<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

 defined('MOODLE_INTERNAL') || die();

/**
 * Restore structure step for mod_thinklet.
 *
 * @package    mod_thinklet
 * @copyright  2026 Olivier Delhaye
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_thinklet_activity_structure_step extends restore_activity_structure_step {
    /**
     * Define structure paths.
     *
     * @return array
     */
    protected function define_structure() {
        $paths = [];
        $paths[] = new restore_path_element('thinklet', '/activity/thinklet');
        $paths[] = new restore_path_element('thinklet_block', '/activity/thinklet/blocks/block');

        return $this->prepare_activity_structure($paths);
    }

    /**
     * Restore the Thinklet instance.
     *
     * @param array $data Restored data.
     */
    protected function process_thinklet($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        unset($data->id);
        $data->course = $this->get_courseid();

        $newitemid = $DB->insert_record('thinklet', $data);
        $this->apply_activity_instance($newitemid);
        $this->set_mapping('thinklet', $oldid, $newitemid, true);
    }

    /**
     * Restore a Thinklet block.
     *
     * @param array $data Restored data.
     */
    protected function process_thinklet_block($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        unset($data->id);
        $data->thinkletid = $this->get_new_parentid('thinklet');

        $newitemid = $DB->insert_record('thinklet_blocks', $data);
        $this->set_mapping('thinklet_block', $oldid, $newitemid, true);
    }

    /**
     * Restore files after data has been inserted.
     */
    protected function after_execute() {
        $this->add_related_files('mod_thinklet', 'intro', null);
        $this->add_related_files('mod_thinklet', 'blockcontent', 'thinklet_block');
        $this->add_related_files('mod_thinklet', 'blocklead', 'thinklet_block');
        $this->add_related_files('mod_thinklet', 'blockfeedback', 'thinklet_block');
    }
}
