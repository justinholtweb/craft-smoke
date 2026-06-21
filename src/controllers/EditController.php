<?php

namespace justinholtweb\smoke\controllers;

use Craft;
use craft\elements\Entry;
use craft\web\Controller;
use craft\web\View;
use yii\web\Response;
use justinholtweb\smoke\helpers\DatastarHelper;
use justinholtweb\smoke\Plugin;

/**
 * Edit Controller
 *
 * Handles requests to open and display field editors
 */
class EditController extends Controller
{
    protected array|int|bool $allowAnonymous = false;

    /**
     * Open the edit panel for an element
     */
    public function actionOpen(): Response
    {
        $this->requireLogin();

        $elementId = Craft::$app->getRequest()->getParam('elementId');
        $element = Entry::find()->id($elementId)->one();

        if (!$element || !Plugin::getInstance()->smoke->canEdit($element)) {
            return $this->asJson([
                'error' => 'You do not have permission to edit this element',
            ]);
        }

        $smoke = Plugin::getInstance()->smoke;
        $fields = $smoke->getEditableFields($element);

        // Render each field's editor via its adapter (so module-provided field types work).
        foreach ($fields as &$info) {
            $field = $element->getFieldLayout()?->getFieldByHandle($info['handle']);
            $info['html'] = $field
                ? $smoke->renderFieldEditor($element, $field, $element->getFieldValue($info['handle']))
                : '';
        }
        unset($info);

        // Render the edit panel
        $html = Craft::$app->getView()->renderTemplate('smoke/_components/edit-panel', [
            'element' => $element,
            'fields' => $fields,
        ], View::TEMPLATE_MODE_CP);

        // Return DataStar SSE response
        return DatastarHelper::response([
            'elements' => [
                'selector' => '#smoke-editor',
                'mode' => 'inner',
                'html' => $html,
            ],
            'signals' => [
                'smokeEditorOpen' => true,
                'smokeElementId' => $element->id,
            ],
        ]);
    }

    /**
     * Load a specific field editor
     */
    public function actionField(): Response
    {
        $this->requireLogin();

        $elementId = Craft::$app->getRequest()->getParam('elementId');
        $fieldHandle = Craft::$app->getRequest()->getParam('fieldHandle');

        $element = Entry::find()->id($elementId)->one();

        if (!$element || !Plugin::getInstance()->smoke->canEdit($element)) {
            return $this->asJson(['error' => 'Permission denied']);
        }

        $field = $element->getFieldLayout()?->getFieldByHandle($fieldHandle);

        if (!$field) {
            return $this->asJson(['error' => 'Field not found']);
        }

        // Render the field editor via its adapter
        $value = $element->getFieldValue($fieldHandle);
        $html = Plugin::getInstance()->smoke->renderFieldEditor($element, $field, $value);

        return DatastarHelper::response([
            'elements' => [
                'selector' => '#smoke-field-editor',
                'mode' => 'inner',
                'html' => $html,
            ],
        ]);
    }

    /**
     * Close the edit panel
     */
    public function actionClose(): Response
    {
        return DatastarHelper::response([
            'signals' => [
                'smokeEditorOpen' => false,
                'smokeElementId' => null,
                'smokeCurrentField' => null,
            ],
        ]);
    }
}
