<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

require('../../config.php');
require_once($CFG->dirroot . '/mod/thinklet/classes/form/editblock_form.php');

$id = required_param('id', PARAM_INT); // Course module id.
$type = optional_param('type', 'stimulus', PARAM_ALPHANUMEXT);
$blockid = optional_param('blockid', 0, PARAM_INT);

$allowedtypes = ['stimulus', 'qcm', 'qcmrenf', 'roc', 'openquestion', 'reveal', 'transition'];
if (!in_array($type, $allowedtypes, true)) {
    throw new moodle_exception('invalidblocktype', 'thinklet');
}

$cm = get_coursemodule_from_id('thinklet', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$thinklet = $DB->get_record('thinklet', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/thinklet:manageblocks', $context);

if ($blockid) {
    $block = $DB->get_record(
        'thinklet_blocks',
        ['id' => $blockid, 'thinkletid' => $thinklet->id],
        '*',
        MUST_EXIST
    );
    $type = $block->type;
} else {
    $block = null;
}

$typenames = [
    'stimulus' => get_string('stimulus', 'thinklet'),
    'qcm' => get_string('qcm', 'thinklet'),
    'qcmrenf' => get_string('qcmrenf', 'thinklet'),
    'roc' => get_string('roc', 'thinklet'),
    'openquestion' => get_string('openquestion', 'thinklet'),
    'reveal' => get_string('reveal', 'thinklet'),
    'transition' => get_string('transition', 'thinklet'),
];

$typename = $typenames[$type] ?? $type;

$options = $block && !empty($block->optionsjson)
    ? json_decode($block->optionsjson)
    : new stdClass();

$url = new moodle_url('/mod/thinklet/editblock.php', [
    'id' => $cm->id,
    'type' => $type,
    'blockid' => $blockid
]);

$PAGE->set_url($url);
$PAGE->set_title(get_string('editblock', 'thinklet'));
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('incourse');

$editoroptions = [
    'context' => $context,
    'maxfiles' => EDITOR_UNLIMITED_FILES,
    'maxbytes' => $CFG->maxbytes,
    'trusttext' => false,
    'subdirs' => false,
    'noclean' => false,
];

$mform = new \mod_thinklet\form\editblock_form($url, [
    'id' => $cm->id,
    'type' => $type,
    'blockid' => $blockid,
    'editoroptions' => $editoroptions,
    'repeatcount' => ($type === 'qcmrenf' && is_array($options->items ?? null))
        ? max(5, count($options->items)) : 5,
]);

$defaultbuttontext = match ($type) {
    'openquestion' => get_string('showpossiblesolution', 'thinklet'),
    'reveal' => get_string('showmore', 'thinklet'),
    'stimulus', 'transition' => get_string('continue', 'thinklet'),
    default => get_string('showfeedback', 'thinklet'),
};

$toform = new stdClass();
$toform->id = $cm->id;
$toform->type = $type;
$toform->blockid = $blockid;
$toform->title = $block ? $block->title : '';
$toform->content = $block ? $block->content : '';
$toform->contentformat = $block && isset($block->contentformat) ? $block->contentformat : FORMAT_HTML;
$toform = file_prepare_standard_editor(
    $toform,
    'content',
    $editoroptions,
    $context,
    'mod_thinklet',
    'blockcontent',
    $block ? $block->id : 0
);

if ($type === 'reveal') {
    $toform->leadtext = $options->leadtext ?? '';
    $toform->leadtextformat = $options->leadtextformat ?? FORMAT_HTML;
    $toform = file_prepare_standard_editor(
        $toform,
        'leadtext',
        $editoroptions,
        $context,
        'mod_thinklet',
        'blocklead',
        $block ? $block->id : 0
    );
				    $toform->nextbuttontext = $options->nextbuttontext ?? get_string('continue', 'thinklet');
}

if ($type === 'qcmrenf') {
    $items = is_array($options->items ?? null) ? $options->items : [];
    $toform->correctpoints = $options->correctpoints ?? 10;
    $toform->bonustarget = $options->bonustarget ?? 5;
    $toform->bonuspoints = $options->bonuspoints ?? 25;
    $toform->retrygap = $options->retrygap ?? 2;
    $toform->continuebuttontext = $options->continuebuttontext ?? get_string('continue', 'thinklet');
    $toform->stopbuttontext = $options->stopbuttontext ?? get_string('stopheredefault', 'thinklet');
    $toform->enablesound = !empty($options->enablesound) ? 1 : 0;
    $toform->nextbuttontext = $options->nextbuttontext ?? get_string('continue', 'thinklet');
    $toform->qcmrenf_repeats = max(5, count($items));
    $toform->itemprompt = [];
    $toform->itemimageurl = [];
    $toform->itemchoices = [];
    $toform->itemcorrect = [];
    foreach ($items as $index => $item) {
        $toform->itemprompt[$index] = $item->prompt ?? '';
        $toform->itemimageurl[$index] = $item->imageurl ?? '';
        $toform->itemchoices[$index] = isset($item->choices) && is_array($item->choices)
            ? implode("\n", $item->choices) : '';
        $toform->itemcorrect[$index] = ((int)($item->correct ?? 0)) + 1;
    }
}

if (in_array($type, ['qcm', 'roc', 'openquestion'], true)) {

if ($type === 'qcm') {
    $toform->choices = $options->choices ?? '';
    $toform->selectiontype = $options->selectiontype ?? 'multiple';
    $toform->nextbuttontext = $options->nextbuttontext ?? get_string('continue', 'thinklet');
}

    $toform->feedback = $options->feedback ?? '';
    $toform->feedbackformat = $options->feedbackformat ?? FORMAT_HTML;

    $toform = file_prepare_standard_editor(
        $toform,
        'feedback',
        $editoroptions,
        $context,
        'mod_thinklet',
        'blockfeedback',
        $block ? $block->id : 0 
    );
}

if ($type === 'openquestion') {
    $toform->threshold = $options->threshold ?? 120;
    $toform->initialtext = $options->initialtext ?? '';
				    $toform->nextbuttontext = $options->nextbuttontext ?? get_string('continue', 'thinklet');
}

if (in_array($type, ['stimulus', 'qcm', 'roc', 'openquestion', 'reveal', 'transition'], true)) {
    $toform->buttontext = $options->buttontext ?? $defaultbuttontext;
}

if (in_array($type, ['stimulus', 'transition'], true)) {
    $toform->buttonurl = $options->buttonurl ?? '';
    $toform->buttonnewwindow = !empty($options->buttonnewwindow) ? 1 : 0;
    $toform->resumeButtonText = $options->resumeButtonText ?? get_string('resumebuttondefault', 'thinklet');
}

$mform->set_data($toform);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/mod/thinklet/edit.php', ['id' => $cm->id]));
}

if ($data = $mform->get_data()) {
    $newoptions = new stdClass();

if ($type === 'openquestion') {
    $newoptions->threshold = $data->threshold ?? 120;
    $newoptions->initialtext = $data->initialtext ?? '';
    $newoptions->buttontext = $data->buttontext ?? get_string('showpossiblesolution', 'thinklet');

}

if ($type === 'qcm') {
    $newoptions->choices = $data->choices ?? '';
    $newoptions->selectiontype = $data->selectiontype ?? 'multiple';
    $newoptions->buttontext = $data->buttontext ?? get_string('showfeedback', 'thinklet');
    $newoptions->nextbuttontext = $data->nextbuttontext ?? get_string('continue', 'thinklet');
}

if ($type === 'qcmrenf') {
    $newoptions->correctpoints = max(0, (int)($data->correctpoints ?? 10));
    $newoptions->bonustarget = max(2, (int)($data->bonustarget ?? 5));
    $newoptions->bonuspoints = max(0, (int)($data->bonuspoints ?? 25));
    $newoptions->retrygap = max(0, (int)($data->retrygap ?? 2));
    $newoptions->continuebuttontext = trim($data->continuebuttontext ?? '') ?: get_string('continue', 'thinklet');
    $newoptions->stopbuttontext = trim($data->stopbuttontext ?? '') ?: get_string('stopheredefault', 'thinklet');
    $newoptions->enablesound = !empty($data->enablesound) ? 1 : 0;
    $newoptions->nextbuttontext = trim($data->nextbuttontext ?? '') ?: get_string('continue', 'thinklet');
    $newoptions->items = [];

    $prompts = $data->itemprompt ?? [];
    foreach ($prompts as $index => $prompt) {
        $choices = preg_split('/\r\n|\r|\n/', trim($data->itemchoices[$index] ?? ''));
        $choices = array_values(array_filter(array_map('trim', $choices), static function($choice) {
            return $choice !== '';
        }));
        if (trim($prompt) === '' || count($choices) < 2) {
            continue;
        }
        $correct = max(0, ((int)($data->itemcorrect[$index] ?? 1)) - 1);
        if ($correct >= count($choices)) {
            $correct = 0;
        }
        $newoptions->items[] = (object)[
            'prompt' => trim($prompt),
            'imageurl' => trim($data->itemimageurl[$index] ?? ''),
            'choices' => $choices,
            'correct' => $correct,
        ];
    }
}

    if ($type === 'roc') {
            $newoptions->buttontext = $data->buttontext ?? get_string('showfeedback', 'thinklet');
												    $newoptions->nextbuttontext = $data->nextbuttontext ?? get_string('continue', 'thinklet');
    }

if (in_array($type, ['stimulus', 'transition'], true)) {
    $newoptions->buttontext = $data->buttontext ?? get_string('continue', 'thinklet');
    $newoptions->buttonurl = trim($data->buttonurl ?? '');
    $newoptions->buttonnewwindow = !empty($data->buttonnewwindow) ? 1 : 0;
    $newoptions->resumeButtonText = trim($data->resumeButtonText ?? '') ?: get_string('resumebuttondefault', 'thinklet');
}

    if ($block) {
        $itemid = $block->id;
    } else {
        $record = new stdClass();
        $record->thinkletid = $thinklet->id;
        $record->type = $type;
        $record->title = $data->title;
        $record->content = '';
        $record->contentformat = FORMAT_HTML;
        $record->optionsjson = '{}';
        $record->sortorder = $DB->count_records('thinklet_blocks', ['thinkletid' => $thinklet->id]) + 1;
        $itemid = $DB->insert_record('thinklet_blocks', $record);
        $block = $DB->get_record('thinklet_blocks', ['id' => $itemid], '*', MUST_EXIST);
    }

    $data = file_postupdate_standard_editor(
        $data,
        'content',
        $editoroptions,
        $context,
        'mod_thinklet',
        'blockcontent',
        $itemid
    );

    if (in_array($type, ['qcm', 'roc', 'openquestion'], true)) {

        $data = file_postupdate_standard_editor(
            $data,
            'feedback',
            $editoroptions,
            $context,
            'mod_thinklet',
            'blockfeedback',
            $itemid
        );

        $newoptions->feedback = $data->feedback ?? '';
        $newoptions->feedbackformat = $data->feedbackformat ?? FORMAT_HTML;
    }

    if ($type === 'reveal') {
        $data = file_postupdate_standard_editor(
            $data,
            'leadtext',
            $editoroptions,
            $context,
            'mod_thinklet',
            'blocklead',
            $itemid
        );

        $newoptions->leadtext = $data->leadtext ?? '';
        $newoptions->leadtextformat = $data->leadtextformat ?? FORMAT_HTML;
        $newoptions->buttontext = $data->buttontext ?? get_string('showmore', 'thinklet');
								    $newoptions->nextbuttontext = $data->nextbuttontext ?? get_string('continue', 'thinklet');
    }

    $block->title = $data->title;
    $block->content = $data->content ?? '';
    $block->contentformat = $data->contentformat ?? FORMAT_HTML;
    $block->optionsjson = json_encode($newoptions, JSON_UNESCAPED_UNICODE);

    $DB->update_record('thinklet_blocks', $block);

    redirect(new moodle_url('/mod/thinklet/edit.php', ['id' => $cm->id]));
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string($block ? 'editblocktype' : 'addblocktype', 'thinklet', s($typename)));
$mform->display();
echo $OUTPUT->footer();
