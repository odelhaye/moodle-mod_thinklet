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



		if ($type === 'openquestion') {
    $mform->addElement(
        'textarea',
        'initialtext',
        get_string('initialtext', 'thinklet'),
        ['rows' => 8, 'cols' => 80]
    );
    $mform->setType('initialtext', PARAM_RAW);
}

        if (in_array($type, ['qcm', 'openquestion', 'reveal'], true)) {
            $mform->addElement('text', 'buttontext', get_string('buttontext', 'thinklet'), ['size' => 80]);
            $mform->setType('buttontext', PARAM_TEXT);
        }

        $contentlabel = match ($type) {
            'stimulus' => get_string('contentstimulus', 'thinklet'),
            'qcm' => get_string('contentqcm', 'thinklet'),
            'qcmrenf' => get_string('contentqcmrenf', 'thinklet'),
                'roc' => get_string('contentroc', 'thinklet'),
            'openquestion' => get_string('contentopenquestion', 'thinklet'),
            'transition' => get_string('contenttransition', 'thinklet'),
            'reveal' => get_string('contentreveal', 'thinklet'),
            default => get_string('content', 'thinklet'),
        };

        $mform->addElement('editor', 'content_editor', $contentlabel, null, $editoroptions);
        $mform->setType('content_editor', PARAM_RAW);
        $mform->addRule('content_editor', null, 'required', null, 'client');
								
	



	
if ($type === 'qcm') {

    // Choix entre réponse unique et réponses multiples.
    $mform->addElement(
        'select',
        'selectiontype',
        get_string('selectiontype', 'thinklet'),
        [
            'single'   => get_string('selectiontypesingle', 'thinklet'),
            'multiple' => get_string('selectiontypemultiple', 'thinklet'),
        ]
    );
    $mform->setDefault('selectiontype', 'multiple');

    // Propositions de réponse.
    $mform->addElement(
        'textarea',
        'choices',
        get_string('choices', 'thinklet'),
        ['rows' => 6, 'cols' => 80]
    );
    $mform->setType('choices', PARAM_RAW);
}

        if ($type === 'qcmrenf') {
            $mform->addElement('header', 'reinforcementsettings', get_string('reinforcementsettings', 'thinklet'));

            $mform->addElement('select', 'reinforcementmode', get_string('reinforcementmode', 'thinklet'), [
                'choice' => get_string('reinforcementmodechoice', 'thinklet'),
                'shortanswer' => get_string('reinforcementmodeshortanswer', 'thinklet'),
            ]);
            $mform->setDefault('reinforcementmode', 'choice');

            $mform->addElement('text', 'correctpoints', get_string('correctpoints', 'thinklet'), ['size' => 8]);
            $mform->setType('correctpoints', PARAM_INT);
            $mform->setDefault('correctpoints', 10);

            $mform->addElement('text', 'bonustarget', get_string('bonustarget', 'thinklet'), ['size' => 8]);
            $mform->setType('bonustarget', PARAM_INT);
            $mform->setDefault('bonustarget', 5);

            $mform->addElement('text', 'bonuspoints', get_string('bonuspoints', 'thinklet'), ['size' => 8]);
            $mform->setType('bonuspoints', PARAM_INT);
            $mform->setDefault('bonuspoints', 25);

            $mform->addElement('text', 'retrygap', get_string('retrygap', 'thinklet'), ['size' => 8]);
            $mform->setType('retrygap', PARAM_INT);
            $mform->setDefault('retrygap', 2);

            $mform->addElement('text', 'stopskip', get_string('stopskip', 'thinklet'), ['size' => 8]);
            $mform->setType('stopskip', PARAM_INT);
            $mform->setDefault('stopskip', 1);

            $mform->addElement('text', 'continuebuttontext', get_string('continuebuttontext', 'thinklet'), ['size' => 40]);
            $mform->setType('continuebuttontext', PARAM_TEXT);
            $mform->setDefault('continuebuttontext', get_string('continue', 'thinklet'));

            $mform->addElement('text', 'stopbuttontext', get_string('stopbuttontext', 'thinklet'), ['size' => 40]);
            $mform->setType('stopbuttontext', PARAM_TEXT);
            $mform->setDefault('stopbuttontext', get_string('stopheredefault', 'thinklet'));

            $mform->addElement('advcheckbox', 'enablesound', get_string('enablesound', 'thinklet'));

            $repeatarray = [];
            $repeatarray[] = $mform->createElement('header', 'qcmrenfitemheader', get_string('qcmrenfitem', 'thinklet'));
            $repeatarray[] = $mform->createElement('text', 'itemprompt', get_string('itemprompt', 'thinklet'), ['size' => 80]);
            $repeatarray[] = $mform->createElement('text', 'itemimageurl', get_string('itemimageurl', 'thinklet'), ['size' => 80]);
            $repeatarray[] = $mform->createElement('textarea', 'itemchoices', get_string('choices', 'thinklet'), ['rows' => 4, 'cols' => 80]);
            $repeatarray[] = $mform->createElement('select', 'itemcorrect', get_string('itemcorrect', 'thinklet'), [
                1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5', 6 => '6',
            ]);
            $repeatarray[] = $mform->createElement('textarea', 'itemanswers', get_string('itemanswers', 'thinklet'), ['rows' => 3, 'cols' => 80]);

            $repeatoptions = [
                'itemprompt' => ['type' => PARAM_TEXT],
                'itemimageurl' => ['type' => PARAM_URL],
                'itemchoices' => ['type' => PARAM_RAW],
                'itemcorrect' => ['type' => PARAM_INT],
                'itemanswers' => ['type' => PARAM_RAW],
            ];

            $repeatcount = max(5, (int)($this->_customdata['repeatcount'] ?? 5));
            $this->repeat_elements(
                $repeatarray,
                $repeatcount,
                $repeatoptions,
                'qcmrenf_repeats',
                'qcmrenf_add_fields',
                1,
                get_string('addqcmrenfitem', 'thinklet'),
                true
            );

            $mform->addElement('text', 'nextbuttontext', get_string('nextbuttontext', 'thinklet'), ['size' => 80]);
            $mform->setType('nextbuttontext', PARAM_TEXT);
            $mform->setDefault('nextbuttontext', get_string('continue', 'thinklet'));
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


if (in_array($type, ['qcm', 'roc', 'openquestion', 'reveal'], true)) {
    $mform->addElement(
        'text',
        'nextbuttontext',
        get_string('nextbuttontext', 'thinklet'),
        ['size' => 80]
    );
    $mform->setType('nextbuttontext', PARAM_TEXT);
    $mform->setDefault('nextbuttontext', get_string('continue', 'thinklet'));
}
        $this->add_action_buttons(true, get_string('saveblock', 'thinklet'));
    }
}
