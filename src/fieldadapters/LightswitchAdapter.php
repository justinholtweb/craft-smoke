<?php

namespace justinholtweb\smoke\fieldadapters;

use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\fields\Lightswitch;
use justinholtweb\smoke\base\BaseFieldAdapter;

class LightswitchAdapter extends BaseFieldAdapter
{
    public function handles(FieldInterface $field): bool
    {
        return $field instanceof Lightswitch;
    }

    public function type(): string
    {
        return 'lightswitch';
    }

    public function supportsInline(): bool
    {
        return true;
    }

    public function inlineConfig(ElementInterface $element, FieldInterface $field, mixed $value): ?array
    {
        return [
            'value' => (bool)$value,
            'onLabel' => $field->onLabel ?: 'On',
            'offLabel' => $field->offLabel ?: 'Off',
        ];
    }
}
