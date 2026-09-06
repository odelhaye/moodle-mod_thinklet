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

echo $OUTPUT->header();

echo html_writer::div(
    format_module_intro('thinklet', $thinklet, $cm->id),
    'alert alert-light mb-4'
);

echo $OUTPUT->heading(get_string('editthinklet', 'thinklet') . ' : ' . format_string($thinklet->name));

echo html_writer::link(
    new moodle_url('/mod/thinklet/view.php', ['id' => $cm->id]),
    get_string('backtothinklet', 'thinklet'),
    ['class' => 'btn btn-outline-secondary mb-4']
);

echo $OUTPUT->heading(get_string('addblock', 'thinklet'), 3);

echo '<div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:30px;">';

$buttons = [
    'stimulus' => get_string('stimulus', 'thinklet'),
    'qcm' => get_string('qcm', 'thinklet'),
    'roc' => get_string('roc', 'thinklet'),
    'openquestion' => get_string('openquestion', 'thinklet'),
    'reveal' => get_string('reveal', 'thinklet')
];
foreach ($buttons as $type => $label) {

    $url = new moodle_url('/mod/thinklet/editblock.php', [
        'id' => $cm->id,
        'type' => $type
    ]);

    echo html_writer::link(
        $url,
        '+ ' . $label,
        ['class' => 'btn btn-primary']
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
    }
}

echo $OUTPUT->footer();
