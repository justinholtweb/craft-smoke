<?php

namespace justinholtweb\smoke\controllers;

use Craft;
use craft\elements\Entry;
use craft\web\Controller;
use justinholtweb\smoke\helpers\DatastarHelper;
use justinholtweb\smoke\Plugin;
use yii\web\Response;

/**
 * Save Controller
 *
 * Handles saving field data from frontend edits. Persistence + lifecycle events live in
 * SmokeService (saveField / saveAllFields).
 */
class SaveController extends Controller
{
    protected array|int|bool $allowAnonymous = false;

    /**
     * Save a single field value (inline edit).
     */
    public function actionField(): Response
    {
        $this->requireLogin();
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $elementId = $request->getBodyParam('elementId');
        $fieldHandle = $request->getBodyParam('fieldHandle');
        // The value comes in either as the field-handle param or a generic 'value' param.
        $value = $request->getBodyParam($fieldHandle) ?? $request->getBodyParam('value');

        $smoke = Plugin::getInstance()->smoke;
        $element = Entry::find()->id($elementId)->one();

        if (!$element || !$smoke->canEdit($element)) {
            return $this->asJson([
                'success' => false,
                'error' => 'You do not have permission to edit this element',
            ]);
        }

        $field = $element->getFieldLayout()?->getFieldByHandle($fieldHandle);

        if (!$field) {
            return $this->asJson(['success' => false, 'error' => 'Field not found']);
        }

        if (!$smoke->saveField($element, $field, $value)) {
            return $this->asJson([
                'success' => false,
                'error' => $this->saveErrorMessage($element),
            ]);
        }

        return $this->asJson([
            'success' => true,
            'value' => $element->getFieldValue($fieldHandle),
            'message' => 'Saved successfully',
        ]);
    }

    /**
     * Save all modified fields for an element (panel "save all").
     */
    public function actionAll(): Response
    {
        $this->requireLogin();
        $this->requirePostRequest();

        // The panel posts DataStar signals as a JSON body: {smokeElementId, fields: {...}, ...}
        $request = Craft::$app->getRequest();
        $elementId = $request->getBodyParam('smokeElementId');
        $fields = $request->getBodyParam('fields', []);

        $smoke = Plugin::getInstance()->smoke;
        $element = Entry::find()->id($elementId)->one();

        if (!$element || !$smoke->canEdit($element)) {
            return DatastarHelper::response([
                'signals' => ['smokeError' => 'Permission denied', 'smokeSaving' => false],
            ]);
        }

        if (!$smoke->saveAllFields($element, $fields)) {
            return DatastarHelper::response([
                'signals' => [
                    'smokeError' => $this->saveErrorMessage($element),
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
     * Build a friendly error message from an element's validation errors.
     */
    private function saveErrorMessage($element): string
    {
        $errors = $element->getErrors();
        if (!$errors) {
            return 'The save was cancelled.';
        }

        return 'Failed to save: ' . implode(', ', array_values($errors)[0] ?? ['Unknown error']);
    }

    /**
     * Build a map of saved field values (via each field's adapter) for refreshing on-page
     * display elements after a panel save.
     *
     * @return array<string, array{value: string, html: bool}> Keyed by field handle.
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

            $display = $smoke->displayValue($field, $element->getFieldValue($handle));
            if ($display !== null) {
                $saved[$handle] = $display;
            }
        }

        return $saved;
    }
}
