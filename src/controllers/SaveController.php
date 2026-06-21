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
        $value = Plugin::getInstance()->smoke->prepareValueForSave($field, $value);
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

        // The panel posts DataStar signals as a JSON body: {smokeElementId, fields: {...}, ...}
        $elementId = Craft::$app->getRequest()->getBodyParam('smokeElementId');
        $fields = Craft::$app->getRequest()->getBodyParam('fields', []);

        $element = Entry::find()->id($elementId)->one();

        if (!$element || !Plugin::getInstance()->smoke->canEdit($element)) {
            return DatastarHelper::response([
                'signals' => [
                    'smokeError' => 'Permission denied',
                ],
            ]);
        }

        // Set all field values
        $smoke = Plugin::getInstance()->smoke;
        foreach ($fields as $fieldHandle => $value) {
            $field = $element->getFieldLayout()?->getFieldByHandle($fieldHandle);
            if ($field) {
                $value = $smoke->prepareValueForSave($field, $value);
            }
            $element->setFieldValue($fieldHandle, $value);
        }

        // Validate and save
        if (!Craft::$app->getElements()->saveElement($element, false)) {
            $errors = $element->getErrors();
            $errorMessage = 'Failed to save: ' . implode(', ', array_values($errors)[0] ?? ['Unknown error']);

            return DatastarHelper::response([
                'signals' => [
                    'smokeError' => $errorMessage,
                    'smokeSaving' => false,
                ],
            ]);
        }

        return DatastarHelper::response([
            'signals' => [
                'smokeSuccess' => 'All changes saved successfully',
                'smokeSaving' => false,
                'smokeEditorOpen' => false,
                // Updated values for any on-page display elements, applied client-side by
                // smoke.js so the page refreshes live without a reload.
                'smokeSavedFields' => $this->savedFieldValues($element, array_keys($fields)),
            ],
        ]);
    }

    /**
     * Build a map of saved field values for refreshing on-page display elements
     * (the `[data-smoke-editable]` elements rendered by craft.smoke.editable()).
     *
     * @return array<string, array{value: string, html: bool}> Keyed by field handle.
     *     `html` = true means the value is HTML (set via innerHTML), false means plain text.
     */
    private function savedFieldValues($element, array $fieldHandles): array
    {
        $smoke = Plugin::getInstance()->smoke;
        $saved = [];

        foreach ($fieldHandles as $handle) {
            $field = $element->getFieldLayout()?->getFieldByHandle($handle);
            if (!$field) {
                continue;
            }

            $type = $smoke->getFieldEditorType(get_class($field));
            $value = $element->getFieldValue($handle);

            // Only refresh field types whose display is a simple value. Rich text is HTML;
            // everything else is plain text. Complex/relational types are skipped.
            $entry = match ($type) {
                'plaintext' => ['value' => (string)$value, 'html' => false],
                // Dropdown displays its option label, not the stored value.
                'dropdown' => ['value' => (string)($value->label ?? $value), 'html' => false],
                'richtext' => ['value' => (string)$value, 'html' => true],
                default => null,
            };

            if ($entry !== null) {
                $saved[$handle] = $entry;
            }
        }

        return $saved;
    }
}
