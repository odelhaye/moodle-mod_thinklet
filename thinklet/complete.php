<?php
// Register explicit learner completion; no answer content is submitted.
require('../../config.php');
require_once($CFG->libdir . '/completionlib.php');

$id = required_param('id', PARAM_INT);
require_sesskey();
$cm = get_coursemodule_from_id('thinklet', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/thinklet:view', $context);
if (isguestuser() || !is_enrolled($context, $USER->id)) {
    throw new moodle_exception('nopermissions', 'error');
}

if (!$DB->record_exists('thinklet_completed', ['thinkletid' => $cm->instance, 'userid' => $USER->id])) {
    $DB->insert_record('thinklet_completed', (object)[
        'thinkletid' => $cm->instance,
        'userid' => $USER->id,
        'timecompleted' => time(),
    ]);
}
$completion = new completion_info($course);
if ($completion->is_enabled($cm) == COMPLETION_TRACKING_AUTOMATIC) {
    $completion->update_state($cm, COMPLETION_COMPLETE, $USER->id);
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['ok' => true]);
