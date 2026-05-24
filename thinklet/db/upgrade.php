<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade steps for mod_thinklet.
 *
 * @param int $oldversion The currently installed version.
 * @return bool
 */
function xmldb_thinklet_upgrade($oldversion) {
    global $DB;

    if ($oldversion < 2026052405) {
        // Allow guests to view Thinklet activities when the course itself allows guest access.
        // This makes the behaviour consistent with ordinary visible resources/activities.
        $systemcontext = context_system::instance();
        $guestroles = get_archetype_roles('guest');
        foreach ($guestroles as $guestrole) {
            assign_capability('mod/thinklet:view', CAP_ALLOW, $guestrole->id, $systemcontext->id, true);
        }

        upgrade_mod_savepoint(true, 2026052405, 'thinklet');
    }

    return true;
}
