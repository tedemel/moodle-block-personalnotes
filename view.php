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
 * Course-level overview of all personal notes for the current user.
 *
 * @package    block_personalnotes
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/accesslib.php');

$courseid = required_param('courseid', PARAM_INT);
$q        = optional_param('q',        '', PARAM_TEXT);
$datefrom = optional_param('datefrom', '', PARAM_TEXT);
$dateto   = optional_param('dateto',   '', PARAM_TEXT);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

require_login($course);
$coursecontext = context_course::instance($courseid);
require_capability('block/personalnotes:viewnotes', $coursecontext);

$PAGE->set_url(new moodle_url('/blocks/personalnotes/view.php', ['courseid' => $courseid]));
$PAGE->set_context($coursecontext);
$PAGE->set_course($course);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('viewnotes', 'block_personalnotes'));
$PAGE->set_heading($course->fullname);

// Convert date filter strings to timestamps.
$tsFrom = 0;
$tsTo   = 0;
if ($datefrom !== '') {
    $tsFrom = mktime(0, 0, 0, ...array_map('intval', explode('-', $datefrom)));
}
if ($dateto !== '') {
    $tsTo = mktime(23, 59, 59, ...array_map('intval', explode('-', $dateto)));
}

// Collect all context ids for this course (course + modules).
$contextids = [$coursecontext->id];
$modinfos   = get_fast_modinfo($course);
foreach ($modinfos->get_cms() as $cm) {
    $contextids[] = context_module::instance($cm->id)->id;
}

if (empty($contextids)) {
    $notes = [];
} else {
    [$insql, $inparams] = $DB->get_in_or_equal($contextids, SQL_PARAMS_NAMED);
    $inparams['userid'] = $USER->id;
    $notes = $DB->get_records_select(
        'block_personalnotes',
        "userid = :userid AND contextid $insql",
        $inparams,
        'contextid ASC, sortorder ASC, id ASC'
    );
}

// Enrich and filter notes.
$notedata = [];
$qlower   = mb_strtolower(trim($q));

foreach ($notes as $note) {
    if (empty(trim(strip_tags($note->notetext)))) {
        continue;
    }

    // Date filter.
    if ($tsFrom && $note->timemodified < $tsFrom) {
        continue;
    }
    if ($tsTo && $note->timemodified > $tsTo) {
        continue;
    }

    $ctx  = context::instance_by_id($note->contextid, IGNORE_MISSING);
    $name = $ctx ? $ctx->get_context_name(false, true) : get_string('unknowncontext', 'block_personalnotes');

    // Keyword filter (searches plain text of note + context name + tab name).
    if ($qlower !== '') {
        $plaintext = mb_strtolower(strip_tags($note->notetext));
        $plainname = mb_strtolower($name);
        $plaintab  = mb_strtolower($note->tabname ?? '');
        if (strpos($plaintext, $qlower) === false
                && strpos($plainname, $qlower) === false
                && strpos($plaintab,  $qlower) === false) {
            continue;
        }
    }

    $modname = '';
    if ($ctx && $ctx->contextlevel == CONTEXT_MODULE) {
        $cm = $modinfos->get_cm($ctx->instanceid);
        $modname = $cm ? $cm->modname : '';
    }

    $notedata[] = [
        'contextname'  => $name,
        'tabname'      => s($note->tabname ?? ''),
        'modname'      => $modname,
        'notetext'     => format_text($note->notetext, FORMAT_HTML),
        'timemodified' => userdate($note->timemodified),
        'contextid'    => $note->contextid,
    ];
}

$exporturl = new moodle_url('/blocks/personalnotes/export.php', ['courseid' => $courseid]);
$formurl   = new moodle_url('/blocks/personalnotes/view.php',   ['courseid' => $courseid]);

$templatecontext = [
    'coursetitle'   => $course->fullname,
    'notes'         => array_values($notedata),
    'hasnotes'      => !empty($notedata),
    'exporturl'     => $exporturl->out(false),
    'sesskey'       => sesskey(),
    'formurl'       => $formurl->out(false),
    'q'             => s($q),
    'datefrom'      => s($datefrom),
    'dateto'        => s($dateto),
    'isfiltered'    => ($q !== '' || $datefrom !== '' || $dateto !== ''),
    'backurl'       => (new moodle_url('/course/view.php', ['id' => $courseid]))->out(false),
    'strexport'     => get_string('exportnotes',   'block_personalnotes'),
    'strprint'      => get_string('printnotes',    'block_personalnotes'),
    'strback'       => get_string('backtocourse',  'block_personalnotes'),
    'strnonotes'    => get_string('nonotes',       'block_personalnotes'),
    'strnoresults'  => get_string('noresults',     'block_personalnotes'),
    'strsearch'     => get_string('search',        'block_personalnotes'),
    'strfrom'       => get_string('datefrom',      'block_personalnotes'),
    'strto'         => get_string('dateto',        'block_personalnotes'),
    'strreset'      => get_string('resetfilter',   'block_personalnotes'),
    'strfilter'     => get_string('filter',        'block_personalnotes'),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('block_personalnotes/view', $templatecontext);
echo $OUTPUT->footer();
