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
use craft\ckeditor\Field as CKEditorField;

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
        $supportedTypes = [
            PlainText::class,
            CKEditorField::class,
            AssetsField::class,
            EntriesField::class,
            CategoriesField::class,
            TagsField::class,
            UsersField::class,
            Lightswitch::class,
            Dropdown::class,
            Table::class,
            Matrix::class,
        ];

        return in_array($fieldClass, $supportedTypes, true);
    }

    /**
     * Get the editor type identifier for a field class
     */
    public function getFieldEditorType(string $fieldClass): string
    {
        $typeMap = [
            PlainText::class => 'plaintext',
            CKEditorField::class => 'richtext',
            AssetsField::class => 'assets',
            EntriesField::class => 'entries',
            CategoriesField::class => 'categories',
            TagsField::class => 'tags',
            UsersField::class => 'users',
            Lightswitch::class => 'lightswitch',
            Dropdown::class => 'dropdown',
            Table::class => 'table',
            Matrix::class => 'matrix',
        ];

        return $typeMap[$fieldClass] ?? 'unknown';
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

        return [
            'data-smoke-editable' => 'true',
            'data-smoke-element-id' => $element->id,
            'data-smoke-field' => $fieldHandle,
            'data-smoke-type' => $fieldType,
        ];
    }
}
