import { createI18n } from 'vue-i18n';

// Import language files
import en from './lang/en/index.js';
import nl from './lang/nl/index.js';
import fr from './lang/fr/index.js';

// Get current locale from Laravel
function getCurrentLocale() {
  // Try to get locale from Laravel meta tag
  const metaLocale = document.querySelector('meta[name="locale"]');
  if (metaLocale) {
    return metaLocale.getAttribute('content');
  }
  
  // Fallback to HTML lang attribute
  const htmlLang = document.documentElement.lang;
  if (htmlLang) {
    return htmlLang.split('-')[0]; // Extract language code from locale like 'en-US'
  }
  
  // Ultimate fallback
  return 'en';
}

// Number formats for different locales
const numberFormats = {
  en: {
    currency: {
      style: 'currency',
      currency: 'EUR',
      notation: 'standard'
    },
    decimal: {
      style: 'decimal',
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    },
    percent: {
      style: 'percent',
      useGrouping: false
    }
  },
  nl: {
    currency: {
      style: 'currency',
      currency: 'EUR',
      notation: 'standard'
    },
    decimal: {
      style: 'decimal',
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    },
    percent: {
      style: 'percent',
      useGrouping: false
    }
  },
  fr: {
    currency: {
      style: 'currency',
      currency: 'EUR',
      notation: 'standard'
    },
    decimal: {
      style: 'decimal',
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    },
    percent: {
      style: 'percent',
      useGrouping: false
    }
  }
};

// Date-time formats for different locales
const datetimeFormats = {
  en: {
    short: {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    },
    long: {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      weekday: 'short',
      hour: 'numeric',
      minute: 'numeric'
    }
  },
  nl: {
    short: {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    },
    long: {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      weekday: 'short',
      hour: 'numeric',
      minute: 'numeric'
    }
  },
  fr: {
    short: {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    },
    long: {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      weekday: 'short',
      hour: 'numeric',
      minute: 'numeric'
    }
  }
};

// Create i18n instance
const i18n = createI18n({
  legacy: false, // Use Composition API
  locale: getCurrentLocale(),
  fallbackLocale: 'en',
  messages: {
    en,
    nl,
    fr
  },
  numberFormats,
  datetimeFormats,
  globalInjection: true, // Make $t available in templates
  silentTranslationWarn: true,
  silentFallbackWarn: true
});

// Sync with Laravel locale changes
export function setLocale(locale) {
  if (i18n.global.availableLocales.includes(locale)) {
    i18n.global.locale.value = locale;
    
    // Update HTML lang attribute
    document.documentElement.lang = locale;
    
    // Update meta tag if exists
    const metaLocale = document.querySelector('meta[name="locale"]');
    if (metaLocale) {
      metaLocale.setAttribute('content', locale);
    }
    
    // Store preference in localStorage
    localStorage.setItem('locale', locale);
    
    return true;
  }
  
  return false;
}

// Get available locales
export function getAvailableLocales() {
  return i18n.global.availableLocales;
}

// Format currency with current locale
export function formatCurrency(amount, locale = null) {
  const currentLocale = locale || i18n.global.locale.value;
  return new Intl.NumberFormat(currentLocale, {
    style: 'currency',
    currency: 'EUR'
  }).format(amount);
}

// Format number with current locale
export function formatNumber(number, locale = null) {
  const currentLocale = locale || i18n.global.locale.value;
  return new Intl.NumberFormat(currentLocale).format(number);
}

// Format date with current locale
export function formatDate(date, options = {}, locale = null) {
  const currentLocale = locale || i18n.global.locale.value;
  const defaultOptions = {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  };
  
  return new Intl.DateTimeFormat(currentLocale, { ...defaultOptions, ...options }).format(new Date(date));
}

// Format relative time (e.g., "2 days ago")
export function formatRelativeTime(date, locale = null) {
  const currentLocale = locale || i18n.global.locale.value;
  const rtf = new Intl.RelativeTimeFormat(currentLocale, { numeric: 'auto' });
  
  const now = new Date();
  const targetDate = new Date(date);
  const diffTime = targetDate - now;
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
  
  if (Math.abs(diffDays) < 1) {
    const diffHours = Math.ceil(diffTime / (1000 * 60 * 60));
    if (Math.abs(diffHours) < 1) {
      const diffMinutes = Math.ceil(diffTime / (1000 * 60));
      return rtf.format(diffMinutes, 'minute');
    }
    return rtf.format(diffHours, 'hour');
  }
  
  return rtf.format(diffDays, 'day');
}

// Pluralization helper
export function pluralize(count, single, plural = null) {
  if (count === 1) {
    return single;
  }
  
  return plural || single + 's';
}

// Translation helper with fallback
export function t(key, values = {}) {
  return i18n.global.t(key, values);
}

// Translation helper with pluralization
export function tc(key, count, values = {}) {
  return i18n.global.tc(key, count, { count, ...values });
}

export default i18n;