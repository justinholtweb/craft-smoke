<?php

namespace justinholtweb\smoke\base;

use craft\base\ElementInterface;
use craft\base\FieldInterface;

/**
 * Field Adapter Interface
 *
 * A field adapter teaches Smoke how to edit one (or a family of) Craft field type(s).
 * Built-in field types are implemented as adapters, and modules/plugins can register
 * their own via SmokeService::EVENT_REGISTER_FIELD_ADAPTERS to support any field type —
 * including those exposed by other plugins.
 *
 * Most adapters should extend {@see BaseFieldAdapter}, which provides sensible defaults,
 * and override only what they need.
 */
interface FieldAdapterInterface
{
    /**
     * Whether this adapter handles the given field. The first registered adapter that
     * returns true wins, so more specific adapters should be registered first.
     */
    public function handles(FieldInterface $field): bool;

    /**
     * A short editor-type identifier (e.g. 'plaintext', 'richtext', 'color'). Surfaced to
     * the frontend as `data-smoke-type` and used to locate the default editor template.
     */
    public function type(): string;

    /**
     * Whether the field can be edited inline (click-to-edit in place) as well as in the panel.
     */
    public function supportsInline(): bool;

    /**
     * Config the inline editor (smoke.js) needs, serialized to the `data-smoke-config`
     * attribute. Return null if the field has no inline editor.
     */
    public function inlineConfig(ElementInterface $element, FieldInterface $field, mixed $value): ?array;

    /**
     * Transform a value posted from the frontend into the shape the field's
     * setFieldValue() expects. Return the value unchanged if no transform is needed.
     */
    public function prepareValue(FieldInterface $field, mixed $value): mixed;

    /**
     * Render the panel editor HTML for this field.
     */
    public function renderEditor(ElementInterface $element, FieldInterface $field, mixed $value): string;

    /**
     * The value used to refresh the field's on-page display after a panel save, as
     * ['value' => string, 'html' => bool], or null if the display can't be refreshed live.
     */
    public function displayValue(FieldInterface $field, mixed $value): ?array;
}
