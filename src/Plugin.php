<?php

namespace justinholtweb\smoke;

use Craft;
use craft\base\Plugin as BasePlugin;
use craft\web\twig\variables\CraftVariable;
use justinholtweb\smoke\services\SmokeService;
use justinholtweb\smoke\variables\SmokeVariable;
use yii\base\Event;

/**
 * Smoke plugin
 *
 * @method static Plugin getInstance()
 * @property-read SmokeService $smoke
 */
class Plugin extends BasePlugin
{
    public string $schemaVersion = '0.1.0';
    public bool $hasCpSettings = false;

    public static function config(): array
    {
        return [
            'components' => [
                'smoke' => SmokeService::class,
            ],
        ];
    }

    public function init(): void
    {
        parent::init();

        // Register Twig variable
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function(Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('smoke', SmokeVariable::class);
            }
        );

        // Register site request listeners
        if (!Craft::$app->getRequest()->getIsCpRequest()) {
            $this->_registerSiteListeners();
        }

        Craft::info(
            'Smoke plugin loaded',
            __METHOD__
        );
    }

    private function _registerSiteListeners(): void
    {
        // Additional site-specific event listeners can be added here
    }
}
