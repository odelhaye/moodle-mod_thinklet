<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

 defined('MOODLE_INTERNAL') || die();

/**
 * Backup structure step for mod_thinklet.
 *
 * @package    mod_thinklet
 * @copyright  2026 Olivier Delhaye
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_thinklet_activity_structure_step extends backup_activity_structure_step {
    /**
     * Define the backup structure.
     *
     * @return backup_nested_element
     */
    protected function define_structure() {
        $thinklet = new backup_nested_element('thinklet', ['id'], [
            'course', 'name', 'intro', 'introformat', 'timecreated', 'timemodified',
        ]);

        $blocks = new backup_nested_element('blocks');
        $block = new backup_nested_element('block', ['id'], [
            'type', 'title', 'content', 'contentformat', 'optionsjson', 'sortorder',
        ]);

        $thinklet->add_child($blocks);
        $blocks->add_child($block);

        $thinklet->set_source_table('thinklet', ['id' => backup::VAR_ACTIVITYID]);
        $block->set_source_table('thinklet_blocks', ['thinkletid' => backup::VAR_PARENTID], 'sortorder ASC, id ASC');

        $thinklet->annotate_files('mod_thinklet', 'intro', null);
        $block->annotate_files('mod_thinklet', 'blockcontent', 'id');
        $block->annotate_files('mod_thinklet', 'blocklead', 'id');
        $block->annotate_files('mod_thinklet', 'blockfeedback', 'id');

        return $this->prepare_activity_structure($thinklet);
    }
}
