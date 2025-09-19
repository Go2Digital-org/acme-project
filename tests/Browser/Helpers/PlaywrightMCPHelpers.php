<?php

declare(strict_types=1);

namespace Tests\Browser\Helpers;

/**
 * Token-efficient helper patterns for Playwright MCP browser testing.
 * These patterns minimize Claude Code token usage during browser automation.
 */
class PlaywrightMCPHelpers
{
    /**
     * Batch multiple DOM operations in a single evaluate call.
     * This reduces token usage compared to multiple individual actions.
     *
     * @return array<string, string>
     */
    public static function getBatchDOMOperations(): array
    {
        return [
            'fillMultipleFields' => "
                (fields) => {
                    fields.forEach(field => {
                        const element = document.querySelector(field.selector);
                        if (element) {
                            if (field.type === 'input' || field.type === 'textarea') {
                                element.value = field.value;
                                element.dispatchEvent(new Event('input', { bubbles: true }));
                                element.dispatchEvent(new Event('change', { bubbles: true }));
                            } else if (field.type === 'select') {
                                element.value = field.value;
                                element.dispatchEvent(new Event('change', { bubbles: true }));
                            } else if (field.type === 'checkbox' || field.type === 'radio') {
                                element.checked = field.value;
                                element.dispatchEvent(new Event('change', { bubbles: true }));
                            }
                        }
                    });
                }
            ",

            'extractFormData' => "
                (formSelector) => {
                    const form = document.querySelector(formSelector);
                    if (!form) return null;
                    
                    const data = {};
                    const inputs = form.querySelectorAll('input, textarea, select');
                    
                    inputs.forEach(input => {
                        const name = input.name || input.id;
                        if (name) {
                            if (input.type === 'checkbox') {
                                data[name] = input.checked;
                            } else if (input.type === 'radio') {
                                if (input.checked) data[name] = input.value;
                            } else {
                                data[name] = input.value;
                            }
                        }
                    });
                    
                    return data;
                }
            ",

            'waitForMultipleElements' => "
                (selectors, timeout = 5000) => {
                    return new Promise((resolve, reject) => {
                        const startTime = Date.now();
                        
                        const check = () => {
                            const allFound = selectors.every(selector => 
                                document.querySelector(selector) !== null
                            );
                            
                            if (allFound) {
                                resolve(true);
                            } else if (Date.now() - startTime > timeout) {
                                reject(new Error('Timeout waiting for elements'));
                            } else {
                                setTimeout(check, 100);
                            }
                        };
                        
                        check();
                    });
                }
            ",

            'extractPageMetadata' => "
                () => {
                    return {
                        title: document.title,
                        url: window.location.href,
                        readyState: document.readyState,
                        bodyText: document.body.innerText.substring(0, 1000),
                        formCount: document.querySelectorAll('form').length,
                        linkCount: document.querySelectorAll('a').length,
                        buttonCount: document.querySelectorAll('button').length,
                        inputCount: document.querySelectorAll('input').length,
                        hasErrors: document.querySelectorAll('.error, .alert-danger, [role=\"alert\"]').length > 0
                    };
                }
            ",

            'performBulkActions' => "
                (actions) => {
                    const results = [];
                    
                    actions.forEach(action => {
                        try {
                            switch(action.type) {
                                case 'click':
                                    const clickElement = document.querySelector(action.selector);
                                    if (clickElement) {
                                        clickElement.click();
                                        results.push({ success: true, action: action.type, selector: action.selector });
                                    }
                                    break;
                                    
                                case 'scroll':
                                    window.scrollTo(0, action.position || document.body.scrollHeight);
                                    results.push({ success: true, action: action.type });
                                    break;
                                    
                                case 'focus':
                                    const focusElement = document.querySelector(action.selector);
                                    if (focusElement) {
                                        focusElement.focus();
                                        results.push({ success: true, action: action.type, selector: action.selector });
                                    }
                                    break;
                                    
                                case 'setValue':
                                    const valueElement = document.querySelector(action.selector);
                                    if (valueElement) {
                                        valueElement.value = action.value;
                                        valueElement.dispatchEvent(new Event('input', { bubbles: true }));
                                        results.push({ success: true, action: action.type, selector: action.selector });
                                    }
                                    break;
                            }
                        } catch (error) {
                            results.push({ success: false, action: action.type, error: error.message });
                        }
                    });
                    
                    return results;
                }
            ",
        ];
    }

