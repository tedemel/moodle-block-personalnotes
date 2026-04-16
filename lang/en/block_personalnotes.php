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
 * English language strings for block_personalnotes.
 *
 * @package    block_personalnotes
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname']     = 'Personal Notes';
$string['pluginname_help'] = 'Allows you to write private notes for each course page or activity. Only you can see your notes.';

// Block UI.
$string['placeholder']    = 'Write a private note…';
$string['toolbar']        = 'Formatting toolbar';
$string['cmd_bold']       = 'Bold';
$string['cmd_bullets']    = 'Bullet list';
$string['viewnotes']      = 'View all my notes in this course';
$string['saving']         = 'Saving…';
$string['saved']          = 'Saved';
// Tabs.
$string['defaulttabname']     = 'Note 1';
$string['defaulttabnamebase'] = 'Note';
$string['addtab']         = 'New tab';
$string['deletetab']      = 'Delete tab';
$string['confirmdelete']  = 'Really delete this tab and its note?';

// View page.
$string['backtocourse']   = 'Back to course';
$string['exportnotes']    = 'Export as';
$string['printnotes']     = 'Print / Save as PDF';
$string['search']         = 'Search';
$string['datefrom']       = 'From date';
$string['dateto']         = 'To date';
$string['filter']         = 'Filter';
$string['resetfilter']    = 'Reset filter';
$string['noresults']      = 'No notes match the current filter.';
$string['nonotes']        = 'You have no notes in this course yet.';
$string['unknowncontext'] = 'Unknown page';

// Capabilities.
$string['block_personalnotes:addinstance']   = 'Add a Personal Notes block';
$string['block_personalnotes:myaddinstance'] = 'Add a Personal Notes block to My Dashboard';
$string['block_personalnotes:addnote']       = 'Write personal notes';
$string['block_personalnotes:viewnotes']     = 'View personal notes list';

// Privacy.
$string['privacy:metadata:block_personalnotes']              = 'The Personal Notes block stores private notes written by users.';
$string['privacy:metadata:block_personalnotes:userid']       = 'The ID of the user who wrote the note.';
$string['privacy:metadata:block_personalnotes:contextid']    = 'The Moodle context (course or activity) the note belongs to.';
$string['privacy:metadata:block_personalnotes:notetext']     = 'The text content of the note.';
$string['privacy:metadata:block_personalnotes:timecreated']  = 'The time the note was first created.';
$string['privacy:metadata:block_personalnotes:timemodified'] = 'The time the note was last modified.';
