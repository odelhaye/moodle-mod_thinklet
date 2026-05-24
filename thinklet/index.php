<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

require('../../config.php');

$id = required_param('id', PARAM_INT);

$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);
require_course_login($course);

$PAGE->set_url('/mod/thinklet/index.php', ['id' => $id]);
$PAGE->set_title(get_string('modulenameplural', 'thinklet'));
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('incourse');

$modinfo = get_fast_modinfo($course);
$instances = $modinfo->get_instances_of('thinklet');

 echo $OUTPUT->header();
 echo $OUTPUT->heading(get_string('modulenameplural', 'thinklet'));

if (empty($instances)) {
    echo $OUTPUT->notification(get_string('noinstances', 'thinklet'), 'info');
    echo $OUTPUT->footer();
    exit;
}

$table = new html_table();
$table->head = [get_string('name')];

foreach ($instances as $cm) {
    if (!$cm->uservisible) {
        continue;
    }
    $link = html_writer::link(new moodle_url('/mod/thinklet/view.php', ['id' => $cm->id]), format_string($cm->name));
    $table->data[] = [$link];
}

 echo html_writer::table($table);
 echo $OUTPUT->footer();
