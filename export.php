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
 * Export personal notes as ODT or DOCX.
 *
 * URL: /blocks/personalnotes/export.php?courseid=X&format=odt|docx&sesskey=...
 *
 * @package    block_personalnotes
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$format   = optional_param('format', 'odt', PARAM_ALPHA);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
require_login($course);
$coursecontext = context_course::instance($courseid);
require_capability('block/personalnotes:viewnotes', $coursecontext);
require_sesskey();

if (!in_array($format, ['odt', 'docx'])) {
    $format = 'odt';
}

// ── Collect notes ─────────────────────────────────────────────────────────
$contextids = [$coursecontext->id];
$modinfos   = get_fast_modinfo($course);
foreach ($modinfos->get_cms() as $cm) {
    $contextids[] = context_module::instance($cm->id)->id;
}

[$insql, $inparams] = $DB->get_in_or_equal($contextids, SQL_PARAMS_NAMED);
$inparams['userid'] = $USER->id;
$notes = $DB->get_records_select(
    'block_personalnotes',
    "userid = :userid AND contextid $insql",
    $inparams,
    'timemodified DESC'
);

$notedata = [];
foreach ($notes as $note) {
    if (empty(trim(strip_tags($note->notetext)))) {
        continue;
    }
    $ctx  = context::instance_by_id($note->contextid, IGNORE_MISSING);
    $name = $ctx ? $ctx->get_context_name(false, true) : get_string('unknowncontext', 'block_personalnotes');
    $notedata[] = [
        'name' => $name,
        'html' => $note->notetext,
        'date' => userdate($note->timemodified),
    ];
}

$coursetitle = $course->fullname;
$exportdate  = userdate(time());
$filename    = clean_filename($coursetitle . '_notes_' . date('Ymd')) . '.' . $format;

// ── HTML → ODT helpers ────────────────────────────────────────────────────

/**
 * Convert simple HTML to ODT paragraph XML string.
 * Handles: <p>, <div>, <br>, <ul>, <li>, <strong>, <b>, <em>, <i>
 *
 * @param string $html
 * @return string ODT XML fragment
 */
function html_to_odt(string $html): string {
    if (empty(trim(strip_tags($html)))) {
        return '<text:p text:style-name="NoteBody"/>';
    }
    $dom = new DOMDocument('1.0', 'UTF-8');
    libxml_use_internal_errors(true);
    $dom->loadHTML('<html><body>' . $html . '</body></html>');
    libxml_clear_errors();
    $body   = $dom->getElementsByTagName('body')->item(0);
    $result = '';
    foreach ($body->childNodes as $child) {
        $result .= odt_block($child);
    }
    return $result ?: '<text:p text:style-name="NoteBody"/>';
}

function odt_block(DOMNode $node): string {
    if ($node->nodeType === XML_TEXT_NODE) {
        $t = $node->textContent;
        if (trim($t) === '') {
            return '';
        }
        return '<text:p text:style-name="NoteBody">'
            . htmlspecialchars($t, ENT_XML1 | ENT_QUOTES) . '</text:p>';
    }
    if ($node->nodeType !== XML_ELEMENT_NODE) {
        return '';
    }
    $tag = strtolower($node->tagName);
    switch ($tag) {
        case 'p':
        case 'div':
            $inner = odt_inline($node);
            if (trim(strip_tags($inner)) === '' && strpos($inner, 'line-break') === false) {
                return '<text:p text:style-name="NoteBody"/>';
            }
            return '<text:p text:style-name="NoteBody">' . $inner . '</text:p>';
        case 'br':
            return '<text:p text:style-name="NoteBody"/>';
        case 'ul':
            return odt_list($node);
        case 'ol':
            return odt_list($node, true);
        default:
            // Inline element at block level – wrap in paragraph.
            return '<text:p text:style-name="NoteBody">' . odt_inline($node) . '</text:p>';
    }
}

function odt_inline(DOMNode $node): string {
    $out = '';
    foreach ($node->childNodes as $child) {
        if ($child->nodeType === XML_TEXT_NODE) {
            $out .= htmlspecialchars($child->textContent, ENT_XML1 | ENT_QUOTES);
        } elseif ($child->nodeType === XML_ELEMENT_NODE) {
            $tag = strtolower($child->tagName);
            switch ($tag) {
                case 'strong':
                case 'b':
                    $out .= '<text:span text:style-name="BoldChar">'
                        . odt_inline($child) . '</text:span>';
                    break;
                case 'em':
                case 'i':
                    $out .= '<text:span text:style-name="ItalicChar">'
                        . odt_inline($child) . '</text:span>';
                    break;
                case 'br':
                    $out .= '<text:line-break/>';
                    break;
                default:
                    $out .= odt_inline($child);
            }
        }
    }
    return $out;
}

