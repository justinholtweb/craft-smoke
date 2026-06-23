# Extending Smoke

Smoke is built around **field adapters** and **events**, so a module or plugin can add
support for any field type (including ones exposed by other plugins), hook into the
edit/save lifecycle, and control who may edit.

## Contents

- [Field adapters](#field-adapters) — support a new field type
- [Lifecycle events (PHP)](#lifecycle-events-php)
- [Client-side events (JS)](#client-side-events-js)
- [Permissions](#permissions)

---

## Field adapters

A **field adapter** teaches Smoke how to edit one (or a family of) Craft field type(s).
Every built-in type is an adapter, and you register your own the same way they're
registered internally — so anything the built-ins can do, your adapter can too.

Implement `justinholtweb\smoke\base\FieldAdapterInterface`, or extend
`BaseFieldAdapter` and override only what you need. At minimum, override `handles()`
and `type()`.

```php
namespace mymodule\smoke;

use craft\base\ElementInterface;
use craft\base\FieldInterface;
use justinholtweb\smoke\base\BaseFieldAdapter;
use mymodule\fields\ColorField;

class ColorAdapter extends BaseFieldAdapter
{
    public function handles(FieldInterface $field): bool
    {
        return $field instanceof ColorField;
    }

    public function type(): string
    {
        return 'color';
    }

    public function supportsInline(): bool
    {
        return true;
    }

    // Config passed to the inline editor (serialized to data-smoke-config).
    public function inlineConfig(ElementInterface $element, FieldInterface $field, mixed $value): ?array
    {
        return ['value' => (string)$value];
    }

    // Transform the posted value into what setFieldValue() expects (default: passthrough).
    public function prepareValue(FieldInterface $field, mixed $value): mixed
    {
        return $value;
    }

    // The on-page value to refresh after a panel save, or null.
    public function displayValue(FieldInterface $field, mixed $value): ?array
    {
        return ['value' => (string)$value, 'html' => false];
    }

    // Render the panel editor. The default renders smoke/_field-editors/{type}.twig in CP
    // mode; point at your own template instead:
    protected function editorTemplate(): string
    {
        return 'my-module/smoke/color';
    }

    protected function editorTemplateMode(): string
    {
        return \craft\web\View::TEMPLATE_MODE_SITE;
    }
}
```

Your editor template should bind its input to the `fields.<handle>` DataStar signal so
the panel "save all" picks it up:

```twig
<div class="smoke-field-group">
    <label class="smoke-field-label">{{ field.name }}</label>
    <input type="color" data-bind="fields.{{ fieldHandle }}" value="{{ value }}">
</div>
```

Register it from your module's `init()`:

```php
use justinholtweb\smoke\events\RegisterFieldAdaptersEvent;
use justinholtweb\smoke\services\SmokeService;
use yii\base\Event;

Event::on(
    SmokeService::class,
    SmokeService::EVENT_REGISTER_FIELD_ADAPTERS,
    function (RegisterFieldAdaptersEvent $event) {
        $event->adapters[] = new \mymodule\smoke\ColorAdapter();
    }
);
```

Registered adapters take **precedence** over the built-ins (first match wins), so you can
also override how Smoke handles a built-in field type.

> **Note on inline editing:** the built-in inline editors (`plaintext`, `dropdown`,
> `lightswitch`, `richtext`) are implemented in `smoke.js`. A custom `type()` will work in
> the **panel** out of the box; full inline support for a new type also needs client-side
> JS that recognizes the type. Until then, return `false` from `supportsInline()`.

---

## Lifecycle events (PHP)

All on `justinholtweb\smoke\services\SmokeService`.

| Constant | Event class | When |
|---|---|---|
| `EVENT_REGISTER_FIELD_ADAPTERS` | `RegisterFieldAdaptersEvent` | Register custom adapters |
| `EVENT_DEFINE_EDITABLE_FIELDS` | `DefineEditableFieldsEvent` | Add/remove/reorder editable fields for an element |
| `EVENT_DEFINE_CAN_EDIT` | `DefineCanEditEvent` | Override the edit-permission decision |
| `EVENT_BEFORE_SAVE_FIELD` | `FieldSaveEvent` | Before a single field saves (mutate value / cancel) |
| `EVENT_AFTER_SAVE_FIELD` | `FieldSaveEvent` | After a single field saves |
| `EVENT_BEFORE_SAVE_ELEMENT` | `SaveElementEvent` | Before a panel "save all" (mutate values / abort) |
| `EVENT_AFTER_SAVE_ELEMENT` | `SaveElementEvent` | After a panel "save all" |

```php
use justinholtweb\smoke\events\FieldSaveEvent;
use justinholtweb\smoke\services\SmokeService;
use yii\base\Event;

// Validate / transform a value before it's saved, or cancel the save.
Event::on(
    SmokeService::class,
    SmokeService::EVENT_BEFORE_SAVE_FIELD,
    function (FieldSaveEvent $e) {
        if ($e->field->handle === 'slug') {
            $e->value = \craft\helpers\StringHelper::slugify($e->value);
        }
        // $e->isValid = false; // to skip saving this field
    }
);
```

```php
use justinholtweb\smoke\events\DefineEditableFieldsEvent;

// Hide a field from the editor.
Event::on(
    SmokeService::class,
    SmokeService::EVENT_DEFINE_EDITABLE_FIELDS,
    function (DefineEditableFieldsEvent $e) {
        $e->fields = array_values(array_filter(
            $e->fields,
            fn($f) => $f['handle'] !== 'internalNotes'
        ));
    }
);
```

---

## Client-side events (JS)

`smoke.js` dispatches bubbling `CustomEvent`s on `document`, useful for wiring custom
editor widgets (e.g. a map picker, a real CKEditor) or analytics.

| Event | `detail` | When |
|---|---|---|
| `smoke:panel-open` | `{ editor }` | The slide-out panel opened |
| `smoke:panel-close` | `{ editor }` | The panel closed |
| `smoke:edit-start` | `{ element, type, elementId, fieldHandle }` | Inline edit began |
| `smoke:before-save` | `{ elementId, fieldHandle, value }` | Inline save started |
| `smoke:after-save` | `{ elementId, fieldHandle, value, data }` | Inline save succeeded |
| `smoke:error` | `{ elementId, fieldHandle, error }` | Inline save failed |
| `smoke:saved` | `{ fields }` | A panel "save all" was applied on-page |

```js
// Initialize a custom widget when its editor morphs into the panel.
document.addEventListener('smoke:panel-open', () => {
    document.querySelectorAll('[data-my-color]:not([data-ready])').forEach(initColorPicker);
});
```

---

## Permissions

By default Smoke uses Craft's native element permission: if a user can save the element in
the control panel, they can edit it on the frontend.

Smoke also registers a permission, **"Edit content on the frontend"**
(`SmokeService::PERMISSION_EDIT`). Turn on **Settings → Require permission** to require it
in addition to the native permission (admins always pass).

For custom rules, listen to `EVENT_DEFINE_CAN_EDIT`:

```php
use justinholtweb\smoke\events\DefineCanEditEvent;
use justinholtweb\smoke\services\SmokeService;
use yii\base\Event;

Event::on(
    SmokeService::class,
    SmokeService::EVENT_DEFINE_CAN_EDIT,
    function (DefineCanEditEvent $e) {
        // e.g. only allow frontend editing of the user's own authored entries
        if ($e->element instanceof \craft\elements\Entry && $e->user) {
            $e->canEdit = $e->canEdit && $e->element->getAuthorIds() === [$e->user->id];
        }
    }
);
```
