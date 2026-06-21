<?php

namespace justinholtweb\smoke\events;

use craft\base\ElementInterface;
use craft\base\FieldInterface;
use yii\base\Event;

/**
 * Raised before and after a single field is saved (inline edit, or one field within a
 * panel "save all").
 *
 * On the BEFORE event you may mutate {@see $value} (it's applied to the element), or set
 * {@see $isValid} to false to skip saving this field. The AFTER event is informational.
 */
class FieldSaveEvent extends Event
{
    public ElementInterface $element;
    public FieldInterface $field;

    /** The value being saved (mutable on the before-save event). */
    public mixed $value = null;

    /** Set to false on the before-save event to skip saving this field. */
    public bool $isValid = true;
}
