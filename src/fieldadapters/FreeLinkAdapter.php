<?php

namespace justinholtweb\smoke\fieldadapters;

use craft\base\FieldInterface;
use justinholtweb\smoke\base\BaseFieldAdapter;

/**
 * FreeLink (justinholtweb/craft-freelink). The field's normalizeValue() auto-wraps a
 * single-link array with a top-level `type`, so no value transform is needed here.
 */
class FreeLinkAdapter extends BaseFieldAdapter
{
    public function handles(FieldInterface $field): bool
    {
        return is_a($field, 'justinholtweb\\freelink\\fields\\FreeLinkField');
    }

    public function type(): string
    {
        return 'freelink';
    }
}
