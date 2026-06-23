<?php

namespace justinholtweb\smoke\events;

use craft\base\ElementInterface;
use craft\elements\User;
use yii\base\Event;

/**
 * Raised when Smoke decides whether the current user may edit an element on the frontend.
 *
 * {@see $canEdit} is seeded with Smoke's own decision (native element save permission +
 * the Smoke frontend-editing permission). Set it to true/false to override.
 */
class DefineCanEditEvent extends Event
{
    public ElementInterface $element;
    public ?User $user = null;
    public bool $canEdit = false;
}
