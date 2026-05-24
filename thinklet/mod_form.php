<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

require_once($CFG->dirroot.'/course/moodleform_mod.php');

class mod_thinklet_mod_form extends moodleform_mod {

    function definition() {

        $mform = $this->_form;

        $mform->addElement('text', 'name', get_string('activityname', 'thinklet'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $this->standard_intro_elements();

        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
    }
}