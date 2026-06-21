# Smoke — Craft CMS Frontend Editing Plugin

## Overview
Smoke is a Craft CMS 5 plugin that enables on-page frontend editing powered by [DataStar](https://data-star.dev/). Logged-in users with edit permissions see a floating "Edit Page" button that opens a slide-out panel for editing entry fields directly on the frontend.

## Architecture
- **DataStar SSE**: Server returns HTML fragments and signal updates via Server-Sent Events
- **DOM Morphing**: Only changed parts of the page are updated
- **Permission-Based**: Respects Craft's native `canSave()` element permissions
- **No custom data storage**: All edits go directly to Craft elements

## Directory Structure
```
src/
├── Plugin.php                  # Main plugin class, settings, event registration
├── controllers/
│   ├── EditController.php      # Open panel, load field editors, close panel
│   └── SaveController.php      # Save single field or all fields
├── helpers/
│   └── DatastarHelper.php      # SSE response formatting (shared by controllers)
├── models/
│   └── Settings.php            # Plugin settings model
├── services/
│   └── SmokeService.php        # Core logic: permissions, field types, attributes
├── variables/
│   └── SmokeVariable.php       # Twig API: init(), canEdit(), editable(), editButton()
├── web/
│   └── assets/
│       ├── SmokeAsset.php      # Asset bundle registration
│       └── dist/
│           ├── smoke.css       # Editor styles
│           └── smoke.js        # Frontend JS (inline editing, shortcuts, notifications)
├── templates/
│   ├── _components/
│   │   ├── edit-panel.twig     # Slide-out editing panel
│   │   └── editor-container.twig # Container + floating edit button
│   ├── _field-editors/         # One template per field type (editor + display)
│   │   ├── plaintext.twig
│   │   ├── richtext.twig
│   │   ├── lightswitch.twig
│   │   ├── dropdown.twig
│   │   ├── table.twig
│   │   ├── assets.twig
│   │   ├── entries.twig
│   │   ├── matrix.twig
│   │   └── ... (-display.twig variants)
│   └── settings/
│       └── _index.twig         # CP settings page
├── translations/
│   └── en/smoke.php
├── migrations/                 # (empty, for future Install migration)
└── icon.svg
```

## Key Classes

| Class | Purpose |
|-------|---------|
| `Plugin` | Bootstrap, settings, component/variable registration |
| `SmokeService` | `canEdit()`, `getEditableFields()`, `getEditableAttributes()`, field type mapping |
| `SmokeVariable` | Twig API — `init()` injects assets/container, `editable()` returns data attributes |
| `EditController` | `actionOpen()`, `actionField()`, `actionClose()` — returns DataStar SSE |
| `SaveController` | `actionField()` (JSON), `actionAll()` (SSE) — saves element field values |
| `DatastarHelper` | Static `response()` — formats DataStar 1.0 `patch-elements`/`patch-signals` SSE |
| `Settings` | `enabled` boolean |
| `SmokeAsset` | Registers `smoke.css`, `smoke.js`, and the vendored `datastar.js` (ES module) |

## Supported Field Types

| Type | Editor | Display | Notes |
|------|--------|---------|-------|
| Plain Text | Inline + panel | Yes | Fully functional |
| Rich Text (CKEditor) | Panel (textarea) | Yes | Basic; full CKEditor integration planned |
| Lightswitch | Panel | Yes | Toggle switch |
| Dropdown | Panel | Yes | Select element |
| Table | Panel | Yes | Add/remove rows |
| Assets | — | Yes | Display only |
| Entries | — | Yes | Display only |
| Categories | — | Yes | Display only (uses entries template) |
| Tags | — | Yes | Display only (uses entries template) |
| Users | — | Yes | Display only (uses entries template) |
| Matrix | — | Yes | Display only |

## Data Flow
1. `{% do craft.smoke.init() %}` in layout registers assets + injects editor container
2. `{{ craft.smoke.editable(entry, 'fieldHandle')|raw }}` adds `data-smoke-*` attributes
3. User clicks "Edit Page" → `GET /actions/smoke/edit/open?elementId=X`
4. Server returns SSE `datastar-patch-elements` (edit panel HTML) + `datastar-patch-signals` (state)
5. Inline edit → `POST /actions/smoke/save/field` (vanilla JS, JSON response, CSRF via `window.smokeCsrf`)
6. Panel "Save all" → `POST /actions/smoke/save/all` (DataStar @post sends signals as JSON; SSE signal response)

## SSE Event Format (DataStar 1.0 — line-based, not JSON-wrapped)
```
event: datastar-patch-elements
data: selector #smoke-editor
data: mode inner
data: elements <div class="smoke-panel-header">...</div>

event: datastar-patch-signals
data: signals {"smokeEditorOpen":true,"smokeElementId":123}
```
Built by `DatastarHelper::response(['elements' => [...], 'signals' => [...]])`.

## DataStar client conventions (1.0)
- Vendored locally at `web/assets/dist/datastar.js` (v1.0.1), registered as an ES module.
- Attributes: `data-on:click="@get('…')"` / `@post('…')`, `data-signals`, `data-bind="fields.handle"`, `data-show`, `data-text`, `data-attr:disabled`.
- Twig API is `craft.smoke.*` (not a bare `smoke` global).

## Conventions
- PHP namespace: `justinholtweb\smoke`
- Composer package: `justinholtweb/craft-smoke`
- Plugin handle: `smoke`
- Asset alias: `@justinholtweb/smoke/web/assets/dist`
- Templates render in `TEMPLATE_MODE_CP`
- Controllers require login; SSE responses via `DatastarHelper::response()`
- Plugin follows craft-* conventions (matching craft-dispatch, craft-garrison, etc.)

## Requirements
- PHP 8.2+
- Craft CMS 5.4+
