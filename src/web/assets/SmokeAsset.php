<?php

namespace justinholtweb\smoke\web\assets;

use craft\web\AssetBundle;

/**
 * Smoke Asset Bundle
 *
 * Registers CSS and JS assets for the Smoke editor
 */
class SmokeAsset extends AssetBundle
{
    public function init(): void
    {
        $this->sourcePath = '@justinholtweb/smoke/web/assets/dist';

        $this->css = [
            'smoke.css',
        ];

        $this->js = [
            'smoke.js',
        ];

        parent::init();
    }
}
