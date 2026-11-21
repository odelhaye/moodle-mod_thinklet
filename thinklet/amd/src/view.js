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
 * Initialises Thinklet block navigation, feedback and activity completion.
 *
 * @module     mod_thinklet/view
 * @copyright  2026 Olivier Delhaye
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @param {Object} options Page configuration.
 */
export const init = ({
    minimumCharactersText,
    previewExpanded,
    previewBlockId,
    courseModuleId,
    sessionKey,
    completionUrl,
    completionError,
}) => {
    if (previewExpanded) {
        document.querySelectorAll('.thinklet-feedback').forEach(function(feedback) {
            feedback.removeAttribute('hidden');
        });
    }

    if (previewBlockId) {
        const previewBlock = document.getElementById('thinklet-block-' + previewBlockId);
        if (previewBlock) {
            window.addEventListener('load', function() {
                previewBlock.scrollIntoView({behavior: 'auto', block: 'start'});
            });
        }
    }

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

    // Only the final presentation block's explicit return-to-course action finishes the module.
    const lastBlock = Array.from(document.querySelectorAll('.thinklet-sequential-block')).pop();
    const lastLink = lastBlock ? lastBlock.querySelector('a.thinklet-return-course') : null;
    const trackCompletion = lastLink && lastLink.dataset.completion === '1';
    if (trackCompletion && lastLink) {
        lastLink.addEventListener('click', async function(event) {
            // Respect Ctrl/Command clicks and other non-standard navigation.
            if (event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) {
                return;
            }
            event.preventDefault();
            const target = lastLink.href;
            lastLink.setAttribute('aria-disabled', 'true');
            try {
                const form = new URLSearchParams({
                    id: courseModuleId,
                    sesskey: sessionKey
                });
                const response = await fetch(completionUrl, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: form.toString(), credentials: 'same-origin'
                });
                if (!response.ok || !(await response.json()).ok) {
                    throw new Error('Completion failed');
                }
                if (lastLink.target === '_blank') {
                    window.open(target, '_blank', 'noopener');
                } else {
                    window.location.assign(target);
                }
            } catch (error) {
                lastLink.removeAttribute('aria-disabled');
                alert(completionError);
            }
        });
    }

    function showNextBlock(currentBlock) {

        const nextBlock = currentBlock.nextElementSibling;

        if (
            nextBlock &&
            nextBlock.classList.contains('thinklet-sequential-block')
        ) {
            nextBlock.classList.remove('thinklet-hidden-block');
            // Keep existing sequential navigation unchanged.

            nextBlock.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    }

    /*
     * For a Presentation block with an external activity,
     * show the resume button after the link is clicked.
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

                        // Make the resume button the primary action.
                        resumeButton.classList.remove(
                            'btn-secondary',
                            'btn-outline-secondary'
                        );
                        resumeButton.classList.add('btn-primary');
                        resumeButton.removeAttribute('hidden');

                        // Make the external activity button secondary.
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
                parseInt(textarea.dataset.threshold || 40);

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
                 * Without starter text,
                 * apply the usual minimum character count.
                 *
                 * With starter text,
                 * prompt the learner to edit the text.
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
                         * Show the button as soon as the text
                         * differs from the starter text.
                         */
                        if (textarea.value !== initialText) {
                            button.removeAttribute('hidden');
                        } else {
                            button.setAttribute('hidden', 'hidden');
                        }

                    } else {

                        /*
                         * With the default behaviour,
                         * show the button when the minimum character count is reached.
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
};
