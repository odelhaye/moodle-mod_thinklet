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

                echo html_writer::empty_tag('input', [
                    'type' => 'checkbox',
                    'id' => $inputid,
                    'class' => 'thinklet-choice-input'
                ]);

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
                    get_string('continue', 'thinklet'),
                    [
                        'type' => 'button',
                        'class' => 'btn btn-primary mt-3 thinklet-next-button thinklet-qcm-next-button',
                        'data-after-feedback' => 'thinklet-feedback-' . $block->id,
                        'hidden' => 'hidden'
                    ]
                );
            }

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
                    get_string('continue', 'thinklet'),
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
                    get_string('continue', 'thinklet'),
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
                    get_string('continue', 'thinklet'),
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

    function showNextBlock(currentBlock) {

        const nextBlock = currentBlock.nextElementSibling;

        if (
            nextBlock &&
            nextBlock.classList.contains('thinklet-sequential-block')
        ) {
            nextBlock.classList.remove('thinklet-hidden-block');

            nextBlock.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
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
