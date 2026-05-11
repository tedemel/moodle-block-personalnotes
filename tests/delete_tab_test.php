<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace block_personalnotes;

use block_personalnotes\external\create_tab;
use block_personalnotes\external\delete_tab;

/**
 * Tests for the delete_tab external function.
 *
 * @package    block_personalnotes
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_personalnotes\external\delete_tab
 */
final class delete_tab_test extends \advanced_testcase {
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

    public function test_owner_can_delete_when_more_than_one_tab(): void {
        global $DB;

        $a = create_tab::execute($this->contextid, 'A');
        $b = create_tab::execute($this->contextid, 'B');

        $result = delete_tab::execute($a['id']);

        $this->assertTrue((bool) $result['success']);
        $this->assertFalse($DB->record_exists('block_personalnotes', ['id' => $a['id']]));
        $this->assertTrue($DB->record_exists('block_personalnotes', ['id' => $b['id']]));
    }

    public function test_last_tab_cannot_be_deleted(): void {
        global $DB;

        $only = create_tab::execute($this->contextid, 'Only');

        $result = delete_tab::execute($only['id']);

        $this->assertFalse((bool) $result['success']);
        $this->assertNotEmpty($result['error']);
        $this->assertTrue($DB->record_exists('block_personalnotes', ['id' => $only['id']]));
    }

    public function test_non_owner_cannot_delete(): void {
        $a = create_tab::execute($this->contextid, 'A');
        create_tab::execute($this->contextid, 'B'); // Ensure not last tab.

        $other = $this->getDataGenerator()->create_and_enrol($this->course, 'student');
        $this->setUser($other);
        $this->expectException(\moodle_exception::class);
        delete_tab::execute($a['id']);
    }
}