function odt_list(DOMNode $node, bool $ordered = false): string {
    $style = $ordered ? 'NumberedList' : 'BulletList';
    $out   = '<text:list text:style-name="' . $style . '">';
    foreach ($node->childNodes as $child) {
        if ($child->nodeType !== XML_ELEMENT_NODE) {
            continue;
        }
        if (strtolower($child->tagName) === 'li') {
            $out .= '<text:list-item>'
                . '<text:p text:style-name="ListItem">' . odt_inline($child) . '</text:p>'
                . '</text:list-item>';
        }
    }
    $out .= '</text:list>';
    return $out;
}

// ── HTML → DOCX helpers ───────────────────────────────────────────────────

function html_to_docx(string $html): string {
    if (empty(trim(strip_tags($html)))) {
        return '<w:p><w:r><w:t/></w:r></w:p>';
    }
    $dom = new DOMDocument('1.0', 'UTF-8');
    libxml_use_internal_errors(true);
    $dom->loadHTML('<html><body>' . $html . '</body></html>');
    libxml_clear_errors();
    $body   = $dom->getElementsByTagName('body')->item(0);
    $result = '';
    foreach ($body->childNodes as $child) {
        $result .= docx_block($child);
    }
    return $result ?: '<w:p><w:r><w:t/></w:r></w:p>';
}

function docx_block(DOMNode $node): string {
    if ($node->nodeType === XML_TEXT_NODE) {
        $t = $node->textContent;
        if (trim($t) === '') {
            return '';
        }
        return '<w:p><w:r><w:t xml:space="preserve">'
            . htmlspecialchars($t, ENT_XML1 | ENT_QUOTES) . '</w:t></w:r></w:p>';
    }
    if ($node->nodeType !== XML_ELEMENT_NODE) {
        return '';
    }
    $tag = strtolower($node->tagName);
    switch ($tag) {
        case 'p':
        case 'div':
            return '<w:p>' . docx_inline($node) . '</w:p>';
        case 'br':
            return '<w:p><w:r><w:t/></w:r></w:p>';
        case 'ul':
            return docx_list($node, false);
        case 'ol':
            return docx_list($node, true);
        default:
            return '<w:p>' . docx_inline($node) . '</w:p>';
    }
}

function docx_inline(DOMNode $node): string {
    $out = '';
    foreach ($node->childNodes as $child) {
        if ($child->nodeType === XML_TEXT_NODE) {
            $t = $child->textContent;
            if ($t !== '') {
                $out .= '<w:r><w:t xml:space="preserve">'
                    . htmlspecialchars($t, ENT_XML1 | ENT_QUOTES) . '</w:t></w:r>';
            }
        } elseif ($child->nodeType === XML_ELEMENT_NODE) {
            $tag = strtolower($child->tagName);
            switch ($tag) {
                case 'strong':
                case 'b':
                    $out .= '<w:r><w:rPr><w:b/><w:bCs/></w:rPr>'
                        . '<w:t xml:space="preserve">'
                        . htmlspecialchars($child->textContent, ENT_XML1 | ENT_QUOTES)
                        . '</w:t></w:r>';
                    break;
                case 'em':
                case 'i':
                    $out .= '<w:r><w:rPr><w:i/><w:iCs/></w:rPr>'
                        . '<w:t xml:space="preserve">'
                        . htmlspecialchars($child->textContent, ENT_XML1 | ENT_QUOTES)
                        . '</w:t></w:r>';
                    break;
                case 'br':
                    $out .= '<w:r><w:br/></w:r>';
                    break;
                default:
                    $out .= docx_inline($child);
            }
        }
    }
    return $out;
}

function docx_list(DOMNode $node, bool $ordered): string {
    $numId = $ordered ? '2' : '1';
    $out   = '';
    foreach ($node->childNodes as $child) {
        if ($child->nodeType !== XML_ELEMENT_NODE) {
            continue;
        }
        if (strtolower($child->tagName) === 'li') {
            $out .= '<w:p>'
                . '<w:pPr><w:numPr>'
                . '<w:ilvl w:val="0"/>'
                . '<w:numId w:val="' . $numId . '"/>'
                . '</w:numPr></w:pPr>'
                . docx_inline($child)
                . '</w:p>';
        }
    }
    return $out;
}

// ── ODT export ────────────────────────────────────────────────────────────

