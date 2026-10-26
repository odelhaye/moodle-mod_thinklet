<?php
// This file is part of Moodle - http://moodle.org/
namespace mod_thinklet\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use core_privacy\local\request\transform;

/**
 * Moodle privacy API support for Thinklet completion dates.
 * No learner responses are persisted.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('thinklet_completed', [
            'userid' => 'privacy:completion:userid',
            'thinkletid' => 'privacy:completion:thinkletid',
            'timecompleted' => 'privacy:completion:timecompleted',
        ], 'privacy:completion');
        return $collection;
    }

    public static function get_contexts_for_userid(int $userid): contextlist {
        $contexts = new contextlist();
        $sql = 'SELECT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid
                  JOIN {modules} m ON m.id = cm.module
                  JOIN {thinklet_completed} tc ON tc.thinkletid = cm.instance
                 WHERE ctx.contextlevel = :level AND m.name = :modname AND tc.userid = :userid';
        $contexts->add_from_sql($sql, ['level' => CONTEXT_MODULE,
            'modname' => 'thinklet', 'userid' => $userid]);
        return $contexts;
    }

    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if ($context->contextlevel !== CONTEXT_MODULE) {
            return;
        }
        $sql = 'SELECT tc.userid FROM {thinklet_completed} tc
                  JOIN {course_modules} cm ON cm.instance = tc.thinkletid
                  JOIN {modules} m ON m.id = cm.module
                 WHERE cm.id = :cmid AND m.name = :modname';
        $userlist->add_from_sql('userid', $sql, ['cmid' => $context->instanceid,
            'modname' => 'thinklet']);
    }

    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_MODULE) {
                continue;
            }
            $sql = 'SELECT tc.* FROM {thinklet_completed} tc
                      JOIN {course_modules} cm ON cm.instance = tc.thinkletid
                      JOIN {modules} m ON m.id = cm.module
                     WHERE cm.id = :cmid AND m.name = :modname AND tc.userid = :userid';
            $records = $DB->get_records_sql($sql, ['cmid' => $context->instanceid,
                'modname' => 'thinklet', 'userid' => $userid]);
            foreach ($records as $record) {
                writer::with_context($context)->export_data(['Thinklet'], (object)[
                    'timecompleted' => transform::datetime($record->timecompleted),
                ]);
            }
        }
    }

    public static function delete_data_for_all_users_in_context(\context $context) {
        if ($context->contextlevel !== CONTEXT_MODULE) {
            return;
        }
        global $DB;
        $cm = get_coursemodule_from_id('thinklet', $context->instanceid);
        if ($cm) {
            $DB->delete_records('thinklet_completed', ['thinkletid' => $cm->instance]);
        }
    }

    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_MODULE) {
                continue;
            }
            $cm = get_coursemodule_from_id('thinklet', $context->instanceid);
            if ($cm) {
                $DB->delete_records('thinklet_completed', [
                    'thinkletid' => $cm->instance, 'userid' => $userid]);
            }
        }
    }

    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;
        $context = $userlist->get_context();
        if ($context->contextlevel !== CONTEXT_MODULE) {
            return;
        }
        $cm = get_coursemodule_from_id('thinklet', $context->instanceid);
        if (!$cm) {
            return;
        }
        $userids = $userlist->get_userids();
        if (!$userids) {
            return;
        }
        list($insql, $params) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $params['thinkletid'] = $cm->instance;
        $DB->delete_records_select('thinklet_completed',
            "thinkletid = :thinkletid AND userid $insql", $params);
    }
}
