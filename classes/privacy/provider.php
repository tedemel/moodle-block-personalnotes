<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Privacy subsystem implementation for block_personalnotes.
 *
 * @package    block_personalnotes
 * @category   privacy
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_personalnotes\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\helper;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider for block_personalnotes.
 *
 * Notes are personal (only visible to the author), so we export and delete
 * them directly rather than delegating to a subsystem.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {

    /**
     * Describe the data stored by this plugin.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'block_personalnotes',
            [
                'userid'       => 'privacy:metadata:block_personalnotes:userid',
                'contextid'    => 'privacy:metadata:block_personalnotes:contextid',
                'notetext'     => 'privacy:metadata:block_personalnotes:notetext',
                'timecreated'  => 'privacy:metadata:block_personalnotes:timecreated',
                'timemodified' => 'privacy:metadata:block_personalnotes:timemodified',
            ],
            'privacy:metadata:block_personalnotes'
        );
        return $collection;
    }

    /**
     * Get the list of contexts that contain user data for the given user.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $contextlist->add_from_sql(
            'SELECT contextid FROM {block_personalnotes} WHERE userid = :userid',
            ['userid' => $userid]
        );
        return $contextlist;
    }

    /**
     * Get the list of users within a specific context.
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        $userlist->add_from_sql(
            'userid',
            'SELECT userid FROM {block_personalnotes} WHERE contextid = :contextid',
            ['contextid' => $context->id]
        );
    }

    /**
     * Export all user data for the given approved contextlist.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            $note = $DB->get_record('block_personalnotes', [
                'userid'    => $userid,
                'contextid' => $context->id,
            ]);
            if ($note) {
                writer::with_context($context)->export_data(
                    [get_string('pluginname', 'block_personalnotes')],
                    (object) [
                        'notetext'     => $note->notetext,
                        'timecreated'  => \core_privacy\local\request\transform::datetime($note->timecreated),
                        'timemodified' => \core_privacy\local\request\transform::datetime($note->timemodified),
                    ]
                );
            }
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        $DB->delete_records('block_personalnotes', ['contextid' => $context->id]);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            $DB->delete_records('block_personalnotes', [
                'userid'    => $userid,
                'contextid' => $context->id,
            ]);
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;
        $context = $userlist->get_context();
        foreach ($userlist->get_userids() as $userid) {
            $DB->delete_records('block_personalnotes', [
                'userid'    => $userid,
                'contextid' => $context->id,
            ]);
        }
    }
}
