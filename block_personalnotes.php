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
 * Block definition for block_personalnotes.
 *
 * @package    block_personalnotes
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Personal Notes block class.
 */
class block_personalnotes extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_personalnotes');
    }

    public function applicable_formats() {
        return [
            'all'         => false,
            'course'      => true,
            'course-view' => true,
            'mod'         => true,
        ];
    }

    public function instance_allow_multiple() {
        return false;
    }

    public function has_config() {
        return false;
    }

    public function get_content() {
        global $USER, $DB, $OUTPUT, $PAGE;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content         = new stdClass();
        $this->content->footer = '';

        if (!isloggedin() || isguestuser()) {
            $this->content->text = '';
            return $this->content;
        }

        $contextid = $this->page->context->id;
        $courseid  = $this->page->course->id;

        // Load all tabs for this user + context, ordered by sortorder.
        $tabs = $DB->get_records('block_personalnotes',
            ['userid' => $USER->id, 'contextid' => $contextid],
            'sortorder ASC, id ASC'
        );

        // Create a default tab if none exist yet.
        if (empty($tabs)) {
            $now     = time();
            $default = (object) [
                'userid'       => $USER->id,
                'contextid'    => $contextid,
                'tabname'      => get_string('defaulttabname', 'block_personalnotes'),
                'sortorder'    => 0,
                'notetext'     => '',
                'timecreated'  => $now,
                'timemodified' => $now,
            ];
            $default->id = $DB->insert_record('block_personalnotes', $default);
            $tabs        = [$default->id => $default];
        }

        // Build template data.
        $tabsdata  = [];
        $multitabs = count($tabs) > 1;
        $first     = true;
        foreach ($tabs as $tab) {
            $tabsdata[] = [
                'id'        => $tab->id,
                'tabname'   => s($tab->tabname),
                'notetext'  => $tab->notetext ?? '',
                'active'    => $first,
                'deletable' => $multitabs,
            ];
            $first = false;
        }

        $viewurl = new moodle_url('/blocks/personalnotes/view.php', ['courseid' => $courseid]);

        $PAGE->requires->js_call_amd('block_personalnotes/autosave', 'init', [$contextid]);

        $this->content->text = $OUTPUT->render_from_template(
            'block_personalnotes/block_content',
            [
                'contextid'    => $contextid,
                'tabs'         => $tabsdata,
                'viewurl'      => $viewurl->out(false),
                'strviewnotes' => get_string('viewnotes', 'block_personalnotes'),
            ]
        );

        return $this->content;
    }
}
