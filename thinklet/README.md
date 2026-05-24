# Thinklet

Thinklet is a Moodle activity plugin for creating short reflective learning sequences.

A Thinklet is not designed as a graded quiz. It is a compact pedagogical sequence made of blocks such as a stimulus, a reflective multiple-choice question, a short answer, an open response, a transition, or a reveal block. Its purpose is to prompt thinking, hesitation, comparison, reformulation, and metacognitive reflection.

## Moodle component

`mod_thinklet`

## Status

Beta. The plugin has been tested manually on Moodle 5 by two users, but it should still be reviewed carefully before production use.

## Main features

- Moodle activity module.
- Rich-text editable blocks.
- Embedded files through Moodle file areas.
- Sequential learner experience.
- Reflective feedback blocks.
- No grading and no stored learner responses.
- English and French language files.
- Privacy API declaration.
- Basic backup and restore support.

## Installation

1. Copy the `thinklet` directory into Moodle’s `mod/` directory.
2. Visit the Moodle administration notifications page.
3. Complete the plugin installation process.
4. Add a Thinklet activity to a course.

## Pedagogical intention

Thinklet is intended for small reflective learning moments: confronting a misleading rule, commenting on an AI-generated explanation, reformulating a concept, or moving from a first intuitive answer to a more nuanced understanding.

## Privacy

Thinklet does not store learner responses. Learner interactions are handled in the browser and are not persisted by the plugin.

## License

GNU GPL v3 or later.
