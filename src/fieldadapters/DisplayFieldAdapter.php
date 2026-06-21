<?php

namespace justinholtweb\smoke\fieldadapters;

use craft\base\FieldInterface;
use justinholtweb\smoke\base\BaseFieldAdapter;

/**
 * A configurable, display-only adapter for relational/complex field types that Smoke can
 * surface in the panel but not yet edit (Assets, Entries, Categories, Tags, Users, Matrix).
 *
 * Constructed with the field classes it handles and the editor type (template) to use.
 */
class DisplayFieldAdapter extends BaseFieldAdapter
{
    /**
     * @param string[] $fieldClasses Field class FQCNs this adapter handles.
     * @param string $type Editor type / template name under smoke/_field-editors/.
     */
    public function __construct(
        private array $fieldClasses,
        private string $type,
    ) {
    }

    public function handles(FieldInterface $field): bool
    {
        foreach ($this->fieldClasses as $class) {
            if (is_a($field, $class)) {
                return true;
            }
        }

        return false;
    }

    public function type(): string
    {
        return $this->type;
    }
}
