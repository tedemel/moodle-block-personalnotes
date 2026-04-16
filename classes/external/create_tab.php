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
 * External function: create a new note tab.
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
 * Create a new tab for the current user in a given context.
 */
class create_tab extends external_api {

    /**
     * Describes the parameters for execute.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'contextid' => new external_value(PARAM_INT,  'Moodle context id'),
            'tabname'   => new external_value(PARAM_TEXT, 'Tab label (empty = auto-number)'),
        ]);
    }

    /**
     * Create a new note tab.
     *
     * @param int    $contextid Moodle context id.
     * @param string $tabname   Desired tab name; empty string triggers auto-numbering.
     * @return array{id: int, tabname: string}
     */
    public static function execute(int $contextid, string $tabname): array {
        global $DB, $USER;

        ['contextid' => $contextid, 'tabname' => $tabname] = self::validate_parameters(
            self::execute_parameters(),
            ['contextid' => $contextid, 'tabname' => $tabname]
        );

        $tabname = clean_param(trim($tabname), PARAM_TEXT);

        $context = context::instance_by_id($contextid, MUST_EXIST);
        self::validate_context($context);
        require_capability('block/personalnotes:addnote', $context);

        // Determine next sortorder = number of existing tabs + 1.
        $count     = $DB->count_records('block_personalnotes', ['userid' => $USER->id, 'contextid' => $contextid]);
        $sortorder = $count; // 0-based sortorder, so count gives us the next slot.
        $tabnumber = $count + 1;

        // Auto-generate name with incrementing number if caller sent empty string.
        if ($tabname === '') {
            $base    = get_string('defaulttabnamebase', 'block_personalnotes');
            $tabname = $base . ' ' . $tabnumber;
        }

        $now = time();
        $id  = $DB->insert_record('block_personalnotes', (object)[
            'userid'       => $USER->id,
            'contextid'    => $contextid,
            'tabname'      => $tabname,
            'sortorder'    => $sortorder,
            'notetext'     => '',
            'timecreated'  => $now,
            'timemodified' => $now,
        ]);

        return ['id' => $id, 'tabname' => $tabname];
    }

    /**
     * Describes the return value for execute.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'id'      => new external_value(PARAM_INT,  'New tab record id'),
            'tabname' => new external_value(PARAM_TEXT, 'Tab label'),
        ]);
    }
}
