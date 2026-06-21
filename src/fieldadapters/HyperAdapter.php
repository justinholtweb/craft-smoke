<?php

namespace justinholtweb\smoke\fieldadapters;

use craft\base\FieldInterface;
use justinholtweb\smoke\base\BaseFieldAdapter;

/**
 * Hyper (verbb/hyper). Wraps the posted {linkValue, linkText, newWindow} into Hyper's
 * list-of-blocks shape with the URL link type.
 *
 * Note: the Hyper field must have its link types configured (saved once in the CP) or it
 * silently drops links.
 */
class HyperAdapter extends BaseFieldAdapter
{
    public function handles(FieldInterface $field): bool
    {
        return is_a($field, 'verbb\\hyper\\fields\\HyperField');
    }

    public function type(): string
    {
        return 'hyper';
    }

    public function prepareValue(FieldInterface $field, mixed $value): mixed
    {
        if (is_array($value)) {
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
}
