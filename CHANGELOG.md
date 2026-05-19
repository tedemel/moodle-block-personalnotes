# Changelog

All notable changes to `block_personalnotes` are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.1] — 2026-05-19

### Changed
- Compatibility verified for Moodle 5.2.
- `$plugin->supported = [500, 502]` set in `version.php` (range 5.0–5.2).
- CI matrix extended to `MOODLE_501_STABLE` and `MOODLE_502_STABLE` (PHP 8.3 and 8.4).

## [1.0.0] — 2026-04-15

### Added
- Initial stable release.
- Tab management (create, rename via double-click, delete).
- Debounced auto-save via AJAX (600 ms).
- Rich text via contenteditable toolbar (bold, bullet lists).
- Course-level overview with keyword search and date filter.
- Export as ODT and DOCX (no external library required).
- Browser print / PDF support.
- Full Moodle Privacy API implementation.
