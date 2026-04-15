# Changelog

All notable changes to this project are documented in this file.

## 0.1.4 - 2026-04-15

- Strengthened role capability sync so existing editorial roles reliably keep media upload and advanced editing capabilities after updates.
- Improved compatibility for Issue workflows that depend on Media Library uploads from non-admin editorial users.

## 0.1.3 - 2026-04-15

- Added role capability sync for existing installs so `pulse_author` and `pulse_editor` reliably keep `upload_files`.
- Kept `pulse_editor` capability sync for `unfiltered_html` to support advanced editor blocks.
- Improved issue media workflows by ensuring editorial roles can upload assets without manual role repair.

