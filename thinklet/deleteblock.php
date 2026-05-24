<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

require('../../config.php');

$id = required_param('id', PARAM_INT); // Course module id.
$blockid = required_param('blockid', PARAM_INT);

require_sesskey();

$cm = get_coursemodule_from_id('thinklet', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$thinklet = $DB->get_record('thinklet', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/thinklet:manageblocks', $context);

$block = $DB->get_record(
    'thinklet_blocks',
    ['id' => $blockid, 'thinkletid' => $thinklet->id],
    '*',
    MUST_EXIST
);

// Supprimer les fichiers intégrés à ce bloc, le cas échéant.
$fs = get_file_storage();
$fs->delete_area_files($context->id, 'mod_thinklet', 'blockcontent', $block->id);
$fs->delete_area_files($context->id, 'mod_thinklet', 'blocklead', $block->id);
$fs->delete_area_files($context->id, 'mod_thinklet', 'blockfeedback', $block->id);

// Supprimer le bloc.
$DB->delete_records('thinklet_blocks', ['id' => $block->id]);

// Réordonner les blocs restants pour éviter les trous dans sortorder.
$blocks = $DB->get_records(
    'thinklet_blocks',
    ['thinkletid' => $thinklet->id],
    'sortorder ASC, id ASC'
);

$sortorder = 1;
foreach ($blocks as $remainingblock) {
    if ((int)$remainingblock->sortorder !== $sortorder) {
        $remainingblock->sortorder = $sortorder;
        $DB->update_record('thinklet_blocks', $remainingblock);
    }
    $sortorder++;
}

redirect(new moodle_url('/mod/thinklet/edit.php', ['id' => $cm->id]));
