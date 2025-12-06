/**
 * Smoke Editor - Frontend JavaScript
 */

(function() {
    'use strict';

    // Initialize Smoke when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSmoke);
    } else {
        initSmoke();
    }

    function initSmoke() {
        // Setup inline editing for editable fields
        setupInlineEditing();

        // Listen for DataStar signals
        setupSignalListeners();

        // Add keyboard shortcuts
        setupKeyboardShortcuts();
    }

    /**
     * Setup inline editing for editable fields
     */
    function setupInlineEditing() {
        const editableFields = document.querySelectorAll('.smoke-editable[data-smoke-type="plaintext"]');

        editableFields.forEach(field => {
            field.addEventListener('click', function(e) {
                e.stopPropagation();

                // Don't re-edit if already editing
                if (this.classList.contains('smoke-editing')) {
                    return;
                }

                const elementId = this.getAttribute('data-smoke-element-id');
                const fieldHandle = this.getAttribute('data-smoke-field');
                const currentValue = this.textContent.trim();

                // Enter editing mode
                enterInlineEditMode(this, elementId, fieldHandle, currentValue);
            });

            // Add tooltip on hover
            field.addEventListener('mouseenter', function() {
                const fieldName = this.getAttribute('data-smoke-field');
                this.setAttribute('title', `Click to edit ${fieldName}`);
            });
        });
    }

    /**
     * Enter inline edit mode for a field
     */
    function enterInlineEditMode(element, elementId, fieldHandle, currentValue) {
        // Mark as editing
        element.classList.add('smoke-editing');

        // Store original content
        const originalContent = element.innerHTML;

        // Create input
        const input = document.createElement('input');
        input.type = 'text';
        input.className = 'smoke-inline-input';
        input.value = currentValue;

        // Create action buttons
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

        // Replace content with input
        element.innerHTML = '';
        element.appendChild(input);
        element.appendChild(actions);

        // Focus input and select all
        input.focus();
        input.select();

        // Handle save
        saveBtn.addEventListener('click', () => {
            saveInlineEdit(element, elementId, fieldHandle, input.value, originalContent);
        });

        // Handle cancel
        cancelBtn.addEventListener('click', () => {
            cancelInlineEdit(element, originalContent);
        });

        // Handle Enter key
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                saveInlineEdit(element, elementId, fieldHandle, input.value, originalContent);
            } else if (e.key === 'Escape') {
                e.preventDefault();
                cancelInlineEdit(element, originalContent);
            }
        });

        // Handle click outside
        const closeOnClickOutside = (e) => {
            if (!element.contains(e.target)) {
                cancelInlineEdit(element, originalContent);
                document.removeEventListener('click', closeOnClickOutside);
            }
        };
        setTimeout(() => document.addEventListener('click', closeOnClickOutside), 0);
    }

    /**
     * Save inline edit
     */
    function saveInlineEdit(element, elementId, fieldHandle, newValue, originalContent) {
        const actions = element.querySelector('.smoke-inline-actions');
        const saveBtn = actions.querySelector('.smoke-inline-save');

        // Show loading state
        saveBtn.classList.add('smoke-inline-saving');
        saveBtn.textContent = 'Saving...';

        // Prepare form data
        const formData = new FormData();
        formData.append('elementId', elementId);
        formData.append('fieldHandle', fieldHandle);
        formData.append(fieldHandle, newValue);
        formData.append(window.csrfToken ? 'CRAFT_CSRF_TOKEN' : '', window.csrfToken || '');

        // Save via API
        fetch('/actions/smoke/save/field', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update element with new value
                element.classList.remove('smoke-editing');
                element.textContent = newValue;
                showNotification('Saved successfully', 'success');
            } else {
                throw new Error(data.error || 'Failed to save');
            }
        })
        .catch(error => {
            // Restore original content on error
            element.classList.remove('smoke-editing');
            element.innerHTML = originalContent;
            showNotification(error.message, 'error');
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
     * Setup DataStar signal listeners
     */
    function setupSignalListeners() {
        // Watch for editor open/close state
        document.addEventListener('datastar-signal', function(e) {
            if (e.detail && e.detail.smokeEditorOpen !== undefined) {
                const editor = document.getElementById('smoke-editor');
                if (editor) {
                    editor.setAttribute('data-smoke-open', e.detail.smokeEditorOpen);

                    // Lock body scroll when editor is open
                    if (e.detail.smokeEditorOpen) {
                        document.body.style.overflow = 'hidden';
                    } else {
                        document.body.style.overflow = '';
                    }
                }
            }

            // Show success notification
            if (e.detail && e.detail.smokeSuccess) {
                showNotification(e.detail.smokeSuccess, 'success');
            }

            // Show error notification
            if (e.detail && e.detail.smokeError) {
                showNotification(e.detail.smokeError, 'error');
            }
        });
    }

    /**
     * Setup keyboard shortcuts
     */
    function setupKeyboardShortcuts() {
        document.addEventListener('keydown', function(e) {
            // ESC to close editor
            if (e.key === 'Escape') {
                const editor = document.getElementById('smoke-editor');
                if (editor && editor.getAttribute('data-smoke-open') === 'true') {
                    const closeBtn = editor.querySelector('[data-on-click*="close"]');
                    if (closeBtn) {
                        closeBtn.click();
                    }
                }
            }

            // Cmd/Ctrl + S to save
            if ((e.metaKey || e.ctrlKey) && e.key === 's') {
                const editor = document.getElementById('smoke-editor');
                if (editor && editor.getAttribute('data-smoke-open') === 'true') {
                    e.preventDefault();
                    const saveBtn = editor.querySelector('.smoke-save-btn');
                    if (saveBtn) {
                        saveBtn.click();
                    }
                }
            }
        });
    }

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
     * Initialize rich text editor (CKEditor)
     */
    window.smokeInitRichText = function(elementId) {
        // This would integrate with CKEditor
        // For POC, we'll use a simple textarea
        console.log('Rich text editor initialized for', elementId);
    };

    /**
     * Add table row
     */
    window.smokeAddTableRow = function(tableId) {
        const table = document.getElementById(tableId);
        if (!table) return;

        const tbody = table.querySelector('tbody');
        const firstRow = tbody.querySelector('tr');

        if (firstRow) {
            const newRow = firstRow.cloneNode(true);
            // Clear input values
            newRow.querySelectorAll('input').forEach(input => {
                input.value = '';
            });
            tbody.appendChild(newRow);
        }
    };

    /**
     * Remove table row
     */
    window.smokeRemoveTableRow = function(button) {
        const row = button.closest('tr');
        const tbody = row.closest('tbody');

        // Don't remove if it's the only row
        if (tbody.querySelectorAll('tr').length > 1) {
            row.remove();
        }
    };

})();
