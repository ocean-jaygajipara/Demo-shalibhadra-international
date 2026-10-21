<script>
    /**
     * API Loading Helper Utility
     * Provides consistent loading state management for dependent API calls
     */
    (function() {
        'use strict';

        // Track active API requests
        const activeRequests = new Map();
        
        // Loading state management
        const LoadingManager = {
            /**
             * Show loading indicator for a specific element
             */
            showLoading: function(selector, options = {}) {
                const $element = $(selector);
                if ($element.length === 0) return;

                const config = {
                    showGlobalLoader: options.showGlobalLoader !== false,
                    showElementLoader: options.showElementLoader !== false,
                    disableElement: options.disableElement !== false,
                    loadingText: options.loadingText || 'Loading...'
                };

                // Disable element
                if (config.disableElement) {
                    $element.prop('disabled', true);
                    $element.addClass('api-loading');
                }

                // Show element-specific loader
                if (config.showElementLoader && $element.is('select')) {
                    const $wrapper = $element.closest('.form-group, .col-md-6, .col-md-4, .col-md-3');
                    if ($wrapper.length) {
                        $wrapper.addClass('api-loading-wrapper');
                        if (!$wrapper.find('.api-loading-spinner').length) {
                            $wrapper.append('<div class="api-loading-spinner"><div class="spinner-border spinner-border-sm text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
                        }
                    }
                }

                // Show global loader if needed
                if (config.showGlobalLoader) {
                    // Global loader is handled by ajaxStart/ajaxStop in app.blade.php
                }
            },

            /**
             * Hide loading indicator for a specific element
             */
            hideLoading: function(selector, options = {}) {
                const $element = $(selector);
                if ($element.length === 0) return;

                const config = {
                    enableElement: options.enableElement !== false,
                    hideElementLoader: options.hideElementLoader !== false
                };

                // Enable element
                if (config.enableElement) {
                    $element.prop('disabled', false);
                    $element.removeClass('api-loading');
                }

                // Hide element-specific loader
                if (config.hideElementLoader) {
                    const $wrapper = $element.closest('.form-group, .col-md-6, .col-md-4, .col-md-3');
                    $wrapper.removeClass('api-loading-wrapper');
                    $wrapper.find('.api-loading-spinner').remove();
                }
            },

            /**
             * Register an active API request
             */
            registerRequest: function(key, xhr) {
                activeRequests.set(key, xhr);
            },

            /**
             * Unregister an active API request
             */
            unregisterRequest: function(key) {
                activeRequests.delete(key);
            },

            /**
             * Abort a specific request
             */
            abortRequest: function(key) {
                const xhr = activeRequests.get(key);
                if (xhr && xhr.abort) {
                    xhr.abort();
                }
                activeRequests.delete(key);
            },

            /**
             * Abort all active requests
             */
            abortAllRequests: function() {
                activeRequests.forEach((xhr, key) => {
                    if (xhr && xhr.abort) {
                        xhr.abort();
                    }
                });
                activeRequests.clear();
            }
        };

        // Make it globally available
        window.ApiLoadingManager = LoadingManager;

        // Add CSS for loading states
        if (!document.getElementById('api-loading-styles')) {
            const style = document.createElement('style');
            style.id = 'api-loading-styles';
            style.textContent = `
                .api-loading {
                    opacity: 0.6;
                    cursor: not-allowed !important;
                    pointer-events: none;
                }
                .api-loading-wrapper {
                    position: relative;
                }
                .api-loading-spinner {
                    position: absolute;
                    top: 50%;
                    right: 10px;
                    transform: translateY(-50%);
                    z-index: 10;
                    pointer-events: none;
                }
                .api-loading-wrapper select {
                    padding-right: 40px;
                }
                .api-loading-wrapper .api-loading-spinner {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
            `;
            document.head.appendChild(style);
        }
    })();
</script>

