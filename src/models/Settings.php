<?php

namespace justinholtweb\smoke\models;

use craft\base\Model;

/**
 * Smoke Settings
 */
class Settings extends Model
{
    public bool $enabled = true;

    protected function defineRules(): array
    {
        return [
            ['enabled', 'boolean'],
        ];
    }
}
