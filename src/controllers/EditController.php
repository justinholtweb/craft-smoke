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

        $fields = Plugin::getInstance()->smoke->getEditableFields($element);

        // Render the edit panel
        $html = Craft::$app->getView()->renderTemplate('smoke/_components/edit-panel', [
            'element' => $element,
            'fields' => $fields,
        ], View::TEMPLATE_MODE_CP);

        // Return DataStar SSE response
        return DatastarHelper::response([
            'fragment' => [
                'selector' => '#smoke-editor',
                'html' => $html,
                'merge' => 'morph',
            ],
            'signal' => [
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

        $fieldType = Plugin::getInstance()->smoke->getFieldEditorType(get_class($field));
        $value = $element->getFieldValue($fieldHandle);

        // Render the field editor
        $html = Craft::$app->getView()->renderTemplate("smoke/_field-editors/{$fieldType}", [
            'element' => $element,
            'field' => $field,
            'fieldHandle' => $fieldHandle,
            'value' => $value,
        ], View::TEMPLATE_MODE_CP);

        return DatastarHelper::response([
            'fragment' => [
                'selector' => '#smoke-field-editor',
                'html' => $html,
                'merge' => 'morph',
            ],
        ]);
    }

    /**
     * Close the edit panel
     */
    public function actionClose(): Response
    {
        return DatastarHelper::response([
            'signal' => [
                'smokeEditorOpen' => false,
                'smokeElementId' => null,
                'smokeCurrentField' => null,
            ],
        ]);
    }
}
