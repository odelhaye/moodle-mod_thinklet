<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace mod_thinklet\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class editblock_form extends \moodleform {

    public function definition() {
        $mform = $this->_form;
        $type = $this->_customdata['type'];
        $blockid = $this->_customdata['blockid'] ?? 0;
        $id = $this->_customdata['id'];
        $editoroptions = $this->_customdata['editoroptions'];

        $mform->addElement('hidden', 'id', $id);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'type', $type);
        $mform->setType('type', PARAM_ALPHANUMEXT);

        $mform->addElement('hidden', 'blockid', $blockid);
        $mform->setType('blockid', PARAM_INT);

        $mform->addElement('text', 'title', get_string('blocktitle', 'thinklet'), ['size' => 80]);
        $mform->setType('title', PARAM_TEXT);

        if ($type === 'reveal') {
            $mform->addElement(
                'editor',
                'leadtext_editor',
                get_string('leadtext', 'thinklet'),
                null,
                $editoroptions
            );
            $mform->setType('leadtext_editor', PARAM_RAW);
        }

        if ($type === 'qcm') {
            $mform->addElement(
                'textarea',
                'choices',
                get_string('choices', 'thinklet'),
                ['rows' => 6, 'cols' => 80]
            );
            $mform->setType('choices', PARAM_RAW);
        }

        if ($type === 'openquestion') {
            $mform->addElement(
                'text',
                'threshold',
                get_string('threshold', 'thinklet'),
                ['size' => 8]
            );
            $mform->setType('threshold', PARAM_INT);
            $mform->setDefault('threshold', 120);
        }

        if (in_array($type, ['qcm', 'openquestion', 'reveal'], true)) {
            $mform->addElement('text', 'buttontext', get_string('buttontext', 'thinklet'), ['size' => 80]);
            $mform->setType('buttontext', PARAM_TEXT);
        }

        $contentlabel = match ($type) {
            'stimulus' => get_string('contentstimulus', 'thinklet'),
            'qcm' => get_string('contentqcm', 'thinklet'),
                'roc' => get_string('contentroc', 'thinklet'),
            'openquestion' => get_string('contentopenquestion', 'thinklet'),
            'transition' => get_string('contenttransition', 'thinklet'),
            'reveal' => get_string('contentreveal', 'thinklet'),
            default => get_string('content', 'thinklet'),
        };

        $mform->addElement('editor', 'content_editor', $contentlabel, null, $editoroptions);
        $mform->setType('content_editor', PARAM_RAW);
        $mform->addRule('content_editor', null, 'required', null, 'client');
								

        if ($type === 'roc') {
            $mform->addElement('text', 'buttontext', get_string('buttontext', 'thinklet'), ['size' => 80]);
            $mform->setType('buttontext', PARAM_TEXT);
        }

if (in_array($type, ['stimulus', 'transition'], true)) {
    $mform->addElement(
        'text',
        'buttontext',
        get_string('buttontext', 'thinklet'),
        ['size' => 80]
    );
    $mform->setType('buttontext', PARAM_TEXT);

    $mform->addElement(
        'text',
        'buttonurl',
        get_string('buttonurl', 'thinklet'),
        ['size' => 80]
    );
    $mform->setType('buttonurl', PARAM_URL);

    $mform->addElement(
        'advcheckbox',
        'buttonnewwindow',
        get_string('buttonnewwindow', 'thinklet')
    );

$mform->addElement(
    'text',
    'resumeButtonText',
    get_string('resumebuttontext', 'thinklet'),
    ['size' => 80]
);
$mform->setType('resumeButtonText', PARAM_TEXT);
	
}

        if (in_array($type, ['qcm', 'roc', 'openquestion'], true)) {
            $mform->addElement(
                'editor',
                'feedback_editor',
                get_string('feedback', 'thinklet'),
                null,
                $editoroptions
            );
            $mform->setType('feedback_editor', PARAM_RAW);
        }

        $this->add_action_buttons(true, get_string('saveblock', 'thinklet'));
    }
}
