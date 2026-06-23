<?php

namespace justinholtweb\smoke\fieldadapters;

use craft\base\FieldInterface;
use craft\fields\Table;
use justinholtweb\smoke\base\BaseFieldAdapter;

class TableAdapter extends BaseFieldAdapter
{
    public function handles(FieldInterface $field): bool
    {
        return $field instanceof Table;
    }

    public function type(): string
    {
        return 'table';
    }

    public function prepareValue(FieldInterface $field, mixed $value): mixed
    {
        // Cells are posted as a JSON string serialized by smoke.js; decode to rows.
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }

        return $value;
    }
}
