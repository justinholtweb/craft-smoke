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
{% do smoke.init() %}
```

This automatically injects the editor container, assets, and DataStar for logged-in users with edit permissions.

### 2. Mark Fields as Editable

In your component templates, add `smoke.editable()` attributes to editable elements:

```twig
<h1 {{ smoke.editable(entry, 'title')|raw }}>
    {{ entry.title }}
</h1>

<div {{ smoke.editable(entry, 'bodyContent')|raw }}>
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
| Rich Text (CKEditor) | Panel editing (basic textarea) |
| Lightswitch | Fully functional |
| Dropdown | Fully functional |
| Table | Fully functional (add/remove rows) |
| Assets | Display only |
| Entries / Categories / Tags / Users | Display only |
| Matrix | Display only |

## Twig API

### `smoke.init()`
Loads DataStar, registers CSS/JS assets, and injects the editor container.

### `smoke.canEdit(entry)`
Returns `true` if the current user can edit the element.

### `smoke.editable(entry, 'fieldHandle')`
Returns HTML data attributes that mark an element as editable.

### `smoke.editButton(entry, options)`
Generates an edit button element. Options: `label` (default: `'Edit'`), `class`.

## Adding Field Type Support

1. Add the field class to `SmokeService::isFieldTypeSupported()`
2. Map it in `SmokeService::getFieldEditorType()`
3. Create `src/templates/_field-editors/{type}.twig`
4. Create `src/templates/_field-editors/{type}-display.twig`

## Troubleshooting

**Edit button doesn't appear** — Ensure you're logged in with edit permissions and `smoke.init()` is called in your layout.

**Fields aren't editable** — Verify `smoke.editable()` is added, the field type is supported, and the field exists in the entry's field layout.

**Save fails** — Check PHP error logs, verify the field handle is correct and you have edit permissions.

## License

This plugin is released under the [Craft License](https://craftcms.github.io/license/).
