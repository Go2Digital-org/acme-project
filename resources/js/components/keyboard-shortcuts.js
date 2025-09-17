/**
 * Global Keyboard Shortcuts Handler
 * Provides system-wide keyboard shortcuts for better UX
 */

function keyboardShortcuts() {
    return {
        // State
        shortcuts: new Map(),
        
        // Initialize
        init() {
            this.registerDefaultShortcuts();
            this.bindEventListeners();
        },
        
        // Register default shortcuts
        registerDefaultShortcuts() {
            // Search modal shortcut (⌘K / Ctrl+K)
            this.addShortcut(['meta+k', 'ctrl+k'], () => {
                this.triggerSearchModal();
            });
            
            // Close modal with Escape (global)
            this.addShortcut(['escape'], () => {
                this.closeActiveModal();
            });
        },
        
        // Add a keyboard shortcut
        addShortcut(keys, callback) {
            if (Array.isArray(keys)) {
                keys.forEach(key => {
                    this.shortcuts.set(this.normalizeKey(key), callback);
                });
            } else {
                this.shortcuts.set(this.normalizeKey(keys), callback);
            }
        },
        
        // Remove a keyboard shortcut
        removeShortcut(keys) {
            if (Array.isArray(keys)) {
                keys.forEach(key => {
                    this.shortcuts.delete(this.normalizeKey(key));
                });
            } else {
                this.shortcuts.delete(this.normalizeKey(keys));
            }
        },
        
        // Bind event listeners
        bindEventListeners() {
            document.addEventListener('keydown', this.handleKeydown.bind(this));
        },
        
        // Handle keydown events
        handleKeydown(event) {
            // Don't trigger shortcuts when user is typing in form fields
            if (this.isTypingInInput(event.target)) {
                return;
            }
            
            const key = this.getKeyCombo(event);
            const callback = this.shortcuts.get(key);
            
            if (callback) {
                event.preventDefault();
                event.stopPropagation();
                callback(event);
            }
        },
        
        // Check if user is typing in an input field
        isTypingInInput(element) {
            const tagName = element.tagName.toLowerCase();
            const inputTypes = ['input', 'textarea', 'select'];
            const contentEditable = element.contentEditable === 'true';
            
            return inputTypes.includes(tagName) || contentEditable;
        },
        
        // Get key combination string from event
        getKeyCombo(event) {
            const parts = [];
            
            // Add modifiers in consistent order
            if (event.ctrlKey) parts.push('ctrl');
            if (event.altKey) parts.push('alt');
            if (event.shiftKey) parts.push('shift');
            if (event.metaKey) parts.push('meta');
            
            // Add the actual key
            const key = event.key.toLowerCase();
            if (!['control', 'alt', 'shift', 'meta'].includes(key)) {
                parts.push(key);
            }
            
            return parts.join('+');
        },
        
        // Normalize key string for consistent storage
        normalizeKey(keyString) {
            return keyString.toLowerCase().split('+').sort().join('+');
        },
        
        // Trigger search modal
        triggerSearchModal() {
            // Find the Alpine component that controls the search modal
            const nav = document.querySelector('[x-data*="showSearchModal"]');
            if (nav && nav.__x) {
                const component = nav.__x.$data;
                if (component.showSearchModal !== undefined) {
                    component.showSearchModal = true;
                    
                    // Focus the search input after modal opens
                    this.$nextTick(() => {
                        const searchInput = document.querySelector('[x-data*="searchAutocomplete"] input[name="search"]');
                        if (searchInput) {
                            searchInput.focus();
                        }
                    });
                }
            }
        },
        
        // Close active modal
        closeActiveModal() {
            // Find any open modals and close them
            const nav = document.querySelector('[x-data*="showSearchModal"]');
            if (nav && nav.__x) {
                const component = nav.__x.$data;
                if (component.showSearchModal) {
                    component.showSearchModal = false;
                }
            }
        },
        
        // Get human-readable shortcut description
        getShortcutDescription(keyString) {
            const parts = keyString.split('+');
            const descriptions = {
                'meta': navigator.platform.includes('Mac') ? '⌘' : 'Win',
                'ctrl': 'Ctrl',
                'alt': 'Alt',
                'shift': 'Shift',
                'escape': 'Esc',
                'enter': 'Enter',
                'space': 'Space'
            };
            
            return parts.map(part => descriptions[part] || part.toUpperCase()).join(' + ');
        },
        
        // Debug helper - list all registered shortcuts
        listShortcuts() {
            console.group('Registered Keyboard Shortcuts:');
            this.shortcuts.forEach((callback, key) => {
                console.log(`${this.getShortcutDescription(key)}: ${key}`);
            });
            console.groupEnd();
        }
    };
}

// Export for ES6 module
export { keyboardShortcuts };