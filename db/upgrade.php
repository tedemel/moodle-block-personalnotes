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
 * Upgrade steps for block_personalnotes.
 *
 * @package    block_personalnotes
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_block_personalnotes_upgrade(int $oldversion): bool {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2026041501) {
        $table = new xmldb_table('block_personalnotes');

        // Add tabname field.
        $field = new xmldb_field('tabname', XMLDB_TYPE_CHAR, '255', null, NOTNULL, null, 'Notiz 1', 'contextid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add sortorder field.
        $field = new xmldb_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, NOTNULL, null, '0', 'tabname');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add non-unique index for user+context lookups.
        // (The old unique index mdl_blocpers_usecon_uix was already dropped manually.)
        $index = new xmldb_index('idx_user_context', XMLDB_INDEX_NOTUNIQUE, ['userid', 'contextid']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_block_savepoint(true, 2026041501, 'personalnotes');
    }

    return true;
}
