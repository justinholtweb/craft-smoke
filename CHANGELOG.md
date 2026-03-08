# Changelog

## 0.1.12 - 2026-02-18
### Changed
- Restructured plugin to match craft-* conventions
- Renamed package from `justinholtweb/smoke` to `justinholtweb/craft-smoke`
- Moved asset bundles from `assetbundles/smoke/` to `web/assets/`
- Extracted `DatastarHelper` from duplicate controller methods
- Added Settings model and CP settings page
- Added proprietary license (Craft License)
- Reorganized `Plugin::init()` into discrete registration methods

## 0.1.11 - 2024-12-06
### Changed
- JS updates for inline editing

## 0.1.10 - 2024-12-06
### Changed
- Refactored inline editing for plaintext fields

## 0.1.0 - 2024-12-01
### Added
- Initial proof-of-concept release
- Inline editing panel with slide-out UI
- Plain Text, Rich Text (basic), Lightswitch, Dropdown, Table field support
- Assets, Entries, Categories, Tags, Users, Matrix display-only support
- Permission-aware editing via Craft's native system
- Mobile responsive layout with keyboard shortcuts
- DataStar SSE-based reactive updates
