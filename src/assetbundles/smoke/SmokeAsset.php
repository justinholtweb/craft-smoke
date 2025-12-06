<?php

namespace justinholtweb\smoke\assetbundles\smoke;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * Smoke Asset Bundle
 *
 * Registers CSS and JS assets for the Smoke editor
 */
class SmokeAsset extends AssetBundle
{
    public function init(): void
    {
        $this->sourcePath = '@yourhandle/smoke/assetbundles/smoke/dist';

        $this->css = [
            'smoke.css',
        ];

        $this->js = [
            'smoke.js',
        ];

        parent::init();
    }
}
