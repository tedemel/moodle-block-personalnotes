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
 * External functions and web-service definitions for block_personalnotes.
 *
 * @package    block_personalnotes
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'block_personalnotes_save_note' => [
        'classname'     => \block_personalnotes\external\save_note::class,
        'methodname'    => 'execute',
        'description'   => 'Save note content for a specific tab.',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
    ],
    'block_personalnotes_create_tab' => [
        'classname'     => \block_personalnotes\external\create_tab::class,
        'methodname'    => 'execute',
        'description'   => 'Create a new note tab for the current user in a context.',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
    ],
    'block_personalnotes_rename_tab' => [
        'classname'     => \block_personalnotes\external\rename_tab::class,
        'methodname'    => 'execute',
        'description'   => 'Rename an existing note tab.',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
    ],
    'block_personalnotes_delete_tab' => [
        'classname'     => \block_personalnotes\external\delete_tab::class,
        'methodname'    => 'execute',
        'description'   => 'Delete a note tab (refuses if it is the last one).',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
    ],
];
