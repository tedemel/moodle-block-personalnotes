<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace block_personalnotes;

use block_personalnotes\external\create_tab;

/**
 * Tests for the create_tab external function.
 *
 * @package    block_personalnotes
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_personalnotes\external\create_tab
 */
final class create_tab_test extends \advanced_testcase {
    /** @var \stdClass */
    private $user;
    /** @var int */
    private $contextid;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $this->user = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($this->user);
        $this->contextid = \context_course::instance($course->id)->id;
    }

    public function test_create_tab_with_explicit_name(): void {
        global $DB;

        $result = create_tab::execute($this->contextid, 'Mein Tab');
        $this->assertSame('Mein Tab', $result['tabname']);
        $this->assertGreaterThan(0, $result['id']);

        $row = $DB->get_record('block_personalnotes', ['id' => $result['id']]);
        $this->assertSame((int) $this->user->id, (int) $row->userid);
        $this->assertSame('Mein Tab', $row->tabname);
        $this->assertSame(0, (int) $row->sortorder);
    }

    public function test_create_tab_auto_numbers_when_empty(): void {
        $first = create_tab::execute($this->contextid, '');
        $second = create_tab::execute($this->contextid, '');

        $this->assertNotSame($first['tabname'], $second['tabname']);
        $this->assertStringContainsString('1', $first['tabname']);
        $this->assertStringContainsString('2', $second['tabname']);
    }

    public function test_create_tab_increments_sortorder_per_user(): void {
        global $DB;

        create_tab::execute($this->contextid, 'A');
        create_tab::execute($this->contextid, 'B');
        create_tab::execute($this->contextid, 'C');

        $rows = $DB->get_records('block_personalnotes',
            ['userid' => $this->user->id, 'contextid' => $this->contextid],
            'sortorder ASC'
        );
        $this->assertCount(3, $rows);
        $sortorders = array_column(array_values($rows), 'sortorder');
        $this->assertSame([0, 1, 2], array_map('intval', $sortorders));
    }
}
