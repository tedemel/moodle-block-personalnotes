<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace block_personalnotes;

use block_personalnotes\external\create_tab;
use block_personalnotes\external\save_note;
use block_personalnotes\external\rename_tab;

/**
 * Tests for save_note + rename_tab external functions.
 *
 * @package    block_personalnotes
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_personalnotes\external\save_note
 * @covers     \block_personalnotes\external\rename_tab
 */
final class save_note_test extends \advanced_testcase {

    /** @var \stdClass */
    private $course;
    /** @var \stdClass */
    private $owner;
    /** @var int */
    private $contextid;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->course = $this->getDataGenerator()->create_course();
        $this->owner = $this->getDataGenerator()->create_and_enrol($this->course, 'student');
        $this->setUser($this->owner);
        $this->contextid = \context_course::instance($this->course->id)->id;
    }

    public function test_owner_saves_html_content_cleaned(): void {
        global $DB;

        $tab = create_tab::execute($this->contextid, 'Tab');

        $dirty = '<p>Hello <strong>world</strong></p><script>alert(1)</script>';
        save_note::execute($tab['id'], $dirty);

        $row = $DB->get_record('block_personalnotes', ['id' => $tab['id']]);
        $this->assertStringContainsString('Hello', $row->notetext);
        $this->assertStringNotContainsString('<script', $row->notetext);
        $this->assertStringNotContainsString('alert(1)', $row->notetext);
    }

    public function test_non_owner_cannot_save(): void {
        $tab = create_tab::execute($this->contextid, 'Tab');

        $other = $this->getDataGenerator()->create_and_enrol($this->course, 'student');
        $this->setUser($other);

        $this->expectException(\moodle_exception::class);
        save_note::execute($tab['id'], '<p>hijack</p>');
    }

    public function test_rename_tab_updates_label_for_owner(): void {
        global $DB;

        $tab = create_tab::execute($this->contextid, 'Old');
        rename_tab::execute($tab['id'], 'Neu');

        $row = $DB->get_record('block_personalnotes', ['id' => $tab['id']]);
        $this->assertSame('Neu', $row->tabname);
    }
}
