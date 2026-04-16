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
 * External function: delete a note tab.
 *
 * @package    block_personalnotes
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_personalnotes\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use context;

/**
 * Delete a tab; refuses if it is the last one.
 */
class delete_tab extends external_api {

    /**
     * Describes the parameters for execute.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'tabid' => new external_value(PARAM_INT, 'Note record id (tab) to delete'),
        ]);
    }

    /**
     * Delete a note tab.
     *
     * @param int $tabid Note record id to delete.
     * @return array{success: bool, error: string}
     */
    public static function execute(int $tabid): array {
        global $DB, $USER;

        ['tabid' => $tabid] = self::validate_parameters(
            self::execute_parameters(),
            ['tabid' => $tabid]
        );

        $record = $DB->get_record('block_personalnotes', ['id' => $tabid], '*', MUST_EXIST);
        if ($record->userid != $USER->id) {
            throw new \moodle_exception('accessdenied', 'admin');
        }

        $context = context::instance_by_id($record->contextid, MUST_EXIST);
        self::validate_context($context);
        require_capability('block/personalnotes:addnote', $context);

        // Refuse to delete the last tab.
        $count = $DB->count_records('block_personalnotes', [
            'userid'    => $USER->id,
            'contextid' => $record->contextid,
        ]);
        if ($count <= 1) {
            return ['success' => false, 'error' => 'lasttab'];
        }

        $DB->delete_records('block_personalnotes', ['id' => $tabid]);

        return ['success' => true, 'error' => ''];
    }

    /**
     * Describes the return value for execute.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether deletion succeeded'),
            'error'   => new external_value(PARAM_TEXT, 'Error key if failed'),
        ]);
    }
}
