<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

defined('MOODLE_INTERNAL') || die();

function thinklet_supports($feature) {

    switch($feature) {

        case FEATURE_MOD_INTRO:
            return true;

        case FEATURE_SHOW_DESCRIPTION:
            return true;

        case FEATURE_BACKUP_MOODLE2:
            return true;

        default:
            return null;
    }
}

function thinklet_add_instance($data, $mform = null) {

    global $DB;

    $data->timecreated = time();
    $data->timemodified = time();

    return $DB->insert_record('thinklet', $data);
}

function thinklet_update_instance($data, $mform = null) {

    global $DB;

    $data->timemodified = time();
    $data->id = $data->instance;

    return $DB->update_record('thinklet', $data);
}

function thinklet_delete_instance($id) {

    global $DB;

    if (!$thinklet = $DB->get_record('thinklet', ['id' => $id])) {
        return false;
    }

    $DB->delete_records('thinklet_blocks', ['thinkletid' => $thinklet->id]);
    $DB->delete_records('thinklet', ['id' => $thinklet->id]);

    return true;
}

function mod_thinklet_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $DB;

    if ($context->contextlevel !== CONTEXT_MODULE) {
        return false;
    }

    require_login($course, true, $cm);

    if (!in_array($filearea, ['blockcontent', 'blocklead', 'blockfeedback'], true)) {
        return false;
    }

    if (empty($args)) {
        return false;
    }

    $itemid = (int)array_shift($args);

    if (!$block = $DB->get_record('thinklet_blocks', ['id' => $itemid])) {
        return false;
    }

    if ((int)$block->thinkletid !== (int)$cm->instance) {
        return false;
    }

    $filename = array_pop($args);
    if (!$filename) {
        return false;
    }

    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'mod_thinklet', $filearea, $itemid, $filepath, $filename);

    if (!$file || $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}
