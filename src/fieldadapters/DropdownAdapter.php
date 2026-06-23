<?php

namespace justinholtweb\smoke\fieldadapters;

use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\fields\Dropdown;
use justinholtweb\smoke\base\BaseFieldAdapter;

class DropdownAdapter extends BaseFieldAdapter
{
    public function handles(FieldInterface $field): bool
    {
        return $field instanceof Dropdown;
    }

    public function type(): string
    {
        return 'dropdown';
    }

    public function supportsInline(): bool
    {
        return true;
    }

    public function inlineConfig(ElementInterface $element, FieldInterface $field, mixed $value): ?array
    {
        return [
            'value' => (string)$value,
            'options' => array_map(
                fn($opt) => ['value' => $opt['value'] ?? '', 'label' => $opt['label'] ?? ($opt['value'] ?? '')],
                array_values(array_filter($field->options ?? [], fn($opt) => !($opt['optgroup'] ?? false)))
            ),
        ];
    }

    public function displayValue(FieldInterface $field, mixed $value): ?array
    {
        // Dropdown displays its option label, not the stored value.
        return ['value' => (string)($value->label ?? $value), 'html' => false];
    }
}
