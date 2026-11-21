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
 * Displays a Thinklet activity.
 *
 * @package    mod_thinklet
 * @copyright  2026 Olivier Delhaye
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once('lib.php');
require_once($CFG->libdir . '/completionlib.php');

$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('thinklet', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$thinklet = $DB->get_record('thinklet', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/thinklet:view', $context);

// Record views in Moodle's standard activity logs (without storing responses).
$event = \mod_thinklet\event\course_module_viewed::create([
    'objectid' => $thinklet->id,
    'context' => $context,
]);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('thinklet', $thinklet);
$event->trigger();

$PAGE->set_url('/mod/thinklet/view.php', ['id' => $cm->id]);
$PAGE->set_title($thinklet->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('incourse');
// Keep the activity tabs and editing controls in Boost's centered column.
$PAGE->add_body_class('limitedwidth');

$canmanage = has_capability('mod/thinklet:manageblocks', $context);
$showediting = $PAGE->user_is_editing() && $canmanage;

$blocks = $DB->get_records(
    'thinklet_blocks',
    ['thinkletid' => $thinklet->id],
    'sortorder ASC, id ASC'
);

// Allow editors to preview one block or the whole Thinklet from block management.
$previewblockid = optional_param('previewblock', 0, PARAM_INT);
$previewall = optional_param('previewall', 0, PARAM_BOOL);
$previewexpanded = optional_param('previewexpanded', 0, PARAM_BOOL);
if (!$canmanage) {
    $previewblockid = 0;
    $previewall = 0;
    $previewexpanded = 0;
} elseif (!isset($blocks[$previewblockid])) {
    $previewblockid = 0;
}

if ($blocks) {
    $PAGE->requires->js_call_amd('mod_thinklet/view', 'init', [[
        'minimumCharactersText' => get_string('minimumcharacters', 'thinklet'),
        'previewExpanded' => (bool)$previewexpanded,
        'previewBlockId' => (int)$previewblockid,
        'courseModuleId' => (string)$cm->id,
        'sessionKey' => sesskey(),
        'completionUrl' => (new moodle_url('/mod/thinklet/complete.php'))->out(false),
        'completionError' => get_string('completionerror', 'thinklet'),
    ]]);
}

$completionenabled = $blocks && !isguestuser() && is_enrolled($context, $USER->id) &&
    (new completion_info($course))->is_enabled($cm) == COMPLETION_TRACKING_AUTOMATIC &&
    !empty($thinklet->completionfinish);

$templatedata = [
    'editurl' => $showediting ? (new moodle_url('/mod/thinklet/edit.php', ['id' => $cm->id]))->out(false) : '',
    'previewexpanded' => (bool)$previewexpanded,
    'hasblocks' => !empty($blocks),
    'blocks' => [],
];

$lastblock = end($blocks);
reset($blocks);

$formatblocktext = static function (string $text, string $area, int $blockid, int $format) use ($context): string {
    $text = file_rewrite_pluginfile_urls($text, 'pluginfile.php', $context->id, 'mod_thinklet', $area, $blockid);
    return format_text($text, $format, ['context' => $context, 'overflowdiv' => true]);
};

$blocknumber = 1;

foreach ($blocks as $block) {

    $islastblock = ($lastblock && (int)$block->id === (int)$lastblock->id);

    $options = !empty($block->optionsjson)
        ? json_decode($block->optionsjson)
        : new stdClass();

    $type = $block->type;
    $title = format_string($block->title);
    $contentformat = $block->contentformat ?? FORMAT_HTML;

    // Custom label for the button that advances to the next block.
    // Use the default Continue label when no custom label is saved.
    $nextbuttontext = trim($options->nextbuttontext ?? '');

    if ($nextbuttontext === '') {
        $nextbuttontext = get_string('continue', 'thinklet');
    }

    $content = $formatblocktext($block->content ?? '', 'blockcontent', $block->id, $contentformat);

    $initialvisible = $previewall || $previewexpanded || ($previewblockid
        ? ((int)$block->id === $previewblockid)
        : ($blocknumber === 1));
    $data = [
        'id' => (int)$block->id,
        'type' => $type,
        'number' => $blocknumber,
        'title' => $title,
        'hiddenblock' => !$initialvisible,
        'notlast' => !$islastblock,
        'nexttext' => $nextbuttontext,
        'content' => $content,
        'feedbackid' => 'thinklet-feedback-' . $block->id,
        'editurl' => $showediting ? (new moodle_url('/mod/thinklet/editblock.php', [
            'id' => $cm->id, 'blockid' => $block->id,
        ]))->out(false) : '',
    ];
    if (in_array($type, ['qcm', 'roc', 'openquestion'], true)) {
        $feedback = $options->feedback ?? '';
        $data['feedback'] = trim($feedback) === '' ? '' : $formatblocktext(
            $feedback, 'blockfeedback', $block->id, $options->feedbackformat ?? FORMAT_HTML
        );
        $data['hasfeedback'] = $data['feedback'] !== '';
    }

    switch ($type) {
        case 'stimulus':
            $data['stimulus'] = true;
            $data['buttontext'] = $options->buttontext ?? get_string('continue', 'thinklet');
            $data['externalurl'] = trim($options->buttonurl ?? '');
            $data['hasexternal'] = $data['externalurl'] !== '';
            $data['newwindow'] = !empty($options->buttonnewwindow);
            $data['resumetext'] = $options->resumeButtonText ?? get_string('resumebuttondefault', 'thinklet');
            if ($islastblock && !empty($options->returncourse)) {
                $data['returnurl'] = (new moodle_url('/course/view.php', ['id' => $course->id]))->out(false);
                $data['returntext'] = trim($options->returncoursetext ?? '') ?: get_string('returncourse', 'thinklet');
                $data['completion'] = $completionenabled ? '1' : '0';
            }
            break;

        case 'qcm':
            $data['qcm'] = true;
            $data['buttontext'] = $options->buttontext ?? get_string('showfeedback', 'thinklet');
            $single = ($options->selectiontype ?? 'multiple') === 'single';
            $data['choices'] = [];
            foreach (preg_split('/\r\n|\r|\n/', trim($options->choices ?? '')) as $index => $choice) {
                if (trim($choice) === '') {
                    continue;
                }
                $data['choices'][] = [
                    'inputid' => 'thinklet-qcm-' . $block->id . '-' . $index,
                    'inputtype' => $single ? 'radio' : 'checkbox',
                    'name' => $single ? 'thinklet-qcm-' . $block->id : '',
                    'label' => $choice,
                ];
            }
            break;

        case 'roc':
        case 'openquestion':
            $isroc = $type === 'roc';
            $data['textquestion'] = true;
            $data['roc'] = $isroc;
            $data['buttontext'] = $options->buttontext ?? get_string(
                $isroc ? 'showfeedback' : 'showpossiblesolution',
                'thinklet'
            );
            $data['validateid'] = ($isroc ? 'thinklet-roc-button-' : 'thinklet-button-') . $block->id;
            $data['nextid'] = ($isroc ? 'thinklet-roc-next-' : 'thinklet-open-next-') . $block->id;
            $data['actionid'] = $data['hasfeedback'] ? $data['validateid'] : $data['nextid'];
            if (!$isroc) {
                $data['threshold'] = $options->threshold ?? 40;
                $data['initialtext'] = $options->initialtext ?? '';
            }
            break;

        case 'reveal':
            $data['reveal'] = true;
            $data['buttontext'] = $options->buttontext ?? get_string('showmore', 'thinklet');
            $data['leadtext'] = $options->leadtext ?? '';
            $data['haslead'] = trim($data['leadtext']) !== '';
            if ($data['haslead']) {
                $data['leadtext'] = $formatblocktext(
                    $data['leadtext'], 'blocklead', $block->id, $options->leadtextformat ?? FORMAT_HTML
                );
            }
            break;

        case 'transition':
        default:
            $data['transition'] = $type === 'transition';
            $data['fallbacknext'] = !$data['transition'] || !$islastblock;
            $data['buttontext'] = $options->buttontext ?? get_string('continue', 'thinklet');
            break;
    }

    $templatedata['blocks'][] = $data;
    $blocknumber++;
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_thinklet/view', $templatedata);
echo $OUTPUT->footer();
