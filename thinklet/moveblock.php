<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

require('../../config.php');

$id = required_param('id', PARAM_INT);
$blockid = required_param('blockid', PARAM_INT);
$direction = required_param('direction', PARAM_ALPHA);
if (!in_array($direction, ['up', 'down'], true)) {
    throw new moodle_exception('invaliddirection', 'thinklet');
}

$cm = get_coursemodule_from_id('thinklet', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$thinklet = $DB->get_record('thinklet', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
require_sesskey();

$context = context_module::instance($cm->id);
require_capability('mod/thinklet:manageblocks', $context);

$blocks = array_values($DB->get_records(
    'thinklet_blocks',
    ['thinkletid' => $thinklet->id],
    'sortorder ASC, id ASC'
));

$currentindex = null;

foreach ($blocks as $index => $block) {
    if ((int)$block->id === (int)$blockid) {
        $currentindex = $index;
        break;
    }
}

if ($currentindex !== null) {
    $targetindex = null;

    if ($direction === 'up' && $currentindex > 0) {
        $targetindex = $currentindex - 1;
    }

    if ($direction === 'down' && $currentindex < count($blocks) - 1) {
        $targetindex = $currentindex + 1;
    }

    if ($targetindex !== null) {
        $current = $blocks[$currentindex];
        $target = $blocks[$targetindex];

        $oldorder = $current->sortorder;
        $current->sortorder = $target->sortorder;
        $target->sortorder = $oldorder;

        $DB->update_record('thinklet_blocks', $current);
        $DB->update_record('thinklet_blocks', $target);
    }
}

redirect(new moodle_url('/mod/thinklet/edit.php', ['id' => $cm->id]));