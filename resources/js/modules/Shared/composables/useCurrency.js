import { ref, computed, watch } from 'vue'
import axios from 'axios'

// Global state for currency
const currentCurrency = ref(localStorage.getItem('currency') || 'EUR')
const currencies = ref([])
const isLoading = ref(false)

// Currency symbols mapping
const currencySymbols = {
    'EUR': '€',
    'USD': '$',
    'GBP': '£',
    'JPY': '¥',
    'CAD': 'C$',
    'AUD': 'A$',
    'CHF': 'CHF',
    'CNY': '¥'
}

export function useCurrency() {
    // Load currencies from API
    const loadCurrencies = async () => {
        if (currencies.value.length > 0) return
        
        try {
            isLoading.value = true
            const response = await axios.get('/api/v1/currencies')
            currencies.value = response.data.data || []
        } catch (error) {
            console.error('Failed to load currencies:', error)
            // Fallback to default currencies
            currencies.value = [
                { code: 'EUR', name: 'Euro', symbol: '€' },
                { code: 'USD', name: 'US Dollar', symbol: '$' },
                { code: 'GBP', name: 'British Pound', symbol: '£' }
            ]
        } finally {
            isLoading.value = false
        }
    }

    // Get current currency from session/API
    const getCurrentCurrency = async () => {
        try {
            const response = await axios.get('/api/v1/currencies/current')
            if (response.data.currency) {
                currentCurrency.value = response.data.currency
                localStorage.setItem('currency', response.data.currency)
            }
        } catch (error) {
            // Use localStorage or default
            currentCurrency.value = localStorage.getItem('currency') || 'EUR'
        }
    }

    // Set currency preference
    const setCurrency = async (currencyCode) => {
        try {
            isLoading.value = true
            
            // Update backend
            await axios.post('/api/v1/currencies/preference', {
                currency: currencyCode
            })
            
            // Update local state
            currentCurrency.value = currencyCode
            localStorage.setItem('currency', currencyCode)
            
            // Reload page to update server-rendered content
            window.location.reload()
        } catch (error) {
            console.error('Failed to set currency:', error)
        } finally {
            isLoading.value = false
        }
    }

    // Format currency amount
    const formatCurrency = (amount, currency = null) => {
        const curr = currency || currentCurrency.value
        const symbol = currencySymbols[curr] || curr
        
        // Simple formatting - just add symbol
        // In production, you'd want proper locale-based formatting
        if (curr === 'EUR') {
            return `${symbol}${amount.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
        } else {
            return `${symbol}${amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
        }
    }

    // Get currency symbol
    const getCurrencySymbol = (currency = null) => {
        const curr = currency || currentCurrency.value
        return currencySymbols[curr] || curr
    }

    // Computed properties
    const currentCurrencySymbol = computed(() => getCurrencySymbol())
    
    const currentCurrencyData = computed(() => {
        return currencies.value.find(c => c.code === currentCurrency.value) || {
            code: currentCurrency.value,
            symbol: getCurrencySymbol(),
            name: currentCurrency.value
        }
    })

    // Initialize on first use
    loadCurrencies()
    getCurrentCurrency()

    return {
        // State
        currentCurrency,
        currencies,
        isLoading,
        
        // Computed
        currentCurrencySymbol,
        currentCurrencyData,
        
        // Methods
        setCurrency,
        formatCurrency,
        getCurrencySymbol,
        loadCurrencies,
        getCurrentCurrency
    }
}