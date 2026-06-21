<?php

namespace justinholtweb\smoke\base;

use Craft;
use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\web\View;

/**
 * Base Field Adapter
 *
 * Sensible defaults for {@see FieldAdapterInterface}. Extend this and override only
 * what you need. At minimum, override {@see handles()} and {@see type()}.
 *
 * By default the editor is rendered from `smoke/_field-editors/{type}.twig` in the CP
 * template mode, receiving `element`, `field`, `fieldHandle`, and `value`. To render
 * your own template, override {@see renderEditor()} (or {@see editorTemplate()} and
 * {@see editorTemplateMode()} to point at a template in your module's template root).
 */
abstract class BaseFieldAdapter implements FieldAdapterInterface
{
    public function supportsInline(): bool
    {
        return false;
    }

    public function inlineConfig(ElementInterface $element, FieldInterface $field, mixed $value): ?array
    {
        return null;
    }

    public function prepareValue(FieldInterface $field, mixed $value): mixed
    {
        return $value;
    }

    public function displayValue(FieldInterface $field, mixed $value): ?array
    {
        return null;
    }

    public function renderEditor(ElementInterface $element, FieldInterface $field, mixed $value): string
    {
        $view = Craft::$app->getView();
        $oldMode = $view->getTemplateMode();
        $view->setTemplateMode($this->editorTemplateMode());

        $html = $view->renderTemplate($this->editorTemplate(), [
            'element' => $element,
            'field' => $field,
            'fieldHandle' => $field->handle,
            'value' => $value,
        ]);

        $view->setTemplateMode($oldMode);

        return $html;
    }

    /**
     * The template used by the default {@see renderEditor()}.
     */
    protected function editorTemplate(): string
    {
        return "smoke/_field-editors/{$this->type()}";
    }

    /**
     * The template mode for the default editor template. Override to TEMPLATE_MODE_SITE
     * if your module exposes its editor template under the site templates root.
     */
    protected function editorTemplateMode(): string
    {
        return View::TEMPLATE_MODE_CP;
    }
}
