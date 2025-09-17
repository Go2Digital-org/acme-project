/**
 * Global Keyboard Shortcuts Handler (Standalone)
 * Provides system-wide keyboard shortcuts without Alpine.js component integration
 */

function initializeGlobalKeyboardShortcuts() {
    // Add global keydown event listener
    document.addEventListener('keydown', handleGlobalKeydown);
}

// Initialize immediately if DOM is already loaded, otherwise wait for DOMContentLoaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeGlobalKeyboardShortcuts);
} else {
    // DOM is already loaded, initialize immediately
    initializeGlobalKeyboardShortcuts();
}

// Make the initialization function available globally for debugging
window.initializeGlobalKeyboardShortcuts = initializeGlobalKeyboardShortcuts;

function handleGlobalKeydown(event) {
    // Don't trigger shortcuts when user is typing in form fields
    if (isTypingInInput(event.target)) {
        return;
    }
    
    const keyCombo = getKeyCombo(event);
    
    // Handle ⌘K / Ctrl+K for search modal
    if (keyCombo === 'ctrl+k' || keyCombo === 'meta+k') {
        event.preventDefault();
        event.stopPropagation();
        triggerSearchModal();
        return;
    }
    
    // Handle Escape to close modal
    if (keyCombo === 'escape') {
        closeActiveModal();
        return;
    }
}

function isTypingInInput(element) {
    const tagName = element.tagName.toLowerCase();
    const inputTypes = ['input', 'textarea', 'select'];
    const contentEditable = element.contentEditable === 'true';
    
    return inputTypes.includes(tagName) || contentEditable;
}

function getKeyCombo(event) {
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
}

function triggerSearchModal() {
    console.log('Triggering search modal via keyboard shortcut...');
    
    // Strategy 1: Try to find the search button by iterating through buttons
    const buttons = document.querySelectorAll('button');
    let searchButton = null;
    
    for (let button of buttons) {
        const text = button.textContent || '';
        const ariaLabel = button.getAttribute('aria-label') || '';
        if (text.includes('Search campaigns') || ariaLabel.includes('Search')) {
            searchButton = button;
            break;
        }
    }
    
    if (searchButton) {
        console.log('Found search button, clicking it...');
        searchButton.click();
        focusSearchInput();
        return;
    }
    
    // Strategy 2: Try to find the body element with showSearchModal and set it directly
    const body = document.body;
    if (body && body.__x && body.__x.$data && 'showSearchModal' in body.__x.$data) {
        console.log('Found body with showSearchModal, setting to true...');
        body.__x.$data.showSearchModal = true;
        focusSearchInput();
        return;
    }
    
    // Strategy 3: Try to find any element with showSearchModal
    const elements = document.querySelectorAll('[x-data]');
    for (let element of elements) {
        if (element.__x && element.__x.$data && 'showSearchModal' in element.__x.$data) {
            console.log('Found element with showSearchModal, setting to true...');
            element.__x.$data.showSearchModal = true;
            focusSearchInput();
            return;
        }
    }
    
    // Strategy 4: Try one more time to find any button containing "Search"
    const allButtons = document.querySelectorAll('button');
    for (let button of allButtons) {
        if (button.textContent.toLowerCase().includes('search')) {
            console.log('Found button with search text, clicking it...');
            button.click();
            focusSearchInput();
            return;
        }
    }
    
    console.warn('Could not find search modal trigger mechanism');
}

function closeActiveModal() {
    // Try to find and close any open modals
    const body = document.body;
    if (body && body.__x && body.__x.$data && 'showSearchModal' in body.__x.$data) {
        if (body.__x.$data.showSearchModal) {
            console.log('Closing search modal...');
            body.__x.$data.showSearchModal = false;
        }
    }
    
    // Also try to find modal backdrop and click it
    const modalBackdrop = document.querySelector('[x-show="showSearchModal"]');
    if (modalBackdrop && modalBackdrop.style.display !== 'none') {
        modalBackdrop.click();
    }
}

function focusSearchInput() {
    // Wait a bit for the modal to appear, then focus the search input
    setTimeout(() => {
        const searchInput = document.querySelector('input[name="search"], input[placeholder*="search" i], input[type="search"]');
        if (searchInput) {
            console.log('Focusing search input...');
            searchInput.focus();
        }
    }, 100);
}

// Export for potential use elsewhere
window.GlobalKeyboardShortcuts = {
    triggerSearchModal,
    closeActiveModal,
    handleGlobalKeydown
};