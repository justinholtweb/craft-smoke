# Smoke - Frontend Editing for Craft CMS

**Proof of Concept** - On-page frontend editing for Craft CMS powered by DataStar.

## Overview

Smoke enables content editors to edit Craft CMS entries directly on the frontend of your website using a lightweight, reactive interface powered by [DataStar](https://data-star.dev/).

## Features

### ✅ Implemented (POC)

- **Inline Editing Panel**: Slide-out panel with all editable fields
- **Field Type Support**:
  - ✅ Plain Text
  - ✅ Rich Text (CKEditor) - basic textarea for now
  - ✅ Lightswitch
  - ✅ Dropdown
  - ✅ Table
  - 🚧 Assets (display only)
  - 🚧 Entries/Categories/Tags/Users (display only)
  - 🚧 Matrix (display only)
- **Permission Aware**: Only shows edit controls to authorized users
- **Mobile Responsive**: Works on all screen sizes
- **Keyboard Shortcuts**: ESC to close, Cmd/Ctrl+S to save

### 🚧 Planned Features

- Full CKEditor integration for rich text
- Asset selector with upload
- Relationship field pickers (entries, categories, tags, users)
- Matrix block editing and reordering
- Auto-save and draft support
- Revision history
- Multi-language support
- Live preview updates

## Installation

### 1. Install via Composer

Since this is a local plugin, it's already configured in your `composer.json`:

```bash
ddev composer install
```

### 2. Install the Plugin

```bash
ddev craft plugin/install smoke
```

### 3. Add to Your Layout

Add the following to your main layout template ([templates/_layout.twig](../../templates/_layout.twig)):

```twig
{# Initialize Smoke editor #}
{% do smoke.init() %}

{# Add editor container before closing body tag #}
{% include 'smoke/_components/editor-container.twig' %}
```

### 4. Make Fields Editable

In your component templates, wrap editable content with the `smoke.editable()` function:

```twig
{# Example: Making a text field editable #}
<h1 {{ smoke.editable(entry, 'title')|raw }}>
    {{ entry.title }}
</h1>

<div {{ smoke.editable(entry, 'bodyContent')|raw }}>
    {{ entry.bodyContent }}
</div>
```

## Usage

### Basic Example

```twig
{# In your template #}
<div class="content">
    <h1 {{ smoke.editable(entry, 'heroHeading')|raw }}>
        {{ entry.heroHeading }}
    </h1>

    <div {{ smoke.editable(entry, 'description')|raw }}>
        {{ entry.description }}
    </div>
</div>
```

### Using the Edit Button

A floating "Edit Page" button will appear for logged-in users with edit permissions. Clicking it opens the edit panel with all editable fields.

### Keyboard Shortcuts

- **ESC** - Close edit panel
- **Cmd/Ctrl + S** - Save changes

## Architecture

### How It Works

1. **DataStar Integration**: Uses DataStar for reactive updates without heavy JavaScript frameworks
2. **SSE Responses**: Server sends HTML fragments via Server-Sent Events
3. **DOM Morphing**: Updates only changed parts of the page
4. **Permission-Based**: Respects Craft's built-in permission system

### File Structure

```
plugins/smoke/
├── src/
│   ├── Plugin.php                     # Main plugin class
│   ├── controllers/
│   │   ├── EditController.php         # Handles edit panel requests
│   │   └── SaveController.php         # Handles save operations
│   ├── services/
│   │   └── SmokeService.php           # Core editing logic
│   ├── variables/
│   │   └── SmokeVariable.php          # Twig template API
│   ├── assetbundles/
│   │   └── smoke/
│   │       ├── SmokeAsset.php
│   │       └── dist/
│   │           ├── smoke.css          # Editor styles
│   │           └── smoke.js           # Frontend interactions
│   └── templates/
│       ├── _components/
│       │   ├── edit-panel.twig        # Main edit UI
│       │   └── editor-container.twig   # Container template
│       └── _field-editors/
│           ├── plaintext.twig         # Field-specific editors
│           ├── richtext.twig
│           ├── lightswitch.twig
│           └── ...
└── composer.json
```

### Twig API

#### `smoke.init()`
Initializes the Smoke editor (loads DataStar and assets).

```twig
{% do smoke.init() %}
```

#### `smoke.canEdit(entry)`
Check if the current user can edit an entry.

```twig
{% if smoke.canEdit(entry) %}
    <button>Edit</button>
{% endif %}
```

#### `smoke.editable(entry, fieldHandle)`
Generate editable attributes for a field.

```twig
<div {{ smoke.editable(entry, 'myField')|raw }}>
    {{ entry.myField }}
</div>
```

#### `smoke.editButton(entry, options)`
Generate an edit button.

```twig
{{ smoke.editButton(entry, {
    label: 'Edit Page',
    class: 'my-custom-class'
})|raw }}
```

## Development

### Requirements

- PHP 8.2+
- Craft CMS 5.4+

### Testing

1. Log in to Craft CMS
2. Visit any entry page on the frontend
3. Look for the floating "Edit Page" button
4. Click to open the edit panel
5. Make changes and save

### Customization

#### Custom Styles

Override Smoke's styles by adding custom CSS after the asset bundle:

```css
.smoke-edit-btn {
    background: your-brand-color;
}
```

#### Adding Field Types

To add support for a new field type:

1. Add the field class to `SmokeService::isFieldTypeSupported()`
2. Map it in `SmokeService::getFieldEditorType()`
3. Create editor template: `templates/_field-editors/{type}.twig`
4. Create display template: `templates/_field-editors/{type}-display.twig`

## Troubleshooting

### Edit button doesn't appear

- Ensure you're logged in with edit permissions
- Check that `smoke.init()` is called in your layout
- Verify the editor container is included

### Fields aren't editable

- Make sure `smoke.editable()` attributes are added
- Check that the field type is supported
- Verify the field exists in the entry's field layout

### Save fails

- Check PHP error logs
- Ensure the field handle is correct
- Verify you have permission to edit the entry
- Check for validation errors

## Credits

- Built with [DataStar](https://data-star.dev/)
- Inspired by [Craft DataStar Plugin](https://github.com/putyourlightson/craft-datastar)

## License

MIT

## Roadmap

See [GitHub Issues](#) for planned features and known bugs.

---

**Note**: This is a proof of concept. Use in production at your own risk. Contributions welcome!
