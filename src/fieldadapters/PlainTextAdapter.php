<?php

namespace justinholtweb\smoke\fieldadapters;

use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\fields\PlainText;
use justinholtweb\smoke\base\BaseFieldAdapter;

class PlainTextAdapter extends BaseFieldAdapter
{
    public function handles(FieldInterface $field): bool
    {
        return $field instanceof PlainText;
    }

    public function type(): string
    {
        return 'plaintext';
    }

    public function supportsInline(): bool
    {
        return true;
    }

    public function inlineConfig(ElementInterface $element, FieldInterface $field, mixed $value): ?array
    {
        return ['multiline' => (bool)($field->multiline ?? false)];
    }

    public function displayValue(FieldInterface $field, mixed $value): ?array
    {
        return ['value' => (string)$value, 'html' => false];
    }
}
