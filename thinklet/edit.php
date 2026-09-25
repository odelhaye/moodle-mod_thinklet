<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

require('../../config.php');

$id = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('thinklet', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$thinklet = $DB->get_record('thinklet', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/thinklet:manageblocks', $context);

$PAGE->set_url('/mod/thinklet/edit.php', ['id' => $cm->id]);
$PAGE->set_title(get_string('editthinklet', 'thinklet'));
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('incourse');
$PAGE->requires->css('/mod/thinklet/styles.css');

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('editthinklet', 'thinklet') . ' : ' . format_string($thinklet->name));

echo html_writer::link(
    new moodle_url('/mod/thinklet/view.php', ['id' => $cm->id]),
    get_string('backtothinklet', 'thinklet'),
    ['class' => 'btn btn-outline-secondary mb-4 thinklet-admin-outline']
);

echo $OUTPUT->heading(get_string('addblock', 'thinklet'), 3);

echo '<div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:30px;">';

$buttons = [
    'stimulus' => ['label' => get_string('stimulus', 'thinklet'), 'icon' => 'M4 2h6l3 3v9H4z M10 2v3h3 M6 8h5 M6 10h5 M6 12h3'],
    'qcm' => ['label' => get_string('qcm', 'thinklet'), 'icon' => 'M2 3h3v3H2z M7 4h7 M2 9h3v3H2z M7 10h7'],
    'roc' => ['label' => get_string('roc', 'thinklet'), 'icon' => 'M2 4h12v8H2z M5 7h6 M5 9h4'],
    'openquestion' => ['label' => get_string('openquestion', 'thinklet'), 'icon' => 'M2 12l1-3 7-7 3 3-7 7z M9 3l3 3 M2 14h12'],
    'reveal' => ['label' => get_string('reveal', 'thinklet'), 'icon' => 'M1 8s3-5 7-5 7 5 7 5-3 5-7 5-7-5-7-5z M8 6a2 2 0 1 0 0 4 2 2 0 0 0 0-4z']
];
foreach ($buttons as $type => $button) {

    $url = new moodle_url('/mod/thinklet/editblock.php', [
        'id' => $cm->id,
        'type' => $type
    ]);

    $icon = html_writer::tag('svg', html_writer::empty_tag('path', [
        'd' => $button['icon'],
        'fill' => 'none',
        'stroke' => 'currentColor',
        'stroke-width' => '1.5',
        'stroke-linecap' => 'round',
        'stroke-linejoin' => 'round'
    ]), [
        'width' => '18', 'height' => '18', 'viewBox' => '0 0 16 16',
        'class' => 'me-2', 'aria-hidden' => 'true', 'focusable' => 'false'
    ]);
    echo html_writer::link(
        $url,
        $icon . s($button['label']),
        ['class' => 'btn btn-primary thinklet-add-block-button']
    );
}

echo '</div>';

$blocks = $DB->get_records(
    'thinklet_blocks',
    ['thinkletid' => $thinklet->id],
    'sortorder ASC'
);

echo $OUTPUT->heading(get_string('blocksstitle', 'thinklet'), 3);

if (!$blocks) {

    echo html_writer::div(
        get_string('noblocks', 'thinklet'),
        'alert alert-info'
    );

} else {

    $blocknumber = 1;
    foreach ($blocks as $block) {

        $types = [
            'stimulus' => get_string('stimulus', 'thinklet'),
            'qcm' => get_string('qcm', 'thinklet'),
            'roc' => get_string('roc', 'thinklet'),
            'openquestion' => get_string('openquestion', 'thinklet'),
            'reveal' => get_string('reveal', 'thinklet'),
            'transition' => get_string('transition', 'thinklet')
        ];

        $typename = $types[$block->type] ?? $block->type;

        echo '<div class="card mb-3">';
        echo '<div class="card-body">';

        echo '<h5 style="margin-bottom:8px;">'
            . html_writer::span($blocknumber, 'thinklet-number me-2')
            . format_string($block->title)
            . '</h5>';

        echo '<div style="color:#666;margin-bottom:15px;">'
            . $typename
            . '</div>';

        echo html_writer::link(
            new moodle_url('/mod/thinklet/editblock.php', [
                'id' => $cm->id,
                'blockid' => $block->id
            ]),
            get_string('edit', 'thinklet'),
            ['class' => 'btn btn-outline-primary btn-sm']
        );
								
								
echo ' ';

echo html_writer::link(
    new moodle_url('/mod/thinklet/moveblock.php', [
        'id' => $cm->id,
        'blockid' => $block->id,
        'direction' => 'up',
        'sesskey' => sesskey()
    ]),
    get_string('moveup', 'thinklet'),
    ['class' => 'btn btn-outline-secondary btn-sm']
);

echo ' ';

echo html_writer::link(
    new moodle_url('/mod/thinklet/moveblock.php', [
        'id' => $cm->id,
        'blockid' => $block->id,
        'direction' => 'down',
        'sesskey' => sesskey()
    ]),
    get_string('movedown', 'thinklet'),
    ['class' => 'btn btn-outline-secondary btn-sm']
);								
								
								

        echo ' ';

        echo html_writer::link(
            new moodle_url('/mod/thinklet/deleteblock.php', [
                'id' => $cm->id,
                'blockid' => $block->id,
                'sesskey' => sesskey()
            ]),
            get_string('delete', 'thinklet'),
            [
                'class' => 'btn btn-outline-danger btn-sm',
                'onclick' => 'return confirm(' . json_encode(get_string('deleteblockconfirm', 'thinklet')) . ');'
            ]
        );

        echo '</div>';
        echo '</div>';
        $blocknumber++;
    }
}

echo $OUTPUT->footer();
