import { ref, computed } from 'vue';

export function useCurrency() {
    const currency = ref('EUR');
    const locale = ref('en-US');

    const formatCurrency = (amount, currencyCode = null) => {
        const useCurrency = currencyCode || currency.value;
        return new Intl.NumberFormat(locale.value, {
            style: 'currency',
            currency: useCurrency,
        }).format(amount);
    };

    const formatNumber = (number) => {
        return new Intl.NumberFormat(locale.value).format(number);
    };

    const formatPercentage = (value) => {
        return new Intl.NumberFormat(locale.value, {
            style: 'percent',
            minimumFractionDigits: 0,
            maximumFractionDigits: 1,
        }).format(value / 100);
    };

    const getCurrencySymbol = (currencyCode = null) => {
        const useCurrency = currencyCode || currency.value;
        return new Intl.NumberFormat(locale.value, {
            style: 'currency',
            currency: useCurrency,
        }).format(0).replace(/[\d.,\s]/g, '');
    };

    return {
        currency,
        locale,
        formatCurrency,
        formatNumber,
        formatPercentage,
        getCurrencySymbol,
    };
}