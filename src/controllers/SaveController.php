<?php

namespace justinholtweb\smoke\controllers;

use Craft;
use craft\elements\Entry;
use craft\web\Controller;
use yii\web\Response;
use justinholtweb\smoke\helpers\DatastarHelper;
use justinholtweb\smoke\Plugin;

/**
 * Save Controller
 *
 * Handles saving field data from frontend edits
 */
class SaveController extends Controller
{
    protected array|int|bool $allowAnonymous = false;

    /**
     * Save a single field value
     */
    public function actionField(): Response
    {
        $this->requireLogin();
        $this->requirePostRequest();

        $elementId = Craft::$app->getRequest()->getBodyParam('elementId');
        $fieldHandle = Craft::$app->getRequest()->getBodyParam('fieldHandle');
        // Get value from either 'value' param or the field handle param
        $value = Craft::$app->getRequest()->getBodyParam($fieldHandle) ?? Craft::$app->getRequest()->getBodyParam('value');

        $element = Entry::find()->id($elementId)->one();

        if (!$element || !Plugin::getInstance()->smoke->canEdit($element)) {
            return $this->asJson([
                'success' => false,
                'error' => 'You do not have permission to edit this element',
            ]);
        }

        $field = $element->getFieldLayout()?->getFieldByHandle($fieldHandle);

        if (!$field) {
            return $this->asJson([
                'success' => false,
                'error' => 'Field not found',
            ]);
        }

        // Set the field value
        $element->setFieldValue($fieldHandle, $value);

        // Validate and save
        if (!Craft::$app->getElements()->saveElement($element, false)) {
            $errors = $element->getErrors();
            $errorMessage = 'Failed to save: ' . implode(', ', array_values($errors)[0] ?? ['Unknown error']);

            return $this->asJson([
                'success' => false,
                'error' => $errorMessage,
            ]);
        }

        // Get the updated value
        $updatedValue = $element->getFieldValue($fieldHandle);

        return $this->asJson([
            'success' => true,
            'value' => $updatedValue,
            'message' => 'Saved successfully',
        ]);
    }

    /**
     * Save all modified fields for an element
     */
    public function actionAll(): Response
    {
        $this->requireLogin();
        $this->requirePostRequest();

        $elementId = Craft::$app->getRequest()->getBodyParam('elementId');
        $fields = Craft::$app->getRequest()->getBodyParam('fields', []);

        $element = Entry::find()->id($elementId)->one();

        if (!$element || !Plugin::getInstance()->smoke->canEdit($element)) {
            return DatastarHelper::response([
                'signal' => [
                    'smokeError' => 'Permission denied',
                ],
            ]);
        }

        // Set all field values
        foreach ($fields as $fieldHandle => $value) {
            $element->setFieldValue($fieldHandle, $value);
        }

        // Validate and save
        if (!Craft::$app->getElements()->saveElement($element, false)) {
            $errors = $element->getErrors();
            $errorMessage = 'Failed to save: ' . implode(', ', array_values($errors)[0] ?? ['Unknown error']);

            return DatastarHelper::response([
                'signal' => [
                    'smokeError' => $errorMessage,
                    'smokeSaving' => false,
                ],
            ]);
        }

        return DatastarHelper::response([
            'signal' => [
                'smokeSuccess' => 'All changes saved successfully',
                'smokeSaving' => false,
                'smokeEditorOpen' => false,
            ],
        ]);
    }
}