    /**
     * Smart waiting strategies to avoid token-heavy polling.
     *
     * @return array<string, string>
     */
    public static function getWaitStrategies(): array
    {
        return [
            'waitForNetworkIdle' => "
                () => {
                    return new Promise(resolve => {
                        let pendingRequests = 0;
                        let idleTimer;
                        
                        const startMonitoring = () => {
                            const observer = new PerformanceObserver((list) => {
                                for (const entry of list.getEntries()) {
                                    if (entry.entryType === 'resource') {
                                        pendingRequests++;
                                        setTimeout(() => {
                                            pendingRequests--;
                                            checkIdle();
                                        }, 100);
                                    }
                                }
                            });
                            
                            observer.observe({ entryTypes: ['resource'] });
                            
                            const checkIdle = () => {
                                clearTimeout(idleTimer);
                                if (pendingRequests === 0) {
                                    idleTimer = setTimeout(() => {
                                        observer.disconnect();
                                        resolve(true);
                                    }, 500);
                                }
                            };
                            
                            checkIdle();
                        };
                        
                        if (document.readyState === 'complete') {
                            startMonitoring();
                        } else {
                            window.addEventListener('load', startMonitoring);
                        }
                    });
                }
            ",

            'waitForAnimations' => '
                () => {
                    return new Promise(resolve => {
                        const animations = document.getAnimations();
                        if (animations.length === 0) {
                            resolve(true);
                        } else {
                            Promise.all(animations.map(a => a.finished)).then(() => resolve(true));
                        }
                    });
                }
            ',

            'smartWaitForContent' => "
                (content, options = {}) => {
                    const maxWait = options.maxWait || 10000;
                    const checkInterval = options.checkInterval || 100;
                    const searchIn = options.searchIn || 'body';
                    
                    return new Promise((resolve, reject) => {
                        const startTime = Date.now();
                        
                        const check = () => {
                            const container = document.querySelector(searchIn);
                            if (container && container.textContent.includes(content)) {
                                resolve(true);
                            } else if (Date.now() - startTime > maxWait) {
                                reject(new Error('Content \"' + content + '\" not found within ' + maxWait + 'ms'));
                            } else {
                                setTimeout(check, checkInterval);
                            }
                        };
                        
                        check();
                    });
                }
            ",
        ];
    }

    /**
     * State extraction patterns for efficient debugging without screenshots.
     *
     * @return array<string, string>
     */
    public static function getStateExtractionPatterns(): array
    {
        return [
            'extractFullPageState' => "
                () => {
                    const extractVisibleText = (element) => {
                        if (!element) return '';
                        const style = window.getComputedStyle(element);
                        if (style.display === 'none' || style.visibility === 'hidden') return '';
                        
                        let text = '';
                        for (const node of element.childNodes) {
                            if (node.nodeType === 3) {
                                text += node.textContent;
                            } else if (node.nodeType === 1) {
                                text += extractVisibleText(node);
                            }
                        }
                        return text.trim();
                    };
                    
                    return {
                        url: window.location.href,
                        title: document.title,
                        visibleText: extractVisibleText(document.body).substring(0, 2000),
                        forms: Array.from(document.forms).map(form => ({
                            id: form.id,
                            action: form.action,
                            method: form.method,
                            fields: Array.from(form.elements).map(el => ({
                                name: el.name,
                                type: el.type,
                                value: el.value,
                                required: el.required
                            }))
                        })),
                        errors: Array.from(document.querySelectorAll('.error, .alert, [role=\"alert\"]')).map(el => ({
                            text: el.textContent.trim(),
                            class: el.className
                        })),
                        interactables: {
                            buttons: document.querySelectorAll('button, [role=\"button\"]').length,
                            links: document.querySelectorAll('a[href]').length,
                            inputs: document.querySelectorAll('input, textarea, select').length
                        }
                    };
                }
            ",

            'extractTestableElements' => "
                () => {
                    const elements = [];
                    
                    // Find all testable elements
                    const testableSelectors = [
                        'button',
                        'a[href]',
                        'input',
                        'textarea',
                        'select',
                        '[role=\"button\"]',
                        '[role=\"link\"]',
                        '[data-test]',
                        '[data-testid]'
                    ];
                    
                    testableSelectors.forEach(selector => {
                        document.querySelectorAll(selector).forEach(el => {
                            const rect = el.getBoundingClientRect();
                            elements.push({
                                tag: el.tagName.toLowerCase(),
                                text: el.textContent?.trim().substring(0, 50),
                                value: el.value,
                                href: el.href,
                                id: el.id,
                                class: el.className,
                                dataTest: el.dataset.test || el.dataset.testid,
                                visible: rect.width > 0 && rect.height > 0,
                                position: { x: rect.x, y: rect.y }
                            });
                        });
                    });
                    
                    return elements;
                }
            ",
        ];
    }
}