if ($format === 'odt') {
    $body = '';
    foreach ($notedata as $n) {
        $body .= '<text:h text:style-name="NoteHeading" text:outline-level="2">'
            . htmlspecialchars($n['name'], ENT_XML1) . '</text:h>' . "\n";
        $body .= html_to_odt($n['html']) . "\n";
        $body .= '<text:p text:style-name="NoteDate">'
            . htmlspecialchars($n['date'], ENT_XML1) . '</text:p>' . "\n";
        $body .= '<text:p text:style-name="NoteBody"/>' . "\n";  // blank line between notes
    }

    $contentxml = '<?xml version="1.0" encoding="UTF-8"?>
<office:document-content
    xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"
    xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0"
    xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"
    xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0"
    office:version="1.3">
  <office:automatic-styles>
    <!-- Character styles -->
    <style:style style:name="BoldChar" style:family="text">
      <style:text-properties fo:font-weight="bold"
          fo:font-weight-asian="bold" fo:font-weight-complex="bold"/>
    </style:style>
    <style:style style:name="ItalicChar" style:family="text">
      <style:text-properties fo:font-style="italic"
          fo:font-style-asian="italic" fo:font-style-complex="italic"/>
    </style:style>
    <!-- Paragraph styles -->
    <style:style style:name="NoteHeading" style:family="paragraph">
      <style:text-properties fo:font-weight="bold" fo:font-size="13pt"/>
      <style:paragraph-properties fo:margin-top="0.15in" fo:margin-bottom="0.05in"/>
    </style:style>
    <style:style style:name="NoteBody" style:family="paragraph">
      <style:paragraph-properties fo:margin-bottom="0.05in"/>
    </style:style>
    <style:style style:name="NoteDate" style:family="paragraph">
      <style:text-properties fo:color="#888888" fo:font-style="italic" fo:font-size="9pt"/>
      <style:paragraph-properties fo:margin-bottom="0.1in"/>
    </style:style>
    <style:style style:name="ListItem" style:family="paragraph">
      <style:paragraph-properties fo:margin-left="0.5in" fo:text-indent="-0.25in"
          fo:margin-bottom="0.03in"/>
    </style:style>
    <!-- List styles -->
    <text:list-style style:name="BulletList">
      <text:list-level-style-bullet text:level="1" text:bullet-char="&#x2022;">
        <style:list-level-properties text:space-before="0.25in" text:min-label-width="0.25in"/>
      </text:list-level-style-bullet>
    </text:list-style>
    <text:list-style style:name="NumberedList">
      <text:list-level-style-number text:level="1" style:num-format="1" style:num-suffix=".">
        <style:list-level-properties text:space-before="0.25in" text:min-label-width="0.25in"/>
      </text:list-level-style-number>
    </text:list-style>
  </office:automatic-styles>
  <office:body>
    <office:text>
      <text:h text:style-name="NoteHeading" text:outline-level="1">'
    . htmlspecialchars($coursetitle . ' – ' . get_string('pluginname', 'block_personalnotes'), ENT_XML1)
    . '</text:h>
      <text:p text:style-name="NoteDate">' . htmlspecialchars($exportdate, ENT_XML1) . '</text:p>
      ' . $body . '
    </office:text>
  </office:body>
</office:document-content>';

    $metaxml = '<?xml version="1.0" encoding="UTF-8"?>
<office:document-meta
    xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"
    xmlns:dc="http://purl.org/dc/elements/1.1/"
    office:version="1.3">
  <office:meta>
    <dc:title>' . htmlspecialchars($coursetitle, ENT_XML1) . '</dc:title>
  </office:meta>
</office:document-meta>';

    $manifestxml = '<?xml version="1.0" encoding="UTF-8"?>
<manifest:manifest xmlns:manifest="urn:oasis:names:tc:opendocument:xmlns:manifest:1.0"
    manifest:version="1.3">
  <manifest:file-entry manifest:full-path="/"
      manifest:media-type="application/vnd.oasis.opendocument.text"/>
  <manifest:file-entry manifest:full-path="content.xml" manifest:media-type="text/xml"/>
  <manifest:file-entry manifest:full-path="meta.xml"    manifest:media-type="text/xml"/>
</manifest:manifest>';

    $tmpfile = tempnam(sys_get_temp_dir(), 'pnotes_');
    $zip = new ZipArchive();
    $zip->open($tmpfile, ZipArchive::OVERWRITE);
    // mimetype MUST be first and uncompressed per ODF spec.
    $zip->addFromString('mimetype', 'application/vnd.oasis.opendocument.text');
    $zip->setCompressionName('mimetype', ZipArchive::CM_STORE);
    $zip->addFromString('content.xml',           $contentxml);
    $zip->addFromString('meta.xml',              $metaxml);
    $zip->addFromString('META-INF/manifest.xml', $manifestxml);
    $zip->close();

    header('Content-Type: application/vnd.oasis.opendocument.text');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($tmpfile));
    readfile($tmpfile);
    unlink($tmpfile);
    exit;
}

