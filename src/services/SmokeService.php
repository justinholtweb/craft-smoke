<?php

namespace justinholtweb\smoke\services;

use Craft;
use craft\base\Component;
use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\fields\Assets as AssetsField;
use craft\fields\Categories as CategoriesField;
use craft\fields\Entries as EntriesField;
use craft\fields\Matrix;
use craft\fields\Tags as TagsField;
use craft\fields\Users as UsersField;
use craft\helpers\Json;
use justinholtweb\smoke\base\FieldAdapterInterface;
use justinholtweb\smoke\events\DefineCanEditEvent;
use justinholtweb\smoke\events\DefineEditableFieldsEvent;
use justinholtweb\smoke\events\FieldSaveEvent;
use justinholtweb\smoke\events\RegisterFieldAdaptersEvent;
use justinholtweb\smoke\events\SaveElementEvent;
use justinholtweb\smoke\fieldadapters\DisplayFieldAdapter;
use justinholtweb\smoke\fieldadapters\DropdownAdapter;
use justinholtweb\smoke\fieldadapters\FreeLinkAdapter;
use justinholtweb\smoke\fieldadapters\HyperAdapter;
use justinholtweb\smoke\fieldadapters\LightswitchAdapter;
use justinholtweb\smoke\fieldadapters\PlainTextAdapter;
use justinholtweb\smoke\fieldadapters\RichTextAdapter;
use justinholtweb\smoke\fieldadapters\TableAdapter;
use justinholtweb\smoke\Plugin;

/**
 * Smoke Service
 *
 * Core service for on-page editing. Field-type support is provided by field adapters
 * (see {@see fieldAdapters()}); modules can register their own via
 * {@see EVENT_REGISTER_FIELD_ADAPTERS}. Lifecycle hooks are exposed as events.
 */
class SmokeService extends Component
{
    /**
     * @event RegisterFieldAdaptersEvent Register custom field adapters (takes precedence
     * over the built-ins).
     */
    public const EVENT_REGISTER_FIELD_ADAPTERS = 'registerFieldAdapters';

    /**
     * @event DefineEditableFieldsEvent Modify which fields are editable for an element.
     */
    public const EVENT_DEFINE_EDITABLE_FIELDS = 'defineEditableFields';

    /**
     * @event DefineCanEditEvent Override whether the current user can edit an element.
     */
    public const EVENT_DEFINE_CAN_EDIT = 'defineCanEdit';

    /**
     * @event FieldSaveEvent Before a single field is saved (mutate value / cancel).
     */
    public const EVENT_BEFORE_SAVE_FIELD = 'beforeSaveField';

    /**
     * @event FieldSaveEvent After a single field is saved.
     */
    public const EVENT_AFTER_SAVE_FIELD = 'afterSaveField';

    /**
     * @event SaveElementEvent Before a panel "save all" persists the element.
     */
    public const EVENT_BEFORE_SAVE_ELEMENT = 'beforeSaveElement';

    /**
     * @event SaveElementEvent After a panel "save all" persists the element.
     */
    public const EVENT_AFTER_SAVE_ELEMENT = 'afterSaveElement';

    /**
     * Permission a user needs to edit content on the frontend (when settings require it).
     */
    public const PERMISSION_EDIT = 'smoke:editFrontend';

    /** @var FieldAdapterInterface[]|null */
    private ?array $_adapters = null;

    // Permissions
    // =========================================================================

