# Smoke Integration Example

This guide shows how to integrate Smoke into your CKC website.

## Step 1: Install the Plugin

Since DDEV/composer commands aren't available in this environment, manually run:

```bash
ddev composer update yourhandle/smoke
ddev craft plugin/install smoke
```

## Step 2: Update Your Layout Template

Edit `templates/_layout.twig` and add Smoke initialization:

```twig
{# At the top of your layout, after opening <html> tag #}
{% do smoke.init() %}

<!DOCTYPE html>
<html lang="en">
<head>
    {# ... your existing head content ... #}
</head>
<body>
    {# ... your existing body content ... #}

    {# Before closing </body> tag, add the Smoke editor container #}
    {% include 'smoke/_components/editor-container.twig' %}
</body>
</html>
```

## Step 3: Make Your Components Editable

### Example 1: Text Block Component

Edit `templates/_components/textBlock.twig`:

```twig
{# Original #}
<div class="text-block">
    <h2>{{ component.heading }}</h2>
    <div>{{ component.bodyContent }}</div>
</div>

{# With Smoke #}
<div class="text-block">
    <h2 {{ smoke.editable(component, 'heading')|raw }}>
        {{ component.heading }}
    </h2>
    <div {{ smoke.editable(component, 'bodyContent')|raw }}>
        {{ component.bodyContent }}
    </div>
</div>
```

### Example 2: Hero Component

Edit `templates/_components/hero.twig`:

```twig
{# Add editable attributes to key fields #}
<section class="hero">
    <h1 {{ smoke.editable(component, 'heroHeading')|raw }}>
        {{ component.heroHeading }}
    </h1>

    <div {{ smoke.editable(component, 'heroSubheading')|raw }}>
        {{ component.heroSubheading }}
    </div>

    {# Asset fields can be marked editable (display only for now) #}
    <div {{ smoke.editable(component, 'heroImage')|raw }}>
        {% if component.heroImage|length %}
            <img src="{{ component.heroImage.one().url }}" alt="{{ component.heroImage.one().title }}">
        {% endif %}
    </div>
</section>
```

### Example 3: Entry Page

Edit `templates/_pages/entry.twig`:

```twig
{# Make the entry itself editable #}
<article>
    <h1 {{ smoke.editable(entry, 'title')|raw }}>
        {{ entry.title }}
    </h1>

    {# For Matrix fields (pageComponents), each block can be made editable #}
    {% for component in entry.pageComponents.all() %}
        {# Component router will handle rendering, but you can wrap individual fields #}
        {% include '_routerComponents.twig' %}
    {% endfor %}
</article>
```

## Step 4: Testing

1. **Login** to your Craft CMS control panel
2. **Visit** any entry page on your frontend (http://ckc.ddev.site)
3. **Look for** the floating "Edit Page" button in the bottom-right corner
4. **Click** the button to open the edit panel
5. **Make changes** to any field
6. **Press** the "Save Changes" button or use Cmd/Ctrl+S

## What Works Now (POC)

### Fully Functional Fields
- ✅ **Plain Text** - Single-line text inputs
- ✅ **Lightswitch** - Toggle switches
- ✅ **Dropdown** - Select dropdowns
- ✅ **Table** - Editable table with add/remove rows

### Partially Functional
- 🟡 **Rich Text** - Uses textarea (full CKEditor coming soon)

### Display Only (Coming Soon)
- 🚧 **Assets** - Shows current assets, selection UI coming
- 🚧 **Entries/Categories/Tags/Users** - Shows relationships, picker coming
- 🚧 **Matrix** - Shows blocks, editing coming

## Recommended Starting Point

For your POC, focus on these components that have simple field types:

### 1. Text Block Component
File: `templates/_components/textBlock.twig`

Best for testing plain text and rich text fields.

### 2. Side by Side Card
File: `templates/_components/sideBySideCard.twig`

Good for testing multiple field types in one component.

### 3. Entry Title
In any entry template, the title field is always editable and works great for testing.

## Advanced: Making Matrix Blocks Editable

While full Matrix editing isn't implemented yet, you can make individual fields within Matrix blocks editable:

```twig
{# In your component router or individual component #}
{% for component in entry.pageComponents.all() %}
    {% switch component.type.handle %}
        {% case 'textBlock' %}
            <div class="text-block">
                {# Make the Matrix block fields editable #}
                <h2 {{ smoke.editable(component, 'heading')|raw }}>
                    {{ component.heading }}
                </h2>
                <div {{ smoke.editable(component, 'text')|raw }}>
                    {{ component.text }}
                </div>
            </div>
    {% endswitch %}
{% endfor %}
```

## Troubleshooting

### "Edit Page" button doesn't show
- Make sure you're logged in to Craft
- Verify you have permission to edit the entry
- Check that `smoke.init()` is called in your layout

### Fields aren't editable
- Ensure `smoke.editable()` is added to the element
- Check that the field exists in the field layout
- Verify the field type is supported

### Save doesn't work
- Check browser console for errors
- Ensure DataStar is loaded (check Network tab)
- Verify CSRF token is present in the form

### Styles look broken
- Clear Craft caches: `ddev craft clear-caches/all`
- Ensure Smoke assets are loaded (check page source)
- Check for CSS conflicts with your existing styles

## Next Steps for Development

After the POC is working:

1. **Full CKEditor Integration** - Replace textarea with actual CKEditor
2. **Asset Selector** - Build asset picker UI with upload
3. **Relationship Pickers** - Entry/category/tag selection
4. **Matrix Block Editing** - Add, reorder, delete blocks
5. **Live Preview** - Real-time updates without reload
6. **Auto-save** - Periodic saving of changes
7. **Revision History** - Track changes over time

## Questions?

- Check the main [README.md](README.md)
- Review the code in `src/` directory
- Look at field editor examples in `src/templates/_field-editors/`
