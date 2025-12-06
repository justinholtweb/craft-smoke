# Smoke Plugin - Quick Start Guide

## Installation Commands

Run these commands in your terminal:

```bash
# 1. Install the Smoke plugin via Composer
composer require justinholtweb/smoke
# or with DDEV:
# ddev composer require justinholtweb/smoke

# 2. Install the plugin in Craft
php craft plugin/install smoke
# or with DDEV:
# ddev craft plugin/install smoke

# 3. Clear caches
php craft clear-caches/all
# or with DDEV:
# ddev craft clear-caches/all
```

## Minimal Integration Test

To test if Smoke is working, add this to `templates/_layout.twig`:

### Before `</head>`:
```twig
{% do smoke.init() %}
```

### Before `</body>`:
```twig
{% if currentUser %}
    <div
        id="smoke-editor"
        data-smoke-open="false"
    ></div>

    {% if entry is defined and smoke.canEdit(entry) %}
        {{ smoke.editButton(entry)|raw }}
    {% endif %}
{% endif %}
```

## Test on a Simple Page

Create a test template at `templates/test-smoke.twig`:

```twig
{% extends '_layout' %}

{% block content %}
    <div style="padding: 40px; max-width: 800px; margin: 0 auto;">
        <h1 {{ smoke.editable(entry, 'title')|raw }}>
            {{ entry.title }}
        </h1>

        <p style="color: #666; margin-top: 20px;">
            If you're logged in, you should see an "Edit Page" button in the bottom-right corner.
            Click it to test the Smoke editor!
        </p>
    </div>
{% endblock %}
```

Then:
1. Create a new entry in Craft that uses this template
2. Visit the entry on the frontend while logged in
3. You should see the "Edit Page" button
4. Click it to open the editor panel

## Verify Installation

Check that the plugin assets are available:

```bash
ls -la vendor/justinholtweb/smoke/src/
ls -la vendor/justinholtweb/smoke/src/assetbundles/smoke/dist/
```

You should see:
- `smoke.css` - Styles for the editor
- `smoke.js` - JavaScript for interactions

## Common Issues

### "Class not found" error

Run:
```bash
ddev composer dump-autoload
```

### Assets not loading

Run:
```bash
ddev craft clear-caches/all
```

### DataStar not loaded

Check the page source and verify you see:
```html
<script src="https://cdn.jsdelivr.net/npm/@sudodevnull/datastar@1.0.0-beta.6/dist/datastar.min.js"></script>
```

## Next Steps

Once the basic installation works:

1. Read [INTEGRATION_EXAMPLE.md](INTEGRATION_EXAMPLE.md) for detailed integration examples
2. Review [README.md](README.md) for full documentation
3. Start adding `smoke.editable()` to your component templates

## Debug Mode

To see what's happening, open browser console (F12) and check:

1. **Network tab**: Look for requests to `/actions/smoke/edit/open`
2. **Console tab**: Check for any JavaScript errors
3. **Elements tab**: Inspect elements with `[data-smoke-editable]` attributes

## Support

If something doesn't work:

1. Check PHP error logs: `ddev logs`
2. Check browser console
3. Verify you're logged in with edit permissions
4. Make sure the entry has a field layout with supported fields
