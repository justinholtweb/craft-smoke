<?php

namespace justinholtweb\smoke\models;

use craft\base\Model;

/**
 * Smoke Settings
 */
class Settings extends Model
{
    /**
     * Whether the plugin's frontend editing is enabled.
     */
    public bool $enabled = true;

    /**
     * Whether editors must hold the "Edit content on the frontend" permission
     * (SmokeService::PERMISSION_EDIT) in addition to Craft's native element edit
     * permission. Admins always pass. When false, anyone who can save the element in
     * the control panel can also edit it on the frontend.
     */
    public bool $requirePermission = false;

    protected function defineRules(): array
    {
        return [
            [['enabled', 'requirePermission'], 'boolean'],
        ];
    }
}
