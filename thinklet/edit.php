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
 * Displays the Thinklet block management page.
 *
 * @package    mod_thinklet
 * @copyright  2026 Olivier Delhaye
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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

$buttons = [
    'stimulus' => ['label' => get_string('stimulus', 'thinklet'), 'icon' => 'M4 2h6l3 3v9H4z M10 2v3h3 M6 8h5 M6 10h5 M6 12h3'],
    'qcm' => ['label' => get_string('qcm', 'thinklet'), 'icon' => 'M2 3h3v3H2z M7 4h7 M2 9h3v3H2z M7 10h7'],
    'roc' => ['label' => get_string('roc', 'thinklet'), 'icon' => 'M2 4h12v8H2z M5 7h6 M5 9h4'],
    'openquestion' => ['label' => get_string('openquestion', 'thinklet'), 'icon' => 'M2 12l1-3 7-7 3 3-7 7z M9 3l3 3 M2 14h12'],
    'reveal' => ['label' => get_string('reveal', 'thinklet'), 'icon' => 'M1 8s3-5 7-5 7 5 7 5-3 5-7 5-7-5-7-5z M8 6a2 2 0 1 0 0 4 2 2 0 0 0 0-4z']
];
$addbuttons = [];
foreach ($buttons as $type => $button) {
    $button['url'] = (new moodle_url('/mod/thinklet/editblock.php', [
        'id' => $cm->id,
        'type' => $type,
    ]))->out(false);
    $addbuttons[] = $button;
}

$blocks = $DB->get_records('thinklet_blocks', ['thinkletid' => $thinklet->id], 'sortorder ASC');
$types = [
    'stimulus' => get_string('stimulus', 'thinklet'),
    'qcm' => get_string('qcm', 'thinklet'),
    'roc' => get_string('roc', 'thinklet'),
    'openquestion' => get_string('openquestion', 'thinklet'),
    'reveal' => get_string('reveal', 'thinklet'),
    'transition' => get_string('transition', 'thinklet')
];

$rows = [];
foreach ($blocks as $block) {
    $params = ['id' => $cm->id, 'blockid' => $block->id];
    $actionparams = $params + ['sesskey' => sesskey()];
    $rows[] = [
        'number' => count($rows) + 1,
        'title' => format_string($block->title),
        'type' => $types[$block->type] ?? $block->type,
        'editurl' => (new moodle_url('/mod/thinklet/editblock.php', $params))->out(false),
        'previewurl' => (new moodle_url('/mod/thinklet/view.php', [
            'id' => $cm->id, 'previewblock' => $block->id,
        ]))->out(false),
        'upurl' => (new moodle_url('/mod/thinklet/moveblock.php', $actionparams + ['direction' => 'up']))->out(false),
        'downurl' => (new moodle_url('/mod/thinklet/moveblock.php', $actionparams + ['direction' => 'down']))->out(false),
        'deleteurl' => (new moodle_url('/mod/thinklet/deleteblock.php', $actionparams))->out(false),
    ];
}

$viewparams = ['id' => $cm->id];
$templatedata = [
    'title' => format_string($thinklet->name),
    'backurl' => (new moodle_url('/mod/thinklet/view.php', $viewparams))->out(false),
    'previewallurl' => (new moodle_url('/mod/thinklet/view.php', $viewparams + ['previewall' => 1]))->out(false),
    'previewexpandedurl' => (new moodle_url('/mod/thinklet/view.php', $viewparams + ['previewexpanded' => 1]))->out(false),
    'addbuttons' => $addbuttons,
    'hasblocks' => !empty($rows),
    'blocks' => $rows,
];

$PAGE->requires->js_call_amd('mod_thinklet/edit', 'init', [get_string('deleteblockconfirm', 'thinklet')]);
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_thinklet/edit', $templatedata);
echo $OUTPUT->footer();
