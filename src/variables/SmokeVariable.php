<?php

namespace justinholtweb\smoke\variables;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\Html;
use craft\web\View;
use justinholtweb\smoke\Plugin;
use justinholtweb\smoke\web\assets\SmokeAsset;

/**
 * Smoke Variable
 *
 * Provides Twig template functions for the Smoke plugin
 */
class SmokeVariable
{
    /**
     * Initialize the Smoke editor on the page
     */
    public function init(): void
    {
        $view = Craft::$app->getView();

        // Only initialize on site requests
        if (Craft::$app->getRequest()->getIsCpRequest()) {
            return;
        }

        // Only initialize for logged-in users
        if (!Craft::$app->getUser()->getIdentity()) {
            return;
        }

        // Register the asset bundle
        $view->registerAssetBundle(SmokeAsset::class);

        // Add DataStar from CDN
        $view->registerJsFile(
            'https://cdn.jsdelivr.net/npm/@sudodevnull/datastar@1.0.0-beta.6/dist/datastar.min.js',
            ['position' => View::POS_HEAD]
        );

        // Initialize Smoke editor state
        $view->registerJs(
            "window.smokeEditor = { active: false, currentField: null };",
            View::POS_HEAD
        );

        // Inject the editor container HTML at the end of the body
        $oldTemplateMode = $view->getTemplateMode();
        $view->setTemplateMode(View::TEMPLATE_MODE_CP);
        $containerHtml = $view->renderTemplate('smoke/_components/editor-container');
        $view->setTemplateMode($oldTemplateMode);
        $view->registerHtml($containerHtml, View::POS_END);
    }

    /**
     * Check if user can edit an element
     */
    public function canEdit(?ElementInterface $element): bool
    {
        if (!$element) {
            return false;
        }

        return Plugin::getInstance()->smoke->canEdit($element);
    }

    /**
     * Get editable attributes for a field
     */
    public function editable(ElementInterface $element, string $fieldHandle): string
    {
        $attributes = Plugin::getInstance()->smoke->getEditableAttributes($element, $fieldHandle);

        if (empty($attributes)) {
            return '';
        }

        $htmlAttributes = [];
        foreach ($attributes as $key => $value) {
            $htmlAttributes[] = Html::encode($key) . '="' . Html::encode($value) . '"';
        }

        return implode(' ', $htmlAttributes);
    }

    /**
     * Get all editable fields for an element
     */
    public function getEditableFields(ElementInterface $element): array
    {
        return Plugin::getInstance()->smoke->getEditableFields($element);
    }

    /**
     * Generate the edit button for an element
     */
    public function editButton(ElementInterface $element, array $options = []): string
    {
        if (!$this->canEdit($element)) {
            return '';
        }

        $label = $options['label'] ?? 'Edit';
        $class = $options['class'] ?? 'smoke-edit-btn';

        return Html::button($label, [
            'class' => $class,
            'data-on-click' => "\$get('/actions/smoke/edit/open?elementId={$element->id}')",
            'data-indicator' => true,
        ]);
    }
}