    /**
     * Whether the current user can edit the given element on the frontend.
     *
     * The default decision is: the user can save the element (Craft's native permission)
     * AND — when enabled in settings — holds the Smoke frontend-editing permission. Modules
     * can override the result via {@see EVENT_DEFINE_CAN_EDIT}.
     */
    public function canEdit(?ElementInterface $element): bool
    {
        if (!$element) {
            return false;
        }

        $user = Craft::$app->getUser()->getIdentity();

        $canEdit = $user !== null
            && Craft::$app->getElements()->canSave($element, $user)
            && (!Plugin::getInstance()->getSettings()->requirePermission || $user->can(self::PERMISSION_EDIT));

        if ($this->hasEventHandlers(self::EVENT_DEFINE_CAN_EDIT)) {
            $event = new DefineCanEditEvent([
                'element' => $element,
                'user' => $user,
                'canEdit' => $canEdit,
            ]);
            $this->trigger(self::EVENT_DEFINE_CAN_EDIT, $event);
            return $event->canEdit;
        }

        return $canEdit;
    }

    // Field adapters
    // =========================================================================

    /**
     * All field adapters, module-registered first (so they can override the built-ins).
     *
     * @return FieldAdapterInterface[]
     */
    public function fieldAdapters(): array
    {
        if ($this->_adapters !== null) {
            return $this->_adapters;
        }

        $event = new RegisterFieldAdaptersEvent();
        $this->trigger(self::EVENT_REGISTER_FIELD_ADAPTERS, $event);

        return $this->_adapters = array_merge($event->adapters, $this->builtInAdapters());
    }

    /**
     * The first adapter that handles the given field, or null if none does.
     */
    public function adapterFor(FieldInterface $field): ?FieldAdapterInterface
    {
        foreach ($this->fieldAdapters() as $adapter) {
            if ($adapter->handles($field)) {
                return $adapter;
            }
        }

        return null;
    }

    /**
     * @return FieldAdapterInterface[]
     */
    private function builtInAdapters(): array
    {
        return [
            new PlainTextAdapter(),
            new RichTextAdapter(),
            new LightswitchAdapter(),
            new DropdownAdapter(),
            new TableAdapter(),
            new FreeLinkAdapter(),
            new HyperAdapter(),
            // Display-only (surfaced in the panel, not yet editable).
            new DisplayFieldAdapter([AssetsField::class], 'assets'),
            new DisplayFieldAdapter([EntriesField::class], 'entries'),
            new DisplayFieldAdapter([CategoriesField::class], 'categories'),
            new DisplayFieldAdapter([TagsField::class], 'tags'),
            new DisplayFieldAdapter([UsersField::class], 'users'),
            new DisplayFieldAdapter([Matrix::class], 'matrix'),
        ];
    }

    /**
     * Whether a field is supported for editing (i.e. an adapter handles it).
     */
    public function isFieldSupported(FieldInterface $field): bool
    {
        return $this->adapterFor($field) !== null;
    }

    /**
     * The Smoke editor type for a field (e.g. 'plaintext'), or 'unknown'.
     */
    public function getFieldEditorType(FieldInterface $field): string
    {
        return $this->adapterFor($field)?->type() ?? 'unknown';
    }

    /**
     * Render the panel editor HTML for a field.
     */
    public function renderFieldEditor(ElementInterface $element, FieldInterface $field, mixed $value): string
    {
        return $this->adapterFor($field)?->renderEditor($element, $field, $value) ?? '';
    }

    /**
     * The live-refresh display value for a field, or null.
     */
    public function displayValue(FieldInterface $field, mixed $value): ?array
    {
        return $this->adapterFor($field)?->displayValue($field, $value);
    }

    /**
     * Transform a posted value into the shape the field's setFieldValue() expects.
     */
    public function prepareValueForSave(FieldInterface $field, mixed $value): mixed
    {
        return $this->adapterFor($field)?->prepareValue($field, $value) ?? $value;
    }

    // Editable fields + attributes
    // =========================================================================

