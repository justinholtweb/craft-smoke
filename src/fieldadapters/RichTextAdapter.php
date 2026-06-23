<?php

namespace justinholtweb\smoke\fieldadapters;

use craft\base\ElementInterface;
use craft\base\FieldInterface;
use justinholtweb\smoke\base\BaseFieldAdapter;

/**
 * Handles any field extending Craft's HtmlField — CKEditor, Redactor, and any other
 * plugin's HTML field — with the contenteditable WYSIWYG editor.
 */
class RichTextAdapter extends BaseFieldAdapter
{
    public function handles(FieldInterface $field): bool
    {
        return is_a($field, 'craft\\htmlfield\\HtmlField');
    }

    public function type(): string
    {
        return 'richtext';
    }

    public function supportsInline(): bool
    {
        return true;
    }

    public function inlineConfig(ElementInterface $element, FieldInterface $field, mixed $value): ?array
    {
        // Seed from the RAW content so ref tags ({asset:123:url}) survive the save.
        return [
            'raw' => (is_object($value) && method_exists($value, 'getRawContent'))
                ? $value->getRawContent()
                : (string)$value,
        ];
    }

    public function displayValue(FieldInterface $field, mixed $value): ?array
    {
        return ['value' => (string)$value, 'html' => true];
    }
}
