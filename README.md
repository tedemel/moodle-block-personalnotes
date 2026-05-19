# Personal Notes — Moodle Block Plugin

**Component:** `block_personalnotes`  
**Requires:** Moodle 5.0+  
**Supported:** Moodle 5.0, 5.1, 5.2  
**License:** GNU GPL v3 or later  
**Author:** Tessa Demel

## Description

Personal Notes lets students and teachers write private, tabbed notes directly on any Moodle course page or activity. Notes are saved automatically via AJAX and are never visible to anyone else — not to instructors, not to admins.

## Features

- **Private per-user notes** — scoped to course page or module context
- **Tab system** — create, rename (double-click), and delete tabs
- **Auto-save** — debounced 600 ms after typing stops
- **Rich text** — bold and bullet lists via contenteditable toolbar
- **Course overview** — view all notes across a course with keyword search and date filter
- **Export** — download notes as ODT or DOCX (no external library required)
- **Print / PDF** — browser print dialog

## Installation

1. Download `block_personalnotes.zip`
2. Go to **Site administration → Plugins → Install plugins**
3. Upload the ZIP and follow the on-screen steps

Or via CLI:

```bash
unzip block_personalnotes.zip -d /path/to/moodle/blocks/
php admin/cli/upgrade.php --non-interactive
```

## Usage

1. Turn editing on in any course
2. **Add a block → Personal Notes**
3. Start writing — notes save automatically

## Capabilities

| Capability | Default roles |
|---|---|
| `block/personalnotes:addinstance` | Manager, Editing teacher |
| `block/personalnotes:myaddinstance` | Authenticated user |
| `block/personalnotes:addnote` | Student, Teacher, Manager |
| `block/personalnotes:viewnotes` | Student, Teacher, Manager |

## Privacy / GDPR

This plugin stores notes in the `mdl_block_personalnotes` table. It implements the full Moodle Privacy API:

- Reports which contexts contain user data
- Exports user data on request
- Deletes user data on request

## File structure

```
blocks/personalnotes/
├── amd/
│   ├── build/autosave.min.js   # compiled AMD module
│   └── src/autosave.js         # source: tab UI + auto-save
├── classes/
│   ├── external/
│   │   ├── create_tab.php
│   │   ├── delete_tab.php
│   │   ├── rename_tab.php
│   │   └── save_note.php
│   └── privacy/provider.php
├── db/
│   ├── access.php
│   ├── install.xml
│   └── services.php
├── lang/
│   ├── de/block_personalnotes.php
│   └── en/block_personalnotes.php
├── templates/
│   ├── block_content.mustache
│   └── view.mustache
├── block_personalnotes.php
├── export.php
├── version.php
└── view.php
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for the full release history.
