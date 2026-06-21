# Smoke — Frontend Editing for Craft CMS

On-page frontend editing for Craft CMS 5, powered by [DataStar](https://data-star.dev/).

## Requirements

- Craft CMS 5.4+
- PHP 8.2+

## Installation

### Via Composer

Add the repository to your project's `composer.json`:

```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/justinholtweb/craft-smoke"
    }
]
```

Then require and install:

```bash
composer require justinholtweb/craft-smoke
php craft plugin/install smoke
```

### Local Development

```json
"repositories": [
    {
        "type": "path",
        "url": "../craft-smoke"
    }
],
"require": {
    "justinholtweb/craft-smoke": "@dev"
}
```

## Usage

### 1. Initialize in Your Layout

Add to your main layout template (e.g., `templates/_layout.twig`):

```twig
{% do craft.smoke.init() %}
```

This automatically injects the editor container, assets, and DataStar for logged-in users with edit permissions.

### 2. Mark Fields as Editable

In your component templates, add `craft.smoke.editable()` attributes to editable elements:

```twig
<h1 {{ craft.smoke.editable(entry, 'title')|raw }}>
    {{ entry.title }}
</h1>

<div {{ craft.smoke.editable(entry, 'bodyContent')|raw }}>
    {{ entry.bodyContent }}
</div>
```

### 3. Edit on the Frontend

1. Log in to Craft CMS
2. Visit any entry page on the frontend
3. Click the floating "Edit Page" button
4. Make changes in the slide-out panel
5. Save with the button or **Cmd/Ctrl+S**
6. Press **ESC** to close

## Supported Field Types

| Type | Status |
|------|--------|
| Plain Text | Fully functional (inline + panel editing) |
| Rich Text (CKEditor / Redactor) | Inline + panel WYSIWYG (contenteditable + toolbar) |
| Lightswitch | Fully functional (inline toggle + panel) |
| Dropdown | Fully functional (inline + panel) |
| Table | Fully functional (panel; add/remove rows) |
| FreeLink | Panel editing (URL / text / new-window) |
| Hyper | Panel editing (first link: URL / text / new-window) |
| Assets | Display only |
| Entries / Categories / Tags / Users | Display only |
| Matrix | Display only |

### Known limitations

- **Rich text with inline asset/element references:** If a Rich Text field's content
  contains Craft reference tags (e.g. `{asset:123:url}`), the frontend editor shows the
  raw tag rather than the resolved URL while editing. This is intentional — editing the
  raw content preserves the reference on save (it would otherwise be flattened to a hard
  URL). Content without inline references is unaffected.
- **Hyper fields** must have their link types configured (saved once in the control
  panel); a freshly-created Hyper field with no configured link types silently drops links.

## Twig API

### `craft.smoke.init()`
Loads DataStar, registers CSS/JS assets, and injects the editor container.

### `craft.smoke.canEdit(entry)`
Returns `true` if the current user can edit the element.

### `craft.smoke.editable(entry, 'fieldHandle')`
Returns HTML data attributes that mark an element as editable.

### `craft.smoke.editButton(entry, options)`
Generates an edit button element. Options: `label` (default: `'Edit'`), `class`.

## Adding Field Type Support

1. Add the field class to `SmokeService::isFieldTypeSupported()`
2. Map it in `SmokeService::getFieldEditorType()`
3. Create `src/templates/_field-editors/{type}.twig`
4. Create `src/templates/_field-editors/{type}-display.twig`

## Troubleshooting

**Edit button doesn't appear** — Ensure you're logged in with edit permissions and `craft.smoke.init()` is called in your layout.

**Fields aren't editable** — Verify `craft.smoke.editable()` is added, the field type is supported, and the field exists in the entry's field layout.

**Save fails** — Check PHP error logs, verify the field handle is correct and you have edit permissions.

## License

This plugin is released under the [Craft License](https://craftcms.github.io/license/).
