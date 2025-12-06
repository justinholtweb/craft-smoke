<?php

namespace justinholtweb\smoke\controllers;

use Craft;
use craft\elements\Entry;
use craft\web\Controller;
use craft\web\View;
use yii\web\Response;
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
            return $this->_asDatastar([
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

            return $this->_asDatastar([
                'signal' => [
                    'smokeError' => $errorMessage,
                    'smokeSaving' => false,
                ],
            ]);
        }

        return $this->_asDatastar([
            'signal' => [
                'smokeSuccess' => 'All changes saved successfully',
                'smokeSaving' => false,
                'smokeEditorOpen' => false,
            ],
        ]);
    }

    /**
     * Format response as DataStar SSE events
     */
    private function _asDatastar(array $events): Response
    {
        $response = Craft::$app->getResponse();
        $response->format = Response::FORMAT_RAW;
        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('X-Accel-Buffering', 'no');

        $data = '';

        // Fragment events (DOM patches)
        if (isset($events['fragment'])) {
            $data .= "event: datastar-fragment\n";
            $data .= 'data: ' . json_encode($events['fragment']) . "\n\n";
        }

        // Signal events (state updates)
        if (isset($events['signal'])) {
            $data .= "event: datastar-signal\n";
            $data .= 'data: ' . json_encode($events['signal']) . "\n\n";
        }

        $response->data = $data;

        return $response;
    }
}
