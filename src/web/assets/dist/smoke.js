/**
 * Smoke Editor - Frontend JavaScript
 */

(function() {
    'use strict';

    /**
     * Dispatch a bubbling `smoke:*` CustomEvent on document so modules can hook in.
     * Events: smoke:panel-open, smoke:panel-close, smoke:edit-start, smoke:before-save,
     * smoke:after-save, smoke:error, smoke:saved.
     */
    function dispatchSmoke(name, detail) {
        document.dispatchEvent(new CustomEvent('smoke:' + name, { bubbles: true, detail: detail || {} }));
    }

    // Initialize Smoke when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSmoke);
    } else {
        initSmoke();
    }

    function initSmoke() {
        // Inject the floating "Edit Page" button (wired to DataStar)
        setupEditButton();

        // Setup inline editing for editable fields
        setupInlineEditing();

        // Wire rich-text + table editors already on the page, and any the panel morphs
        // in later (the panel content is patched in after load by DataStar).
        const wirePanelEditors = function(root) {
            wireRichEditors(root);
            wireTableEditors(root);
        };
        wirePanelEditors(document);
        const editor = document.getElementById('smoke-editor');
        if (editor && window.MutationObserver) {
            new MutationObserver(function() { wirePanelEditors(editor); })
                .observe(editor, { childList: true, subtree: true });

            // Emit panel open/close events when $smokeEditorOpen toggles data-smoke-open.
            new MutationObserver(function() {
                dispatchSmoke(editor.getAttribute('data-smoke-open') === 'true' ? 'panel-open' : 'panel-close', { editor: editor });
            }).observe(editor, { attributes: true, attributeFilter: ['data-smoke-open'] });
        }

        // Add keyboard shortcuts
        setupKeyboardShortcuts();
    }

    // Rich-text toolbar definition — shared by the panel editor and inline editing.
    const RICH_BUTTONS = [
        { cmd: 'bold', html: '<strong>B</strong>', title: 'Bold' },
        { cmd: 'italic', html: '<em>I</em>', title: 'Italic' },
        { cmd: 'formatBlock', arg: '<h2>', html: 'H2', title: 'Heading' },
        { cmd: 'formatBlock', arg: '<p>', html: '¶', title: 'Paragraph' },
        { cmd: 'insertUnorderedList', html: '•&nbsp;List', title: 'Bulleted list' },
        { cmd: 'insertOrderedList', html: '1.&nbsp;List', title: 'Numbered list' },
        { cmd: 'createLink', html: 'Link', title: 'Insert link (select text first)' }
    ];

    /**
     * Build a rich-text toolbar bound to a contenteditable element. `onChange` is called
     * after any command so callers can sync the result. The Link button reveals an inline
     * URL input (no blocking dialog).
     */
    function buildRichToolbar(editable, onChange) {
        const bar = document.createElement('div');
        bar.className = 'smoke-richtext-toolbar';

        // Inline link input, hidden until "Link" is pressed.
        const linkBox = document.createElement('span');
        linkBox.className = 'smoke-richtext-linkbox';
        linkBox.hidden = true;
        const linkInput = document.createElement('input');
        linkInput.type = 'url';
        linkInput.placeholder = 'https://…';
        linkInput.className = 'smoke-richtext-linkinput';
        const linkApply = document.createElement('button');
        linkApply.type = 'button';
        linkApply.textContent = 'Add';
        linkBox.appendChild(linkInput);
        linkBox.appendChild(linkApply);

        let savedRange = null;

        RICH_BUTTONS.forEach(function(b) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.innerHTML = b.html;
            btn.title = b.title;
            btn.addEventListener('mousedown', function(e) { e.preventDefault(); }); // keep selection
            btn.addEventListener('click', function() {
                if (b.cmd === 'createLink') {
                    const sel = window.getSelection();
                    savedRange = sel.rangeCount ? sel.getRangeAt(0).cloneRange() : null;
                    linkBox.hidden = false;
                    linkInput.value = '';
                    linkInput.focus();
                    return;
                }
                document.execCommand(b.cmd, false, b.arg || null);
                editable.focus();
                onChange();
            });
            bar.appendChild(btn);
        });
        bar.appendChild(linkBox);

        const applyLink = function() {
            const url = linkInput.value.trim();
            linkBox.hidden = true;
            if (!url) {
                return;
            }
            editable.focus();
            if (savedRange) {
                const sel = window.getSelection();
                sel.removeAllRanges();
                sel.addRange(savedRange);
            }
            document.execCommand('createLink', false, url);
            onChange();
        };
        linkApply.addEventListener('mousedown', function(e) { e.preventDefault(); });
        linkApply.addEventListener('click', applyLink);
        linkInput.addEventListener('keydown', function(e) {
            e.stopPropagation(); // don't let Enter/Escape reach the editor's handlers
            if (e.key === 'Enter') { e.preventDefault(); applyLink(); }
            else if (e.key === 'Escape') { e.preventDefault(); linkBox.hidden = true; }
        });

        return bar;
    }

    /**
     * Wire panel rich-text editors: inject the toolbar and sync the editor's HTML into the
     * hidden, DataStar-bound textarea so the panel save picks it up.
     */
    function wireRichEditors(root) {
        root.querySelectorAll('[data-smoke-richtext]:not([data-smoke-wired])').forEach(function(wrap) {
            wrap.setAttribute('data-smoke-wired', '1');

            const editable = wrap.querySelector('.smoke-richtext-editable');
            const input = wrap.querySelector('.smoke-richtext-input');
            if (!editable) {
                return;
            }

            const sync = function() {
                if (input) {
                    input.value = editable.innerHTML;
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                }
            };

            wrap.insertBefore(buildRichToolbar(editable, sync), editable);
            editable.addEventListener('input', sync);
        });
    }

    /**
     * Reveal the floating "Edit Page" button when the page has editable fields.
     *
     * The button is rendered server-side (and bound by DataStar at its initial scan, so
     * there's no first-click race); here we only un-hide it when there's something to edit,
     * or remove it otherwise.
     */
    function setupEditButton() {
        const button = document.querySelector('#smoke-edit-button-container .smoke-edit-btn');
        if (!button) {
            return;
        }
        if (document.querySelector('[data-smoke-editable]')) {
            button.hidden = false;
        } else {
            button.remove();
        }
    }

    /**
     * Setup inline editing for editable fields
     */
    function setupInlineEditing() {
        // Field types that support editing in place. Display-only / relational types
        // (assets, entries, matrix, …) are intentionally excluded.
        const INLINE_TYPES = ['plaintext', 'dropdown', 'lightswitch', 'richtext'];
        const selector = INLINE_TYPES.map(t => '[data-smoke-editable][data-smoke-type="' + t + '"]').join(',');

        document.querySelectorAll(selector).forEach(field => {
            field.classList.add('smoke-inline-editable');

            field.addEventListener('click', function(e) {
                if (this.classList.contains('smoke-editing')) {
                    return;
                }
                e.stopPropagation();

                const type = this.getAttribute('data-smoke-type');
                const elementId = this.getAttribute('data-smoke-element-id');
                const fieldHandle = this.getAttribute('data-smoke-field');
                let config = {};
                try { config = JSON.parse(this.getAttribute('data-smoke-config') || '{}'); } catch (err) {}

                // Lightswitch toggles immediately; the others open an in-place editor.
                if (type === 'lightswitch') {
                    toggleLightswitch(this, elementId, fieldHandle, config);
                } else {
                    enterInlineEditMode(this, type, elementId, fieldHandle, config);
                }
            });

            field.addEventListener('mouseenter', function() {
                this.setAttribute('title', 'Click to edit ' + this.getAttribute('data-smoke-field'));
            });
        });
    }

    /**
     * Build the inline control for a field type.
     * @returns {{control: HTMLElement, getValue: function, getDisplay: function}}
     */
    function buildInlineControl(type, element, config) {
        if (type === 'richtext') {
            // Seed from the RAW content (config.raw) so existing ref tags survive the save.
            const wrap = document.createElement('div');
            wrap.className = 'smoke-richtext';
            const editable = document.createElement('div');
            editable.className = 'smoke-richtext-editable';
            editable.setAttribute('contenteditable', 'true');
            editable.innerHTML = (config.raw !== undefined && config.raw !== null) ? config.raw : element.innerHTML;
            wrap.appendChild(buildRichToolbar(editable, function() {}));
            wrap.appendChild(editable);
            return {
                control: wrap,
                focusTarget: editable,
                isHtml: true,
                getValue: () => editable.innerHTML,
                getDisplay: () => editable.innerHTML
            };
        }

        if (type === 'dropdown') {
            const select = document.createElement('select');
            select.className = 'smoke-inline-input';
            (config.options || []).forEach(opt => {
                const o = document.createElement('option');
                o.value = opt.value;
                o.textContent = opt.label;
                if (opt.value === config.value) o.selected = true;
                select.appendChild(o);
            });
            return {
                control: select,
                getValue: () => select.value,
                getDisplay: () => {
                    const opt = (config.options || []).find(o => o.value === select.value);
                    return opt ? opt.label : select.value;
                }
            };
        }

        // plaintext — single- or multi-line
        const input = document.createElement(config.multiline ? 'textarea' : 'input');
        if (!config.multiline) input.type = 'text';
        input.className = 'smoke-inline-input';
        input.value = element.textContent.trim();
        return {
            control: input,
            getValue: () => input.value,
            getDisplay: () => input.value
        };
    }

    /**
     * Enter inline edit mode for a field (plaintext / dropdown).
     */
    function enterInlineEditMode(element, type, elementId, fieldHandle, config) {
        element.classList.add('smoke-editing');
        dispatchSmoke('edit-start', { element: element, type: type, elementId: elementId, fieldHandle: fieldHandle });
        const originalContent = element.innerHTML;

        const built = buildInlineControl(type, element, config);

        const actions = document.createElement('div');
        actions.className = 'smoke-inline-actions';

        const saveBtn = document.createElement('button');
        saveBtn.className = 'smoke-inline-btn smoke-inline-save';
        saveBtn.textContent = 'Save';

        const cancelBtn = document.createElement('button');
        cancelBtn.className = 'smoke-inline-btn smoke-inline-cancel';
        cancelBtn.textContent = 'Cancel';

        actions.appendChild(saveBtn);
        actions.appendChild(cancelBtn);

        element.innerHTML = '';
        element.appendChild(built.control);
        element.appendChild(actions);

        const focusTarget = built.focusTarget || built.control;
        focusTarget.focus();
        if (focusTarget.select) focusTarget.select();

        const doSave = () => saveInlineEdit(element, elementId, fieldHandle, built.getValue(), built.getDisplay(), originalContent, built.isHtml);

        saveBtn.addEventListener('click', doSave);
        cancelBtn.addEventListener('click', () => cancelInlineEdit(element, originalContent));

        built.control.addEventListener('keydown', (e) => {
            // Enter saves for single-line inputs/selects only. In a textarea or rich-text
            // editor, Enter inserts a newline, so don't intercept it.
            const multilineEditor = built.control.tagName === 'TEXTAREA' || built.isHtml;
            if (e.key === 'Enter' && !multilineEditor) {
                e.preventDefault();
                doSave();
            } else if (e.key === 'Escape') {
                e.preventDefault();
                cancelInlineEdit(element, originalContent);
            }
        });

        const closeOnClickOutside = (e) => {
            if (!element.contains(e.target)) {
                cancelInlineEdit(element, originalContent);
                document.removeEventListener('click', closeOnClickOutside);
            }
        };
        setTimeout(() => document.addEventListener('click', closeOnClickOutside), 0);
    }

    /**
     * Toggle a lightswitch field in place (immediate save, no edit mode).
     */
    function toggleLightswitch(element, elementId, fieldHandle, config) {
        const newValue = !config.value;
        postFieldValue(elementId, fieldHandle, newValue ? '1' : '0')
            .then(() => {
                config.value = newValue;
                element.setAttribute('data-smoke-config', JSON.stringify(config));
                element.textContent = newValue ? (config.onLabel || 'On') : (config.offLabel || 'Off');
                showNotification('Saved successfully', 'success');
            })
            .catch(err => showNotification(err.message, 'error'));
    }

    /**
     * Save an inline edit (plaintext / dropdown), then swap in the display value.
     */
    function saveInlineEdit(element, elementId, fieldHandle, newValue, displayValue, originalContent, isHtml) {
        const saveBtn = element.querySelector('.smoke-inline-save');
        if (saveBtn) {
            saveBtn.classList.add('smoke-inline-saving');
            saveBtn.textContent = 'Saving...';
        }

        postFieldValue(elementId, fieldHandle, newValue)
            .then(() => {
                element.classList.remove('smoke-editing');
                if (isHtml) {
                    element.innerHTML = displayValue;
                } else {
                    element.textContent = displayValue;
                }
                showNotification('Saved successfully', 'success');
            })
            .catch(error => {
                element.classList.remove('smoke-editing');
                element.innerHTML = originalContent;
                showNotification(error.message, 'error');
            });
    }

    /**
     * POST a single field value to Smoke's save endpoint. Returns a promise that
     * resolves with the JSON payload or rejects with an Error.
     */
    function postFieldValue(elementId, fieldHandle, value) {
        const formData = new FormData();
        formData.append('elementId', elementId);
        formData.append('fieldHandle', fieldHandle);
        formData.append(fieldHandle, value);
        // CSRF token (exposed by smoke.init() via window.smokeCsrf)
        if (window.smokeCsrf && window.smokeCsrf.name) {
            formData.append(window.smokeCsrf.name, window.smokeCsrf.value);
        }

        dispatchSmoke('before-save', { elementId: elementId, fieldHandle: fieldHandle, value: value });

        return fetch('/actions/smoke/save/field', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                throw new Error(data.error || 'Failed to save');
            }
            dispatchSmoke('after-save', { elementId: elementId, fieldHandle: fieldHandle, value: value, data: data });
            return data;
        })
        .catch(err => {
            dispatchSmoke('error', { elementId: elementId, fieldHandle: fieldHandle, error: err.message });
            throw err;
        });
    }

    /**
     * Cancel inline edit
     */
    function cancelInlineEdit(element, originalContent) {
        element.classList.remove('smoke-editing');
        element.innerHTML = originalContent;
    }

    /**
     * Setup keyboard shortcuts.
     *
     * Panel open/close, scroll-lock, and toasts are all handled reactively via DataStar
     * attributes in editor-container.twig / edit-panel.twig — no signal listener needed here.
     */
    function setupKeyboardShortcuts() {
        document.addEventListener('keydown', function(e) {
            const editor = document.getElementById('smoke-editor');
            const isOpen = editor && editor.getAttribute('data-smoke-open') === 'true';
            if (!isOpen) {
                return;
            }

            // ESC closes the panel (delegates to the DataStar-bound close button)
            if (e.key === 'Escape') {
                editor.querySelector('.smoke-close-btn')?.click();
            }

            // Cmd/Ctrl + S saves all
            if ((e.metaKey || e.ctrlKey) && e.key === 's') {
                e.preventDefault();
                editor.querySelector('.smoke-save-btn')?.click();
            }
        });
    }

    /**
     * Apply saved field values to on-page display elements (live refresh after a panel save).
     *
     * Called from a DataStar data-effect with the `smokeSavedFields` signal:
     *   { fieldHandle: { value: string, html: bool }, ... }
     */
    window.smokeApplySavedFields = function(saved) {
        if (!saved) {
            return;
        }
        Object.keys(saved).forEach(function(handle) {
            const data = saved[handle];
            document.querySelectorAll('[data-smoke-field="' + handle + '"]').forEach(function(el) {
                if (el.classList.contains('smoke-editing')) {
                    return; // don't clobber an in-progress inline edit
                }
                if (data.html) {
                    el.innerHTML = data.value;
                } else {
                    el.textContent = data.value;
                }
            });
        });
        dispatchSmoke('saved', { fields: saved });
    };

    /**
     * Show notification message
     */
    function showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `smoke-notification smoke-notification-${type}`;
        notification.textContent = message;

        document.body.appendChild(notification);

        // Auto-remove after 3 seconds
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
    }

    /**
     * Handle file uploads for asset fields
     */
    window.smokeUploadAsset = function(input, fieldHandle) {
        const files = input.files;
        if (!files || files.length === 0) return;

        const formData = new FormData();
        formData.append('file', files[0]);
        formData.append('fieldHandle', fieldHandle);

        // Show loading state
        const container = input.closest('.smoke-field-group');
        const loadingIndicator = document.createElement('div');
        loadingIndicator.className = 'smoke-loading';
        container.appendChild(loadingIndicator);

        // Upload via Craft's asset upload endpoint
        fetch('/actions/assets/upload', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            }
        })
        .then(response => response.json())
        .then(data => {
            loadingIndicator.remove();
            if (data.assetId) {
                // Update the hidden input with asset ID
                const hiddenInput = container.querySelector('input[type="hidden"]');
                if (hiddenInput) {
                    hiddenInput.value = data.assetId;
                }
                showNotification('Asset uploaded successfully', 'success');
            }
        })
        .catch(error => {
            loadingIndicator.remove();
            showNotification('Failed to upload asset', 'error');
        });
    };

    /**
     * Wire panel table editors: serialize rows to a hidden DataStar-bound JSON textarea
     * on every change, and handle add/remove row.
     */
    function wireTableEditors(root) {
        root.querySelectorAll('[data-smoke-table]:not([data-smoke-wired])').forEach(function(wrap) {
            wrap.setAttribute('data-smoke-wired', '1');

            const input = wrap.querySelector('.smoke-table-input');
            const tbody = wrap.querySelector('tbody');
            if (!input || !tbody) {
                return;
            }

            const serialize = function() {
                const rows = [];
                tbody.querySelectorAll('tr').forEach(function(tr) {
                    const row = {};
                    tr.querySelectorAll('input[data-col]').forEach(function(cell) {
                        row[cell.getAttribute('data-col')] = cell.value;
                    });
                    rows.push(row);
                });
                input.value = JSON.stringify(rows);
                input.dispatchEvent(new Event('input', { bubbles: true }));
            };

            wrap.addEventListener('input', function(e) {
                if (e.target.matches('input[data-col]')) {
                    serialize();
                }
            });

            wrap.addEventListener('click', function(e) {
                if (e.target.matches('.smoke-table-add')) {
                    const template = tbody.querySelector('tr');
                    if (template) {
                        const newRow = template.cloneNode(true);
                        newRow.querySelectorAll('input').forEach(function(i) { i.value = ''; });
                        tbody.appendChild(newRow);
                        serialize();
                    }
                } else if (e.target.matches('.smoke-table-remove')) {
                    if (tbody.querySelectorAll('tr').length > 1) {
                        e.target.closest('tr').remove();
                        serialize();
                    }
                }
            });

            serialize(); // seed the hidden input from the initial rows
        });
    }

})();
