import { createApp } from 'vue';
import { createPinia } from 'pinia';
import i18n from './i18n.js';

// Create Pinia store
const pinia = createPinia();

// Auto-import all Vue components from module Infrastructure layers
const moduleComponents = import.meta.glob([
    '../../modules/*/Infrastructure/Vue/Components/**/*.vue',
    '../../modules/*/Infrastructure/Vue/Pages/**/*.vue'
], { eager: true });

// Create a component registry map for easy lookup
const componentRegistry = {};

// Register components by their file name (without path and extension)
Object.entries(moduleComponents).forEach(([path, module]) => {
    // Extract component name from path
    // e.g., "../../modules/Campaign/Infrastructure/Vue/Components/Display/CampaignCard.vue" -> "CampaignCard"
    const componentName = path.split('/').pop().replace('.vue', '');
    
    // Also create kebab-case version for data attributes
    const kebabName = componentName
        .replace(/([a-z])([A-Z])/g, '$1-$2')
        .toLowerCase();
    
    componentRegistry[componentName] = module.default;
    componentRegistry[kebabName] = module.default;
});

// Legacy component imports removed - components are now loaded via import.meta.glob above

// Extract props from data attributes
const extractProps = (element) => {
    const props = {};
    
    // Extract all data-prop-* attributes
    Array.from(element.attributes).forEach(attr => {
        if (attr.name.startsWith('data-prop-')) {
            const propName = attr.name
                .replace('data-prop-', '')
                .replace(/-([a-z])/g, (g) => g[1].toUpperCase()); // Convert kebab-case to camelCase
            
            try {
                // Try to parse as JSON first
                props[propName] = JSON.parse(attr.value);
            } catch {
                // If not valid JSON, use as string
                props[propName] = attr.value;
            }
        }
    });
    
    // Also check for inline data attributes (legacy support)
    const dataAttributes = ['campaigns', 'filters', 'user', 'csrf-token', 'locale'];
    dataAttributes.forEach(attr => {
        const dataAttr = element.dataset[attr] || element.getAttribute(`data-${attr}`);
        if (dataAttr) {
            const camelCaseAttr = attr.replace(/-([a-z])/g, (g) => g[1].toUpperCase());
            try {
                props[camelCaseAttr] = JSON.parse(dataAttr);
            } catch {
                props[camelCaseAttr] = dataAttr;
            }
        }
    });
    
    return props;
};

// Initialize Vue component on a specific element
const initVueComponent = (element, componentNameOrComponent, additionalProps = {}) => {
    if (!element) return null;
    
    // Determine the component
    let component;
    if (typeof componentNameOrComponent === 'string') {
        component = componentRegistry[componentNameOrComponent];
        if (!component) {
            console.warn(`Component "${componentNameOrComponent}" not found in registry`);
            return null;
        }
    } else {
        component = componentNameOrComponent;
    }
    
    // Extract props from element
    const elementProps = extractProps(element);
    const props = { ...elementProps, ...additionalProps };
    
    // Create and mount the app
    const app = createApp(component, props);
    app.use(pinia);
    app.use(i18n);
    
    // Provide global services
    app.provide('$componentRegistry', componentRegistry);
    
    // Mount the app
    app.mount(element);
    
    return app;
};

// Auto-initialize Vue components when DOM is ready
const autoInitialize = () => {
    // Find all elements with data-vue-component attribute
    const vueElements = document.querySelectorAll('[data-vue-component]');
    
    vueElements.forEach(element => {
        const componentName = element.dataset.vueComponent;
        
        if (componentName) {
            initVueComponent(element, componentName);
        }
    });
};

// Initialize on DOMContentLoaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', autoInitialize);
} else {
    // DOM is already loaded
    autoInitialize();
}

// Also reinitialize when Livewire updates (if using Livewire)
if (window.Livewire) {
    window.Livewire.on('contentChanged', () => {
        setTimeout(autoInitialize, 100);
    });
}

// Export for manual initialization if needed
export { 
    initVueComponent, 
    autoInitialize,
    componentRegistry,
    extractProps 
};

// For debugging in development
if (import.meta.env.DEV) {
    window.VueComponents = componentRegistry;
    console.log('Vue component registry:', Object.keys(componentRegistry));
}