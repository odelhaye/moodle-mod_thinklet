<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace mod_thinklet\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event raised when a learner views a Thinklet.
 *
 * @package mod_thinklet
 */
class course_module_viewed extends \core\event\course_module_viewed {
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'thinklet';
    }

    public static function get_objectid_mapping() {
        return ['db' => 'thinklet', 'restore' => 'thinklet'];
    }
}
