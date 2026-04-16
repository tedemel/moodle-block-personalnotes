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
 * German language strings for block_personalnotes.
 *
 * @package    block_personalnotes
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname']     = 'Persönliche Notizen';
$string['pluginname_help'] = 'Ermöglicht das Schreiben privater Notizen zu jeder Kursseite oder Aktivität. Nur du kannst deine Notizen sehen.';

// Block UI.
$string['placeholder']    = 'Private Notiz schreiben…';
$string['toolbar']        = 'Formatierungsleiste';
$string['cmd_bold']       = 'Fett';
$string['cmd_bullets']    = 'Aufzählungsliste';
$string['viewnotes']      = 'Alle meine Notizen in diesem Kurs anzeigen';
$string['saving']         = 'Wird gespeichert…';
$string['saved']          = 'Gespeichert';
// Tabs.
$string['defaulttabname']     = 'Notiz 1';
$string['defaulttabnamebase'] = 'Notiz';
$string['addtab']         = 'Neuer Tab';
$string['deletetab']      = 'Tab löschen';
$string['confirmdelete']  = 'Diesen Tab und seine Notiz wirklich löschen?';

// View page.
$string['backtocourse']   = 'Zurück zum Kurs';
$string['exportnotes']    = 'Exportieren als';
$string['printnotes']     = 'Drucken / Als PDF speichern';
$string['search']         = 'Schlagwortsuche';
$string['datefrom']       = 'Von Datum';
$string['dateto']         = 'Bis Datum';
$string['filter']         = 'Filtern';
$string['resetfilter']    = 'Filter zurücksetzen';
$string['noresults']      = 'Keine Notizen entsprechen dem Filter.';
$string['nonotes']        = 'Du hast noch keine Notizen in diesem Kurs.';
$string['unknowncontext'] = 'Unbekannte Seite';

// Capabilities.
$string['block_personalnotes:addinstance']   = 'Block „Persönliche Notizen" hinzufügen';
$string['block_personalnotes:myaddinstance'] = 'Block „Persönliche Notizen" zum Dashboard hinzufügen';
$string['block_personalnotes:addnote']       = 'Persönliche Notizen schreiben';
$string['block_personalnotes:viewnotes']     = 'Notizliste ansehen';

// Privacy.
$string['privacy:metadata:block_personalnotes']              = 'Der Block „Persönliche Notizen" speichert private Notizen der Nutzer/innen.';
$string['privacy:metadata:block_personalnotes:userid']       = 'ID der Person, die die Notiz geschrieben hat.';
$string['privacy:metadata:block_personalnotes:contextid']    = 'Der Moodle-Kontext (Kurs oder Aktivität), zu dem die Notiz gehört.';
$string['privacy:metadata:block_personalnotes:notetext']     = 'Der Textinhalt der Notiz.';
$string['privacy:metadata:block_personalnotes:timecreated']  = 'Zeitpunkt der Erstellung.';
$string['privacy:metadata:block_personalnotes:timemodified'] = 'Zeitpunkt der letzten Änderung.';
