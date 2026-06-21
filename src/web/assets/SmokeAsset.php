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
            // DataStar 1.0 is an ES module; register it with type="module".
            // Vendored locally (see dist/datastar.js) to avoid a CDN runtime dependency.
            ['datastar.js', 'type' => 'module'],
            'smoke.js',
        ];

        parent::init();
    }
}
