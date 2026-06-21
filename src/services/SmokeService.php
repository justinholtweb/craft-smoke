<?php

namespace justinholtweb\smoke\services;

use Craft;
use craft\base\Component;
use craft\base\ElementInterface;
use craft\elements\Entry;
use craft\elements\Asset;
use craft\fields\PlainText;
use craft\fields\Assets as AssetsField;
use craft\fields\Entries as EntriesField;
use craft\fields\Categories as CategoriesField;
use craft\fields\Tags as TagsField;
use craft\fields\Users as UsersField;
use craft\fields\Lightswitch;
use craft\fields\Dropdown;
use craft\fields\Table;
use craft\fields\Matrix;
use craft\helpers\Json;

/**
 * Smoke Service
 *
 * Core service for managing on-page editing functionality
 */
class SmokeService extends Component
{
    /**
     * Check if the current user can edit an entry
     */
    public function canEdit(ElementInterface $element): bool
    {
        $user = Craft::$app->getUser()->getIdentity();

        if (!$user) {
            return false;
        }

        // Check if user can edit this entry
        return Craft::$app->getElements()->canSave($element, $user);
    }

    /**
     * Get editable fields for an element
     */
    public function getEditableFields(ElementInterface $element): array
    {
        $fieldLayout = $element->getFieldLayout();

        if (!$fieldLayout) {
            return [];
        }

        $editableFields = [];

        foreach ($fieldLayout->getCustomFields() as $field) {
            $fieldType = get_class($field);

            // Only include supported field types
            if ($this->isFieldTypeSupported($fieldType)) {
                $editableFields[] = [
                    'handle' => $field->handle,
                    'name' => $field->name,
                    'type' => $this->getFieldEditorType($fieldType),
                    'required' => (bool)$field->required,
                    'instructions' => $field->instructions,
                ];
            }
        }

        return $editableFields;
    }

    /**
     * Check if a field type is supported for editing
     */
    public function isFieldTypeSupported(string $fieldClass): bool
    {
        return array_key_exists($fieldClass, $this->fieldTypeMap());
    }

    /**
     * Get the editor type identifier for a field class
     */
    public function getFieldEditorType(string $fieldClass): string
    {
        return $this->fieldTypeMap()[$fieldClass] ?? 'unknown';
    }

    /**
     * Map of field class (FQCN) => Smoke editor type.
     *
     * Third-party field types (CKEditor, Redactor, FreeLink, Hyper) are referenced by
     * string FQCN rather than `::class`, so Smoke carries no hard dependency on those
     * plugins being installed — absent classes simply never match a field on the page.
     */
    private function fieldTypeMap(): array
    {
        return [
            // Core Craft fields
            PlainText::class => 'plaintext',
            Lightswitch::class => 'lightswitch',
            Dropdown::class => 'dropdown',
            Table::class => 'table',
            AssetsField::class => 'assets',
            EntriesField::class => 'entries',
            CategoriesField::class => 'categories',
            TagsField::class => 'tags',
            UsersField::class => 'users',
            Matrix::class => 'matrix',
            // Third-party fields (string FQCNs — no hard dependency)
            'craft\\ckeditor\\Field' => 'richtext',
            'craft\\redactor\\Field' => 'richtext',
            'justinholtweb\\freelink\\fields\\FreeLinkField' => 'freelink',
            'verbb\\hyper\\fields\\HyperField' => 'hyper',
        ];
    }

    /**
     * Transform a posted value into the shape a field's setFieldValue() expects.
     *
     * Most fields accept the posted value directly. Hyper needs its list-of-blocks shape
     * (see verbb\hyper — normalizeValue iterates the outer array as link blocks), so we wrap
     * the posted {linkValue, linkText, newWindow} into a single URL-type link block.
     */
    public function prepareValueForSave($field, mixed $value): mixed
    {
        $type = $this->getFieldEditorType(get_class($field));

        if ($type === 'hyper' && is_array($value)) {
            return [
                [
                    'type' => 'verbb\\hyper\\links\\Url',
                    'handle' => 'default-verbb-hyper-links-url',
                    'linkValue' => $value['linkValue'] ?? '',
                    'linkText' => $value['linkText'] ?? '',
                    'newWindow' => !empty($value['newWindow']),
                    'fields' => [],
                ],
            ];
        }

        return $value;
    }

    /**
     * Save field data to an entry
     */
    public function saveFieldData(int $entryId, string $fieldHandle, mixed $value): bool
    {
        $entry = Entry::find()->id($entryId)->one();

        if (!$entry || !$this->canEdit($entry)) {
            return false;
        }

        $entry->setFieldValue($fieldHandle, $value);

        return Craft::$app->getElements()->saveElement($entry, false);
    }

    /**
     * Generate DataStar attributes for an editable field
     */
    public function getEditableAttributes(ElementInterface $element, string $fieldHandle): array
    {
        if (!$this->canEdit($element)) {
            return [];
        }

        $field = $element->getFieldLayout()?->getFieldByHandle($fieldHandle);

        if (!$field) {
            return [];
        }

        $fieldType = $this->getFieldEditorType(get_class($field));

        $attributes = [
            'data-smoke-editable' => 'true',
            'data-smoke-element-id' => $element->id,
            'data-smoke-field' => $fieldHandle,
            'data-smoke-type' => $fieldType,
        ];

        // Type-specific config the inline editor (smoke.js) needs to render in place.
        $config = $this->inlineConfig($field, $fieldType, $element->getFieldValue($fieldHandle));
        if ($config !== null) {
            $attributes['data-smoke-config'] = Json::encode($config);
        }

        return $attributes;
    }

    /**
     * Build the config blob an inline editor needs for a given field, or null if the
     * field type has no inline editor (panel-only / display-only).
     */
    private function inlineConfig($field, string $fieldType, mixed $value): ?array
    {
        return match ($fieldType) {
            'plaintext' => [
                'multiline' => (bool)($field->multiline ?? false),
            ],
            'dropdown' => [
                'value' => (string)$value,
                'options' => array_map(
                    fn($opt) => ['value' => $opt['value'] ?? '', 'label' => $opt['label'] ?? ($opt['value'] ?? '')],
                    array_values(array_filter($field->options ?? [], fn($opt) => !($opt['optgroup'] ?? false)))
                ),
            ],
            'lightswitch' => [
                'value' => (bool)$value,
                'onLabel' => $field->onLabel ?: 'On',
                'offLabel' => $field->offLabel ?: 'Off',
            ],
            // Seed inline rich-text editing from the RAW content so ref tags survive
            // (the rendered DOM only has parsed HTML with refs already resolved).
            'richtext' => [
                'raw' => (is_object($value) && method_exists($value, 'getRawContent'))
                    ? $value->getRawContent()
                    : (string)$value,
            ],
            default => null,
        };
    }
}