// ── DOCX export ───────────────────────────────────────────────────────────

if ($format === 'docx') {
    $paragraphs = '';
    foreach ($notedata as $n) {
        // Heading for context name.
        $paragraphs .= '<w:p>'
            . '<w:pPr><w:pStyle w:val="Heading2"/></w:pPr>'
            . '<w:r><w:t>' . htmlspecialchars($n['name'], ENT_XML1) . '</w:t></w:r>'
            . '</w:p>' . "\n";
        // Note content (HTML→DOCX).
        $paragraphs .= html_to_docx($n['html']) . "\n";
        // Date.
        $paragraphs .= '<w:p>'
            . '<w:pPr><w:rPr><w:color w:val="888888"/><w:i/></w:rPr></w:pPr>'
            . '<w:r><w:rPr><w:color w:val="888888"/><w:i/></w:rPr>'
            . '<w:t>' . htmlspecialchars($n['date'], ENT_XML1) . '</w:t></w:r>'
            . '</w:p>' . "\n";
        $paragraphs .= '<w:p/>' . "\n";  // blank line between notes
    }

    $documentxml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document
    xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"
    xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <w:body>
    <w:p>
      <w:pPr><w:pStyle w:val="Title"/></w:pPr>
      <w:r><w:t>'
    . htmlspecialchars($coursetitle . ' – ' . get_string('pluginname', 'block_personalnotes'), ENT_XML1)
    . '</w:t></w:r>
    </w:p>
    <w:p>
      <w:r><w:rPr><w:color w:val="888888"/><w:i/></w:rPr>
        <w:t>' . htmlspecialchars($exportdate, ENT_XML1) . '</w:t>
      </w:r>
    </w:p>
    ' . $paragraphs . '
  </w:body>
</w:document>';

    // Numbering definitions for bullet and numbered lists.
    $numberingxml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:numbering xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <!-- Abstract: bullet -->
  <w:abstractNum w:abstractNumId="0">
    <w:multiLevelType w:val="hybridMultilevel"/>
    <w:lvl w:ilvl="0">
      <w:start w:val="1"/>
      <w:numFmt w:val="bullet"/>
      <w:lvlText w:val="&#x2022;"/>
      <w:lvlJc w:val="left"/>
      <w:pPr><w:ind w:left="720" w:hanging="360"/></w:pPr>
    </w:lvl>
  </w:abstractNum>
  <!-- Abstract: decimal -->
  <w:abstractNum w:abstractNumId="1">
    <w:multiLevelType w:val="hybridMultilevel"/>
    <w:lvl w:ilvl="0">
      <w:start w:val="1"/>
      <w:numFmt w:val="decimal"/>
      <w:lvlText w:val="%1."/>
      <w:lvlJc w:val="left"/>
      <w:pPr><w:ind w:left="720" w:hanging="360"/></w:pPr>
    </w:lvl>
  </w:abstractNum>
  <w:num w:numId="1"><w:abstractNumId w:val="0"/></w:num>
  <w:num w:numId="2"><w:abstractNumId w:val="1"/></w:num>
</w:numbering>';

    $docRelsxml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1"
    Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/numbering"
    Target="numbering.xml"/>
</Relationships>';

    $relsxml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1"
    Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument"
    Target="word/document.xml"/>
</Relationships>';

    $contenttypesxml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml"  ContentType="application/xml"/>
  <Override PartName="/word/document.xml"
    ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
  <Override PartName="/word/numbering.xml"
    ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.numbering+xml"/>
</Types>';

    $tmpfile = tempnam(sys_get_temp_dir(), 'pnotes_');
    $zip = new ZipArchive();
    $zip->open($tmpfile, ZipArchive::OVERWRITE);
    $zip->addFromString('[Content_Types].xml',       $contenttypesxml);
    $zip->addFromString('_rels/.rels',               $relsxml);
    $zip->addFromString('word/document.xml',         $documentxml);
    $zip->addFromString('word/numbering.xml',        $numberingxml);
    $zip->addFromString('word/_rels/document.xml.rels', $docRelsxml);
    $zip->close();

    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($tmpfile));
    readfile($tmpfile);
    unlink($tmpfile);
    exit;
}
