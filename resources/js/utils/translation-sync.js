/**
 * Translation Synchronization Utility
 * 
 * This utility helps synchronize translations between Laravel (PHP) and Vue.js (JavaScript)
 * ensuring consistency across the application.
 */

// Helper to convert Laravel trans_choice syntax to Vue i18n pluralization
export function convertPluralization(laravelString) {
  // Convert Laravel format ":count day|:count days" to Vue i18n format
  return laravelString.replace(/:count/g, '{count}');
}

// Helper to format currency consistently
export function formatCurrency(amount, locale = 'en') {
  return new Intl.NumberFormat(locale, {
    style: 'currency',
    currency: 'EUR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(amount);
}

// Helper to format dates consistently across locales
export function formatDate(dateString, locale = 'en') {
  const date = new Date(dateString);
  
  const formatOptions = {
    en: { month: 'short', day: 'numeric', year: 'numeric' },
    nl: { day: 'numeric', month: 'short', year: 'numeric' },
    fr: { day: 'numeric', month: 'short', year: 'numeric' }
  };
  
  return new Intl.DateTimeFormat(locale, formatOptions[locale] || formatOptions.en).format(date);
}

// Helper to format relative time
export function formatRelativeTime(dateString, locale = 'en') {
  const now = new Date();
  const date = new Date(dateString);
  const diffTime = now - date;
  
  const seconds = Math.floor(diffTime / 1000);
  const minutes = Math.floor(seconds / 60);
  const hours = Math.floor(minutes / 60);
  const days = Math.floor(hours / 24);
  
  const rtf = new Intl.RelativeTimeFormat(locale, { numeric: 'auto' });
  
  if (days > 0) {
    return rtf.format(-days, 'day');
  } else if (hours > 0) {
    return rtf.format(-hours, 'hour');
  } else if (minutes > 0) {
    return rtf.format(-minutes, 'minute');
  } else {
    return rtf.format(-seconds, 'second');
  }
}

// Helper to get campaign status translations
export function getCampaignStatusTranslation(status, t) {
  const statusMap = {
    'draft': t('campaigns.statusDraft'),
    'active': t('campaigns.statusActive'),
    'completed': t('campaigns.statusCompleted'),
    'cancelled': t('campaigns.statusCancelled'),
    'paused': t('campaigns.statusPaused')
  };
  
  return statusMap[status] || status;
}

// Helper to get donation status translations
export function getDonationStatusTranslation(status, t) {
  const statusMap = {
    'pending': t('donations.statusPending'),
    'completed': t('donations.statusCompleted'),
    'failed': t('donations.statusFailed'),
    'refunded': t('donations.statusRefunded'),
    'cancelled': t('donations.statusCancelled')
  };
  
  return statusMap[status] || status;
}

// Helper to format donation amounts with proper locale
export function formatDonationAmount(amount, locale = 'en') {
  return formatCurrency(amount, locale);
}

// Helper to format campaign progress percentage
export function formatProgress(current, target, locale = 'en') {
  const percentage = Math.round((current / target) * 100);
  return new Intl.NumberFormat(locale, {
    style: 'percent',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(percentage / 100);
}

// Helper to calculate days remaining
export function calculateDaysRemaining(endDate) {
  const now = new Date();
  const end = new Date(endDate);
  const diffTime = end - now;
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
  
  return Math.max(0, diffDays);
}

// Helper to format campaign categories
export function getCategoryTranslation(category, t) {
  const categoryMap = {
    'education': t('campaigns.categoryEducation'),
    'health': t('campaigns.categoryHealth'),
    'environment': t('campaigns.categoryEnvironment'),
    'animals': t('campaigns.categoryAnimals'),
    'humanitarian': t('campaigns.categoryHumanitarian'),
    'community': t('campaigns.categoryCommunity'),
    'arts': t('campaigns.categoryArts'),
    'sports': t('campaigns.categorySports'),
    'emergency': t('campaigns.categoryEmergency'),
    'other': t('campaigns.categoryOther')
  };
  
  return categoryMap[category] || category;
}

// Helper to validate form fields with proper locale error messages
export function getValidationError(field, rule, params, t) {
  const fieldName = t(`validation.attributes.${field}`) || field;
  
  switch (rule) {
    case 'required':
      return t('validation.required', { field: fieldName });
    case 'email':
      return t('validation.email', { field: fieldName });
    case 'min':
      return t('validation.min', { field: fieldName, min: params.min });
    case 'max':
      return t('validation.max', { field: fieldName, max: params.max });
    case 'numeric':
      return t('validation.numeric', { field: fieldName });
    case 'between':
      return t('validation.between', { field: fieldName, min: params.min, max: params.max });
    default:
      return t('validation.invalid', { field: fieldName });
  }
}

export default {
  convertPluralization,
  formatCurrency,
  formatDate,
  formatRelativeTime,
  getCampaignStatusTranslation,
  getDonationStatusTranslation,
  formatDonationAmount,
  formatProgress,
  calculateDaysRemaining,
  getCategoryTranslation,
  getValidationError
};