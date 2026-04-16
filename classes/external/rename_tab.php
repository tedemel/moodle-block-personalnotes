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
 * External function: rename an existing note tab.
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
 * Rename a tab (double-click on tab label).
 */
class rename_tab extends external_api {

    /**
     * Describes the parameters for execute.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'tabid'   => new external_value(PARAM_INT,  'Note record id (tab)'),
            'tabname' => new external_value(PARAM_TEXT, 'New tab label'),
        ]);
    }

    /**
     * Rename a note tab.
     *
     * @param int    $tabid   Note record id.
     * @param string $tabname New label; falls back to default name if empty.
     * @return array{success: bool, tabname: string}
     */
    public static function execute(int $tabid, string $tabname): array {
        global $DB, $USER;

        ['tabid' => $tabid, 'tabname' => $tabname] = self::validate_parameters(
            self::execute_parameters(),
            ['tabid' => $tabid, 'tabname' => $tabname]
        );

        $tabname = clean_param(trim($tabname), PARAM_TEXT);
        if ($tabname === '') {
            $tabname = get_string('defaulttabname', 'block_personalnotes');
        }

        $record = $DB->get_record('block_personalnotes', ['id' => $tabid], '*', MUST_EXIST);
        if ($record->userid != $USER->id) {
            throw new \moodle_exception('accessdenied', 'admin');
        }

        $context = context::instance_by_id($record->contextid, MUST_EXIST);
        self::validate_context($context);
        require_capability('block/personalnotes:addnote', $context);

        $DB->update_record('block_personalnotes', (object)[
            'id'           => $tabid,
            'tabname'      => $tabname,
            'timemodified' => time(),
        ]);

        return ['success' => true, 'tabname' => $tabname];
    }

    /**
     * Describes the return value for execute.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether rename succeeded'),
            'tabname' => new external_value(PARAM_TEXT, 'Saved tab label'),
        ]);
    }
}
