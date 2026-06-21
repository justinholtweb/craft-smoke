<?php

namespace justinholtweb\smoke\events;

use craft\base\ElementInterface;
use yii\base\Event;

/**
 * Raised after Smoke determines which fields are editable for an element, so modules can
 * add, remove, or reorder them.
 *
 * Each entry in {@see $fields} is an array: ['handle', 'name', 'type', 'required', 'instructions'].
 */
class DefineEditableFieldsEvent extends Event
{
    public ElementInterface $element;

    /** @var array[] */
    public array $fields = [];
}