    /**
     * Get editable field metadata for an element.
     *
     * @return array[] Each: ['handle', 'name', 'type', 'required', 'instructions'].
     */
    public function getEditableFields(ElementInterface $element): array
    {
        $fieldLayout = $element->getFieldLayout();
        $editableFields = [];

        if ($fieldLayout) {
            foreach ($fieldLayout->getCustomFields() as $field) {
                $adapter = $this->adapterFor($field);
                if ($adapter) {
                    $editableFields[] = [
                        'handle' => $field->handle,
                        'name' => $field->name,
                        'type' => $adapter->type(),
                        'required' => (bool)$field->required,
                        'instructions' => $field->instructions,
                    ];
                }
            }
        }

        if ($this->hasEventHandlers(self::EVENT_DEFINE_EDITABLE_FIELDS)) {
            $event = new DefineEditableFieldsEvent([
                'element' => $element,
                'fields' => $editableFields,
            ]);
            $this->trigger(self::EVENT_DEFINE_EDITABLE_FIELDS, $event);
            return $event->fields;
        }

        return $editableFields;
    }

    /**
     * Generate the data-smoke-* attributes for an editable field.
     */
    public function getEditableAttributes(ElementInterface $element, string $fieldHandle): array
    {
        if (!$this->canEdit($element)) {
            return [];
        }

        $field = $element->getFieldLayout()?->getFieldByHandle($fieldHandle);
        $adapter = $field ? $this->adapterFor($field) : null;

        if (!$field || !$adapter) {
            return [];
        }

        $attributes = [
            'data-smoke-editable' => 'true',
            'data-smoke-element-id' => $element->id,
            'data-smoke-field' => $fieldHandle,
            'data-smoke-type' => $adapter->type(),
        ];

        if ($adapter->supportsInline()) {
            $config = $adapter->inlineConfig($element, $field, $element->getFieldValue($fieldHandle));
            if ($config !== null) {
                $attributes['data-smoke-config'] = Json::encode($config);
            }
        }

        return $attributes;
    }

    // Saving
    // =========================================================================

    /**
     * Save a single field on an element, firing before/after events.
     */
    public function saveField(ElementInterface $element, FieldInterface $field, mixed $value): bool
    {
        $value = $this->prepareValueForSave($field, $value);

        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_FIELD)) {
            $event = new FieldSaveEvent(['element' => $element, 'field' => $field, 'value' => $value]);
            $this->trigger(self::EVENT_BEFORE_SAVE_FIELD, $event);
            if (!$event->isValid) {
                return false;
            }
            $value = $event->value;
        }

        $element->setFieldValue($field->handle, $value);
        $success = Craft::$app->getElements()->saveElement($element, false);

        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_FIELD)) {
            $this->trigger(self::EVENT_AFTER_SAVE_FIELD, new FieldSaveEvent([
                'element' => $element,
                'field' => $field,
                'value' => $value,
                'isValid' => $success,
            ]));
        }

        return $success;
    }

    /**
     * Save several fields on an element (panel "save all"), firing before/after events.
     *
     * @param array<string, mixed> $values Map of fieldHandle => posted value.
     */
    public function saveAllFields(ElementInterface $element, array $values): bool
    {
        $fieldLayout = $element->getFieldLayout();

        $prepared = [];
        foreach ($values as $handle => $value) {
            $field = $fieldLayout?->getFieldByHandle($handle);
            $prepared[$handle] = $field ? $this->prepareValueForSave($field, $value) : $value;
        }

        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_ELEMENT)) {
            $event = new SaveElementEvent(['element' => $element, 'values' => $prepared]);
            $this->trigger(self::EVENT_BEFORE_SAVE_ELEMENT, $event);
            if (!$event->isValid) {
                return false;
            }
            $prepared = $event->values;
        }

        foreach ($prepared as $handle => $value) {
            $element->setFieldValue($handle, $value);
        }

        $success = Craft::$app->getElements()->saveElement($element, false);

        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_ELEMENT)) {
            $this->trigger(self::EVENT_AFTER_SAVE_ELEMENT, new SaveElementEvent([
                'element' => $element,
                'values' => $prepared,
                'success' => $success,
            ]));
        }

        return $success;
    }
}
