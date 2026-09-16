<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

require('../../config.php');
require_once('lib.php');

$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('thinklet', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$thinklet = $DB->get_record('thinklet', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/thinklet:view', $context);

$PAGE->set_url('/mod/thinklet/view.php', ['id' => $cm->id]);
$PAGE->set_title($thinklet->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('incourse');

$PAGE->requires->css('/mod/thinklet/styles.css');

echo $OUTPUT->header();

if ($PAGE->user_is_editing() && has_capability('mod/thinklet:manageblocks', $context)) {
    echo html_writer::div(
        html_writer::link(
            new moodle_url('/mod/thinklet/edit.php', ['id' => $cm->id]),
            get_string('editblocks', 'thinklet'),
            ['class' => 'btn btn-primary mb-4']
        )
    );
}

$blocks = $DB->get_records(
    'thinklet_blocks',
    ['thinkletid' => $thinklet->id],
    'sortorder ASC'
);

if (!$blocks) {
    echo html_writer::div(
        get_string('emptyactivity', 'thinklet'),
        'alert alert-info'
    );

    echo $OUTPUT->footer();
    exit;
}

echo html_writer::start_div('thinklet-container');

$lastblock = end($blocks);
reset($blocks);

$blocknumber = 1;

foreach ($blocks as $block) {

    $islastblock = ($lastblock && (int)$block->id === (int)$lastblock->id);

    $options = !empty($block->optionsjson)
        ? json_decode($block->optionsjson)
        : new stdClass();

    $type = $block->type;
    $title = format_string($block->title);
    $contentformat = $block->contentformat ?? FORMAT_HTML;

    // Texte personnalisable du bouton qui mène au bloc suivant.
    // Si aucune valeur n'est enregistrée, on conserve "Continuer".
    $nextbuttontext = trim($options->nextbuttontext ?? '');

    if ($nextbuttontext === '') {
        $nextbuttontext = get_string('continue', 'thinklet');
    }

    // Les fichiers intégrés par TinyMCE sont stockés avec @@PLUGINFILE@@.
    $contentwithfiles = file_rewrite_pluginfile_urls(
        $block->content ?? '',
        'pluginfile.php',
        $context->id,
        'mod_thinklet',
        'blockcontent',
        $block->id
    );

    $content = format_text($contentwithfiles, $contentformat, [
        'context' => $context,
        'overflowdiv' => true,
    ]);

    $hiddenclass = ($blocknumber === 1) ? '' : ' thinklet-hidden-block';

    echo html_writer::start_div(
        'thinklet-block thinklet-sequential-block thinklet-type-' . s($type) . $hiddenclass,
        ['data-blocknumber' => $blocknumber]
    );

    echo html_writer::start_div('thinklet-block-header');

    echo html_writer::span($blocknumber, 'thinklet-number');

    if (!empty($title)) {
        echo html_writer::tag('h3', $title, ['class' => 'thinklet-title']);
    }

    if ($PAGE->user_is_editing()
        && has_capability('mod/thinklet:manageblocks', $context)) {

        $editurl = new moodle_url('/mod/thinklet/editblock.php', [
            'id' => $cm->id,
            'blockid' => $block->id,
        ]);

        echo html_writer::link(
            $editurl,
            '✎',
            [
                'class' => 'thinklet-inline-edit',
                'title' => get_string('editblock', 'thinklet'),
                'aria-label' => get_string('editblock', 'thinklet'),
                'target' => '_blank',
                'rel' => 'noopener noreferrer',
            ]
        );
    }

    echo html_writer::end_div();

    switch ($type) {

        case 'stimulus':
            $buttontext = $options->buttontext ?? get_string('continue', 'thinklet');
            $buttonurl = trim($options->buttonurl ?? '');
            $buttonnewwindow = !empty($options->buttonnewwindow);
            $resumebuttontext = $options->resumeButtonText
                ?? get_string('resumebuttondefault', 'thinklet');

            echo html_writer::div(
                $content,
                'thinklet-stimulus'
            );

            if ($buttonurl !== '') {

                $attributes = [
                    'class' => 'btn btn-primary mt-3 text-decoration-none thinklet-external-button',
                    'href' => $buttonurl,
                ];

                if ($buttonnewwindow) {
                    $attributes['target'] = '_blank';
                    $attributes['rel'] = 'noopener noreferrer';
                }

                echo html_writer::tag(
                    'a',
                    s($buttontext),
                    $attributes
                );

                if (!$islastblock) {
                    echo html_writer::tag(
                        'button',
                        s($resumebuttontext),
                        [
                            'type' => 'button',
                            'class' => 'btn btn-secondary mt-3 ms-2 thinklet-next-button thinklet-resume-button',
                            'hidden' => 'hidden'
                        ]
                    );
                }

            } elseif (!$islastblock) {

                echo html_writer::tag(
                    'button',
                    s($buttontext),
                    [
                        'type' => 'button',
                        'class' => 'btn btn-primary mt-3 thinklet-next-button'
                    ]
                );
            }

            break;

        case 'qcm':

            $choicesraw = $options->choices ?? '';
            $choices = preg_split('/\r\n|\r|\n/', trim($choicesraw));
            $buttontext = $options->buttontext ?? get_string('showfeedback', 'thinklet');
            $selectiontype = $options->selectiontype ?? 'multiple';
            $inputtype = ($selectiontype === 'single') ? 'radio' : 'checkbox';

            echo html_writer::div(
                $content,
                'thinklet-question'
            );

            echo html_writer::start_div('thinklet-choices');

            foreach ($choices as $index => $choice) {

                if (trim($choice) === '') {
                    continue;
                }

                $inputid = 'thinklet-qcm-' . $block->id . '-' . $index;

                echo html_writer::start_div('thinklet-choice');

                $inputattributes = [
                    'type' => $inputtype,
                    'id' => $inputid,
                    'class' => 'thinklet-choice-input'
                ];

                if ($inputtype === 'radio') {
                    $inputattributes['name'] = 'thinklet-qcm-' . $block->id;
                }

                echo html_writer::empty_tag('input', $inputattributes);

                echo html_writer::tag(
                    'label',
                    s($choice),
                    ['for' => $inputid]
                );

                echo html_writer::end_div();
            }

            echo html_writer::end_div();

            $feedbackraw = $options->feedback ?? '';
            $feedbackhtml = '';

            if (!empty(trim($feedbackraw))) {

                $feedbackwithfiles = file_rewrite_pluginfile_urls(
                    $feedbackraw,
                    'pluginfile.php',
                    $context->id,
                    'mod_thinklet',
                    'blockfeedback',
                    $block->id
                );

                $feedbackhtml = format_text(
                    $feedbackwithfiles,
                    $options->feedbackformat ?? FORMAT_HTML,
                    [
                        'context' => $context,
                        'overflowdiv' => true,
                    ]
                );
            }

            echo html_writer::tag(
                'button',
                s($buttontext),
                [
                    'type' => 'button',
                    'class' => 'btn btn-primary thinklet-reveal-button thinklet-qcm-validate-button',
                    'data-target' => 'thinklet-feedback-' . $block->id,
                    'hidden' => 'hidden'
                ]
            );

            if ($feedbackhtml !== '') {

                echo html_writer::div(
                    $feedbackhtml,
                    'thinklet-feedback',
                    [
                        'id' => 'thinklet-feedback-' . $block->id,
                        'hidden' => 'hidden'
                    ]
                );

                echo html_writer::tag(
                    'button',
                    s($nextbuttontext),
                    [
                        'type' => 'button',
                        'class' => 'btn btn-primary mt-3 thinklet-next-button thinklet-qcm-next-button',
                        'data-after-feedback' => 'thinklet-feedback-' . $block->id,
                        'hidden' => 'hidden'
                    ]
                );
            }

            break;

        case 'qcmrenf':
            $items = is_array($options->items ?? null) ? $options->items : [];
            $settings = [
                'items' => $items,
                'mode' => ($options->reinforcementmode ?? 'choice') === 'shortanswer'
                    ? 'shortanswer' : 'choice',
                'correctpoints' => max(0, (int)($options->correctpoints ?? 10)),
                'bonustarget' => max(2, (int)($options->bonustarget ?? 5)),
                'bonuspoints' => max(0, (int)($options->bonuspoints ?? 25)),
                'retrygap' => max(0, (int)($options->retrygap ?? 2)),
                'stopskip' => max(0, (int)($options->stopskip ?? 1)),
                'enablesound' => !empty($options->enablesound),
                'continuebuttontext' => trim($options->continuebuttontext ?? '') ?: get_string('continue', 'thinklet'),
                'stopbuttontext' => trim($options->stopbuttontext ?? '') ?: get_string('stopheredefault', 'thinklet'),
                'nextbuttontext' => $nextbuttontext,
                'islastblock' => $islastblock,
                'strings' => [
                    'score' => get_string('scorelabel', 'thinklet'),
                    'streak' => get_string('streaklabel', 'thinklet'),
                    'correct' => get_string('correctanswer', 'thinklet', '__POINTS__'),
                    'incorrect' => get_string('incorrectanswer', 'thinklet'),
                    'answerwas' => get_string('answerwas', 'thinklet', '__ANSWER__'),
                    'streakmessage' => get_string('streakmessage', 'thinklet', '__STREAK__'),
                    'onemore' => get_string('onemoreforbonus', 'thinklet'),
                    'moreforbonus' => get_string('moreforbonus', 'thinklet', '__COUNT__'),
                    'bonusmessage' => get_string('bonusmessage', 'thinklet', '__BONUS__'),
                    'bonuswon' => get_string('bonuswon', 'thinklet', '__BONUS__'),
                    'finished' => get_string('seriesfinished', 'thinklet'),
                    'validate' => get_string('validateanswer', 'thinklet'),
                    'shortplaceholder' => get_string('shortreinforcementplaceholder', 'thinklet'),
                ],
            ];

            echo html_writer::div($content, 'thinklet-question');

            if (!$items) {
                echo html_writer::div(get_string('noqcmrenfitems', 'thinklet'), 'alert alert-warning');
                break;
            }

            echo html_writer::start_div('thinklet-reinforcement', [
                'data-settings' => json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
            echo html_writer::div('', 'thinklet-reinforcement-score', ['aria-live' => 'polite']);
            echo html_writer::div('', 'thinklet-reinforcement-item');
            echo html_writer::div('', 'thinklet-reinforcement-feedback', ['aria-live' => 'polite']);
            echo html_writer::div('', 'thinklet-reinforcement-actions');
            echo html_writer::end_div();

            break;

        case 'roc':

            $buttontext = $options->buttontext ?? get_string('showfeedback', 'thinklet');
            $feedbackhtml = '';

            if (!empty(trim($options->feedback ?? ''))) {

                $feedbackwithfiles = file_rewrite_pluginfile_urls(
                    $options->feedback,
                    'pluginfile.php',
                    $context->id,
                    'mod_thinklet',
                    'blockfeedback',
                    $block->id
                );

                $feedbackhtml = format_text(
                    $feedbackwithfiles,
                    $options->feedbackformat ?? FORMAT_HTML,
                    [
                        'context' => $context,
                        'overflowdiv' => true,
                    ]
                );
            }

            $hasfeedback = ($feedbackhtml !== '');

            $actionbuttonid = $hasfeedback
                ? 'thinklet-roc-button-' . $block->id
                : 'thinklet-roc-next-' . $block->id;

            echo html_writer::div(
                $content,
                'thinklet-question'
            );

            echo html_writer::empty_tag('input', [
                'type' => 'text',
                'class' => 'form-control thinklet-short-answer',
                'placeholder' => get_string('shortanswerplaceholder', 'thinklet'),
                'data-button' => $actionbuttonid
            ]);

            if ($hasfeedback) {

                echo html_writer::tag(
                    'button',
                    s($buttontext),
                    [
                        'type' => 'button',
                        'class' => 'btn btn-primary mt-3 thinklet-reveal-button thinklet-roc-validate-button',
                        'id' => 'thinklet-roc-button-' . $block->id,
                        'data-target' => 'thinklet-feedback-' . $block->id,
                        'hidden' => 'hidden'
                    ]
                );

                echo html_writer::div(
                    $feedbackhtml,
                    'thinklet-feedback',
                    [
                        'id' => 'thinklet-feedback-' . $block->id,
                        'hidden' => 'hidden'
                    ]
                );
            }

            if (!$islastblock) {

                $continueattributes = [
                    'type' => 'button',
                    'class' => 'btn btn-primary mt-3 thinklet-next-button thinklet-roc-next-button',
                    'id' => 'thinklet-roc-next-' . $block->id,
                    'hidden' => 'hidden'
                ];

                if ($hasfeedback) {
                    $continueattributes['data-after-feedback'] =
                        'thinklet-feedback-' . $block->id;
                }

                echo html_writer::tag(
                    'button',
                    s($nextbuttontext),
                    $continueattributes
                );
            }

            break;

        case 'openquestion':

            $threshold = $options->threshold ?? 120;
            $buttontext = $options->buttontext
                ?? get_string('showpossiblesolution', 'thinklet');
            $initialtext = $options->initialtext ?? '';

            $feedbackhtml = '';

            if (!empty(trim($options->feedback ?? ''))) {

                $feedbackwithfiles = file_rewrite_pluginfile_urls(
                    $options->feedback,
                    'pluginfile.php',
                    $context->id,
                    'mod_thinklet',
                    'blockfeedback',
                    $block->id
                );

                $feedbackhtml = format_text(
                    $feedbackwithfiles,
                    $options->feedbackformat ?? FORMAT_HTML,
                    [
                        'context' => $context,
                        'overflowdiv' => true,
                    ]
                );
            }

            $hasfeedback = ($feedbackhtml !== '');

            $actionbuttonid = $hasfeedback
                ? 'thinklet-button-' . $block->id
                : 'thinklet-open-next-' . $block->id;

            echo html_writer::div(
                $content,
                'thinklet-question'
            );

            echo html_writer::tag('textarea', s($initialtext), [
                'class' => 'form-control thinklet-open-answer',
                'rows' => 8,
                'placeholder' => get_string('openanswerplaceholder', 'thinklet'),
                'data-threshold' => $threshold,
                'data-button' => $actionbuttonid,
                'data-counter' => 'thinklet-counter-' . $block->id,
                'data-initial-text' => $initialtext
            ]);

            echo html_writer::div(
                '0 / ' . $threshold . ' ' . get_string('minimumcharacters', 'thinklet'),
                'thinklet-counter',
                ['id' => 'thinklet-counter-' . $block->id]
            );

            if ($hasfeedback) {

                echo html_writer::tag(
                    'button',
                    s($buttontext),
                    [
                        'type' => 'button',
                        'class' => 'btn btn-primary mt-3 thinklet-reveal-button',
                        'id' => 'thinklet-button-' . $block->id,
                        'data-target' => 'thinklet-feedback-' . $block->id,
                        'hidden' => 'hidden'
                    ]
                );

                echo html_writer::div(
                    $feedbackhtml,
                    'thinklet-feedback',
                    [
                        'id' => 'thinklet-feedback-' . $block->id,
                        'hidden' => 'hidden'
                    ]
                );
            }

            if (!$islastblock) {

                $continueattributes = [
                    'type' => 'button',
                    'class' => 'btn btn-primary mt-3 thinklet-next-button thinklet-openquestion-next-button',
                    'id' => 'thinklet-open-next-' . $block->id,
                    'hidden' => 'hidden'
                ];

                if ($hasfeedback) {
                    $continueattributes['data-after-feedback'] =
                        'thinklet-feedback-' . $block->id;
                }

                echo html_writer::tag(
                    'button',
                    s($nextbuttontext),
                    $continueattributes
                );
            }

            break;

        case 'reveal':

            $leadtext = $options->leadtext ?? '';
            $buttontext = $options->buttontext ?? get_string('showmore', 'thinklet');

            if (!empty(trim($leadtext))) {

                $leadtextwithfiles = file_rewrite_pluginfile_urls(
                    $leadtext,
                    'pluginfile.php',
                    $context->id,
                    'mod_thinklet',
                    'blocklead',
                    $block->id
                );

                echo html_writer::div(
                    format_text(
                        $leadtextwithfiles,
                        $options->leadtextformat ?? FORMAT_HTML,
                        [
                            'context' => $context,
                            'overflowdiv' => true,
                        ]
                    ),
                    'thinklet-reveal-lead'
                );
            }

            echo html_writer::tag(
                'button',
                s($buttontext),
                [
                    'type' => 'button',
                    'class' => 'btn btn-secondary thinklet-reveal-button',
                    'data-target' => 'thinklet-reveal-' . $block->id
                ]
            );

            echo html_writer::div(
                $content,
                'thinklet-feedback',
                [
                    'id' => 'thinklet-reveal-' . $block->id,
                    'hidden' => 'hidden'
                ]
            );

            if (!$islastblock) {

                echo html_writer::tag(
                    'button',
                    s($nextbuttontext),
                    [
                        'type' => 'button',
                        'class' => 'btn btn-primary mt-3 thinklet-next-button',
                        'data-after-feedback' => 'thinklet-reveal-' . $block->id,
                        'hidden' => 'hidden'
                    ]
                );
            }

            break;

        case 'transition':

            // Compatibilité avec les anciens blocs Transition.
            $buttontext = $options->buttontext ?? get_string('continue', 'thinklet');

            echo html_writer::div(
                $content,
                'thinklet-stimulus thinklet-transition'
            );

            if (!$islastblock) {

                echo html_writer::tag(
                    'button',
                    s($buttontext),
                    [
                        'type' => 'button',
                        'class' => 'btn btn-primary mt-3 thinklet-next-button'
                    ]
                );
            }

            break;

        default:

            $buttontext = $options->buttontext ?? get_string('continue', 'thinklet');

            echo html_writer::tag(
                'button',
                s($buttontext),
                [
                    'type' => 'button',
                    'class' => 'btn btn-primary mt-3 thinklet-next-button'
                ]
            );

            break;
    }

    echo html_writer::end_div();

    $blocknumber++;
}

echo html_writer::end_div();

?>
<script>
document.addEventListener('DOMContentLoaded', function () {

    const minimumCharactersText =
        <?php echo json_encode(get_string('minimumcharacters', 'thinklet')); ?>;

    function softenOldButtons() {

        document
            .querySelectorAll('.thinklet-next-button, .thinklet-reveal-button')
            .forEach(function(button) {

                button.addEventListener('click', function() {

                    this.classList.remove('btn-outline-secondary');
                    this.classList.add('btn-primary');

                    setTimeout(() => {

                        const currentBlock =
                            this.closest('.thinklet-sequential-block');

                        const nextBlock =
                            currentBlock
                                ? currentBlock.nextElementSibling
                                : null;

                        if (
                            !nextBlock ||
                            !nextBlock.querySelector('button:not([hidden])')
                        ) {
                            this.classList.remove('btn-primary');
                            this.classList.add('btn-outline-secondary');
                        }

                    }, 100);

                    document
                        .querySelectorAll(
                            '.thinklet-next-button, .thinklet-reveal-button'
                        )
                        .forEach(function(otherbutton) {

                            if (
                                otherbutton !== button &&
                                !otherbutton.hasAttribute('hidden')
                            ) {
                                otherbutton.classList.remove('btn-primary');
                                otherbutton.classList.add('btn-outline-secondary');
                            }
                        });
                });
            });
    }

    softenOldButtons();

    function showFollowingBlock(currentBlock, skipCount) {

        let nextBlock = currentBlock.nextElementSibling;
        let remaining = Number(skipCount || 0);
        while (nextBlock && remaining > 0) {
            nextBlock = nextBlock.nextElementSibling;
            remaining -= 1;
        }

        if (
            nextBlock &&
            nextBlock.classList.contains('thinklet-sequential-block')
        ) {
            nextBlock.classList.remove('thinklet-hidden-block');

            nextBlock.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
            nextBlock.dispatchEvent(new CustomEvent('thinklet:blockshown'));
        }
    }

    function showNextBlock(currentBlock) {
        showFollowingBlock(currentBlock, 0);
    }

    /*
     * Présentation avec activité externe :
     * le bouton de reprise n'apparaît qu'après clic sur le lien.
     */
    document
        .querySelectorAll('.thinklet-external-button')
        .forEach(function(button) {

            button.addEventListener('click', function() {

                const currentBlock =
                    this.closest('.thinklet-sequential-block');

                if (!currentBlock) {
                    return;
                }

                const resumeButton =
                    currentBlock.querySelector('.thinklet-resume-button');

                if (resumeButton) {
                    const externalButton =
                        currentBlock.querySelector('.thinklet-external-button');

                    setTimeout(function() {

                        // Le bouton de retour devient le bouton principal.
                        resumeButton.classList.remove(
                            'btn-secondary',
                            'btn-outline-secondary'
                        );
                        resumeButton.classList.add('btn-primary');
                        resumeButton.removeAttribute('hidden');

                        // Le bouton externe devient secondaire.
                        if (externalButton) {
                            externalButton.classList.remove('btn-primary');
                            externalButton.classList.add('btn-outline-secondary');
                        }

                    }, 1000);
                }
            });
        });

    document
        .querySelectorAll('.thinklet-next-button')
        .forEach(function(button) {

            button.addEventListener('click', function() {

                const currentBlock =
                    this.closest('.thinklet-sequential-block');

                if (currentBlock) {
                    showNextBlock(currentBlock);
                }
            });
        });

    document
        .querySelectorAll('.thinklet-reveal-button')
        .forEach(function(button) {

            button.addEventListener('click', function() {

                const target =
                    document.getElementById(this.dataset.target);

                const currentBlock =
                    this.closest('.thinklet-sequential-block');

                if (target) {
                    target.removeAttribute('hidden');
                }

                const continueButton =
                    currentBlock
                        ? currentBlock.querySelector(
                            '[data-after-feedback="' +
                            this.dataset.target +
                            '"]'
                        )
                        : null;

                if (target && continueButton) {
                    continueButton.removeAttribute('hidden');
                    this.setAttribute('hidden', 'hidden');
                    return;
                }

                if (currentBlock) {
                    showNextBlock(currentBlock);
                }
            });
        });

    document
        .querySelectorAll('.thinklet-type-qcm')
        .forEach(function(block) {

            const button =
                block.querySelector('.thinklet-qcm-validate-button');

            const inputs =
                block.querySelectorAll('.thinklet-choice-input');

            function updateQcmButtonState() {

                if (!button) {
                    return;
                }

                const hasCheckedChoice =
                    Array.from(inputs).some(function(input) {
                        return input.checked;
                    });

                if (hasCheckedChoice) {
                    button.removeAttribute('hidden');
                } else {
                    button.setAttribute('hidden', 'hidden');
                }
            }

            inputs.forEach(function(input) {
                input.addEventListener(
                    'change',
                    updateQcmButtonState
                );
            });

            updateQcmButtonState();
        });

    document
        .querySelectorAll('.thinklet-reinforcement')
        .forEach(function(activity) {
            const settings = JSON.parse(activity.dataset.settings || '{}');
            const items = Array.isArray(settings.items) ? settings.items : [];
            const scoreElement = activity.querySelector('.thinklet-reinforcement-score');
            const itemElement = activity.querySelector('.thinklet-reinforcement-item');
            const feedbackElement = activity.querySelector('.thinklet-reinforcement-feedback');
            const actionsElement = activity.querySelector('.thinklet-reinforcement-actions');
            const block = activity.closest('.thinklet-sequential-block');
            const initialEntries = items.map(function(item, index) {
                return {item: item, index: index};
            });
            const queue = initialEntries.slice();
            window.thinkletReinforcementState = window.thinkletReinforcementState || {score: 0};
            let score = Number(window.thinkletReinforcementState.score || 0);
            let streak = 0;
            let pursuingBonus = false;
            let bonusWon = false;
            let checkpointOffered = false;
            let voluntaryDecision = null;
            let currentEntry = null;

            function format(template, marker, value) {
                return String(template || '').replace(marker, String(value));
            }

            function updateScore() {
                window.thinkletReinforcementState.score = score;
                scoreElement.textContent = settings.strings.score + ' : ' + score +
                    ' · ' + settings.strings.streak + ' : ' + streak;
            }

            function playSuccessSound() {
                if (!settings.enablesound || !window.AudioContext) {
                    return;
                }
                const context = new window.AudioContext();
                const oscillator = context.createOscillator();
                const gain = context.createGain();
                oscillator.frequency.value = 660;
                gain.gain.setValueAtTime(0.035, context.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, context.currentTime + 0.12);
                oscillator.connect(gain);
                gain.connect(context.destination);
                oscillator.start();
                oscillator.stop(context.currentTime + 0.12);
            }

            function makeButton(label, className, handler) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = className;
                button.textContent = label;
                button.addEventListener('click', handler);
                return button;
            }

            function finish(decision) {
                const finalDecision = decision === 'complete' && voluntaryDecision
                    ? voluntaryDecision : decision;
                activity.dataset.decision = finalDecision;
                itemElement.replaceChildren();
                feedbackElement.textContent = settings.strings.finished;
                actionsElement.replaceChildren();

                activity.dispatchEvent(new CustomEvent('thinklet:reinforcementdecision', {
                    bubbles: true,
                    detail: {decision: finalDecision, score: score, streak: streak},
                }));

                if (!settings.islastblock) {
                    actionsElement.appendChild(makeButton(
                        settings.nextbuttontext,
                        'btn btn-primary mt-3',
                        function() {
                            if (block) {
                                const skip = finalDecision === 'stop'
                                    ? Number(settings.stopskip || 0) : 0;
                                showFollowingBlock(block, skip);
                            }
                        }
                    ));
                }
            }

            function offerChoice() {
                itemElement.replaceChildren();
                feedbackElement.replaceChildren();
                actionsElement.replaceChildren();

                const streakMessage = document.createElement('strong');
                streakMessage.textContent = '🔥 ' + format(
                    settings.strings.streakmessage,
                    '__STREAK__',
                    streak
                );
                feedbackElement.appendChild(streakMessage);
                feedbackElement.appendChild(document.createElement('br'));
                const remainingForBonus = Math.max(
                    1,
                    Number(settings.bonustarget) - streak
                );
                const remainingMessage = remainingForBonus === 1
                    ? settings.strings.onemore
                    : format(settings.strings.moreforbonus, '__COUNT__', remainingForBonus);
                feedbackElement.appendChild(document.createTextNode(remainingMessage));
                feedbackElement.appendChild(document.createElement('br'));
                const bonusMessage = document.createElement('strong');
                bonusMessage.textContent = format(
                    settings.strings.bonusmessage,
                    '__BONUS__',
                    settings.bonuspoints
                );
                feedbackElement.appendChild(bonusMessage);

                actionsElement.appendChild(makeButton(
                    settings.continuebuttontext,
                    'btn btn-primary mt-3 me-2',
                    function() {
                        voluntaryDecision = 'continue';
                        activity.dataset.decision = voluntaryDecision;
                        checkpointOffered = true;
                        pursuingBonus = true;
                        ensureReviewItems(remainingForBonus);
                        showItem();
                    }
                ));
                actionsElement.appendChild(makeButton(
                    settings.stopbuttontext,
                    'btn btn-outline-secondary mt-3',
                    function() {
                        finish('stop');
                    }
                ));
            }

            function answer(choiceIndex) {
                const item = currentEntry.item;
                const buttons = itemElement.querySelectorAll('button');
                buttons.forEach(function(button) {
                    button.disabled = true;
                });

                if (choiceIndex === Number(item.correct)) {
                    score += Number(settings.correctpoints);
                    streak += 1;
                    let message = '✓ ' + format(
                        settings.strings.correct,
                        '__POINTS__',
                        settings.correctpoints
                    );
                    if (pursuingBonus && streak >= Number(settings.bonustarget)) {
                        score += Number(settings.bonuspoints);
                        message += ' · ' + format(
                            settings.strings.bonuswon,
                            '__BONUS__',
                            settings.bonuspoints
                        );
                        pursuingBonus = false;
                        bonusWon = true;
                        queue.length = 0;
                    }
                    feedbackElement.textContent = message;
                    feedbackElement.className = 'thinklet-reinforcement-feedback is-correct';
                    playSuccessSound();
                } else {
                    streak = 0;
                    const correctAnswer = settings.mode === 'shortanswer'
                        ? ((item.answers || [])[0] || '')
                        : (Array.isArray(item.choices)
                            ? item.choices[Number(item.correct)] : '');
                    feedbackElement.textContent = '✗ ' + settings.strings.incorrect + ' ' +
                        format(settings.strings.answerwas, '__ANSWER__', correctAnswer);
                    feedbackElement.className = 'thinklet-reinforcement-feedback is-incorrect';
                    const insertionPoint = Math.min(Number(settings.retrygap), queue.length);
                    queue.splice(insertionPoint, 0, currentEntry);
                }

                updateScore();
                window.setTimeout(function() {
                    if (!checkpointOffered && (
                        streak >= Number(settings.bonustarget) - 1 || queue.length === 0
                    )) {
                        offerChoice();
                    } else if (queue.length === 0 && pursuingBonus && !bonusWon) {
                        ensureReviewItems(Math.max(1, Number(settings.bonustarget) - streak));
                        showItem();
                    } else if (queue.length === 0) {
                        finish('complete');
                    } else {
                        showItem();
                    }
                }, 1100);
            }

            function ensureReviewItems(count) {
                if (initialEntries.length === 0) {
                    return;
                }
                const missing = Math.max(0, count - queue.length);
                for (let index = 0; index < missing; index += 1) {
                    queue.push(initialEntries[index % initialEntries.length]);
                }
            }

            function normalizeAnswer(value) {
                return String(value || '')
                    .trim()
                    .toLocaleLowerCase()
                    .replace(/\s+/g, ' ');
            }

            function answerShort(value) {
                const accepted = Array.isArray(currentEntry.item.answers)
                    ? currentEntry.item.answers.map(normalizeAnswer) : [];
                const answerIndex = accepted.indexOf(normalizeAnswer(value));
                answer(answerIndex >= 0 ? Number(currentEntry.item.correct || 0) : -1);
            }

            function showItem() {
                if (queue.length === 0) {
                    finish('complete');
                    return;
                }

                currentEntry = queue.shift();
                const item = currentEntry.item;
                itemElement.replaceChildren();
                feedbackElement.replaceChildren();
                feedbackElement.className = 'thinklet-reinforcement-feedback';
                actionsElement.replaceChildren();

                if (item.imageurl) {
                    const image = document.createElement('img');
                    image.src = item.imageurl;
                    image.alt = item.prompt || '';
                    image.className = 'thinklet-reinforcement-image';
                    itemElement.appendChild(image);
                }

                const prompt = document.createElement('p');
                prompt.className = 'thinklet-reinforcement-prompt';
                prompt.textContent = item.prompt;
                itemElement.appendChild(prompt);

                if (settings.mode === 'shortanswer') {
                    const answerGroup = document.createElement('div');
                    answerGroup.className = 'thinklet-reinforcement-shortanswer';
                    const input = document.createElement('input');
                    input.type = 'text';
                    input.className = 'form-control';
                    input.placeholder = settings.strings.shortplaceholder;
                    const validate = makeButton(
                        settings.strings.validate,
                        'btn btn-primary mt-2',
                        function() {
                            if (input.value.trim() !== '') {
                                input.disabled = true;
                                validate.disabled = true;
                                answerShort(input.value);
                            }
                        }
                    );
                    input.addEventListener('keydown', function(event) {
                        if (event.key === 'Enter') {
                            event.preventDefault();
                            validate.click();
                        }
                    });
                    answerGroup.appendChild(input);
                    answerGroup.appendChild(validate);
                    itemElement.appendChild(answerGroup);
                    input.focus();
                } else {
                    const choices = document.createElement('div');
                    choices.className = 'thinklet-reinforcement-choices';
                    (item.choices || []).forEach(function(choice, index) {
                        choices.appendChild(makeButton(
                            choice,
                            'btn btn-outline-primary thinklet-reinforcement-choice',
                            function() {
                                answer(index);
                            }
                        ));
                    });
                    itemElement.appendChild(choices);
                }
            }

            if (block) {
                block.addEventListener('thinklet:blockshown', function() {
                    score = Number(window.thinkletReinforcementState.score || 0);
                    updateScore();
                });
            }
            updateScore();
            showItem();
        });

    document
        .querySelectorAll('.thinklet-short-answer')
        .forEach(function(input) {

            const button =
                document.getElementById(input.dataset.button);

            function updateRocButtonState() {

                if (!button) {
                    return;
                }

                if (input.value.trim().length > 0) {
                    button.removeAttribute('hidden');
                } else {
                    button.setAttribute('hidden', 'hidden');
                }
            }

            input.addEventListener(
                'input',
                updateRocButtonState
            );

            updateRocButtonState();
        });

    document
        .querySelectorAll('.thinklet-open-answer')
        .forEach(function(textarea) {

            const threshold =
                parseInt(textarea.dataset.threshold || 120);

            const button =
                document.getElementById(textarea.dataset.button);

            const counter =
                document.getElementById(textarea.dataset.counter);

            const initialText =
                textarea.dataset.initialText || '';

            const hasInitialText =
                initialText.trim().length > 0;

            function updateOpenAnswerState() {

                const count =
                    textarea.value.trim().length;

                /*
                 * Sans texte initial :
                 * comportement habituel avec seuil de caractères.
                 *
                 * Avec texte initial :
                 * on indique simplement que le texte doit être modifié.
                 */
                if (counter) {
                    if (hasInitialText) {
                        counter.textContent =
                            textarea.value !== initialText
                                ? ''
                                : '';
                    } else {
                        counter.textContent =
                            count +
                            ' / ' +
                            threshold +
                            ' ' +
                            minimumCharactersText;
                    }
                }

                if (button) {

                    if (hasInitialText) {

                        /*
                         * Le bouton apparaît dès que le texte
                         * diffère du texte proposé au départ.
                         */
                        if (textarea.value !== initialText) {
                            button.removeAttribute('hidden');
                        } else {
                            button.setAttribute('hidden', 'hidden');
                        }

                    } else {

                        /*
                         * Fonctionnement normal :
                         * apparition lorsque le seuil est atteint.
                         */
                        if (count >= threshold) {
                            button.removeAttribute('hidden');
                        } else {
                            button.setAttribute('hidden', 'hidden');
                        }
                    }
                }
            }

            textarea.addEventListener(
                'input',
                updateOpenAnswerState
            );

            updateOpenAnswerState();
        });
});
</script>

<?php

echo $OUTPUT->footer();
