<?php

namespace justinholtweb\smoke;

use Craft;
use craft\base\Model;
use craft\base\Plugin as BasePlugin;
use craft\events\RegisterUserPermissionsEvent;
use craft\services\UserPermissions;
use craft\web\twig\variables\CraftVariable;
use justinholtweb\smoke\models\Settings;
use justinholtweb\smoke\services\SmokeService;
use justinholtweb\smoke\variables\SmokeVariable;
use yii\base\Event;

/**
 * Smoke plugin
 *
 * @method static Plugin getInstance()
 * @method Settings getSettings()
 * @property-read SmokeService $smoke
 */
class Plugin extends BasePlugin
{
    public string $schemaVersion = '0.1.0';
    public bool $hasCpSettings = true;

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

        $this->_registerVariables();
        $this->_registerPermissions();

        if (Craft::$app->getRequest()->getIsSiteRequest()) {
            $this->_registerSiteListeners();
        }

        Craft::info('Smoke plugin loaded', __METHOD__);
    }

    protected function createSettingsModel(): ?Model
    {
        return new Settings();
    }

    protected function settingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('smoke/settings/_index', [
            'settings' => $this->getSettings(),
        ]);
    }

    private function _registerVariables(): void
    {
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function(Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('smoke', SmokeVariable::class);
            }
        );
    }

    private function _registerPermissions(): void
    {
        Event::on(
            UserPermissions::class,
            UserPermissions::EVENT_REGISTER_PERMISSIONS,
            function(RegisterUserPermissionsEvent $event) {
                $event->permissions[] = [
                    'heading' => 'Smoke',
                    'permissions' => [
                        SmokeService::PERMISSION_EDIT => [
                            'label' => Craft::t('smoke', 'Edit content on the frontend'),
                        ],
                    ],
                ];
            }
        );
    }

    private function _registerSiteListeners(): void
    {
        // TODO: Additional site-specific event listeners
    }
}
