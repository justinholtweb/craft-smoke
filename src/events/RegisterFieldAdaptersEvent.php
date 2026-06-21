<?php

namespace justinholtweb\smoke\events;

use justinholtweb\smoke\base\FieldAdapterInterface;
use yii\base\Event;

/**
 * Raised so modules can register custom field adapters.
 *
 * Adapters added here take precedence over the built-ins (first match wins), so you can
 * also override how Smoke handles a built-in field type.
 *
 * ```php
 * Event::on(
 *     SmokeService::class,
 *     SmokeService::EVENT_REGISTER_FIELD_ADAPTERS,
 *     function (RegisterFieldAdaptersEvent $e) {
 *         $e->adapters[] = new MyColorFieldAdapter();
 *     }
 * );
 * ```
 */
class RegisterFieldAdaptersEvent extends Event
{
    /** @var FieldAdapterInterface[] */
    public array $adapters = [];
}
