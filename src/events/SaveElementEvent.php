<?php

namespace justinholtweb\smoke\events;

use craft\base\ElementInterface;
use yii\base\Event;

/**
 * Raised before and after a panel "save all" persists an element.
 *
 * On the BEFORE event you may mutate {@see $values} (a map of fieldHandle => value to be
 * applied), or set {@see $isValid} to false to abort the save. The AFTER event reports
 * {@see $success}.
 */
class SaveElementEvent extends Event
{
    public ElementInterface $element;

    /** @var array<string, mixed> Map of fieldHandle => value (mutable on the before event). */
    public array $values = [];

    /** Set to false on the before event to abort the save. */
    public bool $isValid = true;

    /** Whether the element saved successfully (after event only). */
    public bool $success = false;
}
