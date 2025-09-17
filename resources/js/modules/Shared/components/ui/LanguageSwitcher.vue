<template>
  <div class="language-switcher">
    <div class="dropdown" :class="{ 'is-active': isOpen }">
      <button 
        @click="toggleDropdown" 
        @keydown.esc="closeDropdown"
        class="dropdown-toggle"
        aria-haspopup="true"
        :aria-expanded="isOpen"
      >
        <span class="flag" :class="`flag-${currentLocale}`"></span>
        {{ getLocaleDisplayName(currentLocale) }}
        <svg class="chevron" :class="{ 'rotate-180': isOpen }" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
      </button>
      
      <Transition name="dropdown">
        <ul v-if="isOpen" class="dropdown-menu" @click.stop>
          <li v-for="locale in availableLocales" :key="locale.code">
            <a 
              :href="getLocalizedUrl(locale.code)" 
              class="dropdown-item"
              @click="changeLanguage(locale.code)"
            >
              <span class="flag" :class="`flag-${locale.code}`"></span>
              {{ locale.native }}
            </a>
          </li>
        </ul>
      </Transition>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'

interface Locale {
  code: string
  name: string
  native: string
  regional: string
}

const isOpen = ref(false)
const currentLocale = ref('en')

const supportedLocales: Record<string, Locale> = {
  'en': { code: 'en', name: 'English', native: 'English', regional: 'en_US' },
  'nl': { code: 'nl', name: 'Dutch', native: 'Nederlands', regional: 'nl_NL' },
  'fr': { code: 'fr', name: 'French', native: 'Français', regional: 'fr_FR' }
}

const availableLocales = computed(() => {
  return Object.values(supportedLocales).filter(locale => locale.code !== currentLocale.value)
})

const toggleDropdown = () => {
  isOpen.value = !isOpen.value
}

const closeDropdown = () => {
  isOpen.value = false
}

const getLocaleDisplayName = (localeCode: string) => {
  return supportedLocales[localeCode]?.native || localeCode.toUpperCase()
}

const getLocalizedUrl = (localeCode: string) => {
  const currentPath = window.location.pathname
  const currentLocalePattern = new RegExp(`^/(${Object.keys(supportedLocales).join('|')})/`)
  
  // Remove current locale from path
  const pathWithoutLocale = currentPath.replace(currentLocalePattern, '/')
  
  // Add new locale prefix
  if (localeCode === 'en' && pathWithoutLocale === '/') {
    return '/' // English homepage without prefix
  }
  
  return `/${localeCode}${pathWithoutLocale === '/' ? '' : pathWithoutLocale}`
}

const changeLanguage = (localeCode: string) => {
  currentLocale.value = localeCode
  closeDropdown()
  
  // Store language preference
  localStorage.setItem('locale', localeCode)
  document.cookie = `locale=${localeCode}; path=/; max-age=31536000` // 1 year
}

const detectCurrentLocale = () => {
  const path = window.location.pathname
  const match = path.match(/^\/([a-z]{2})\//i)
  
  if (match && supportedLocales[match[1]]) {
    currentLocale.value = match[1]
  } else if (path === '/') {
    currentLocale.value = 'en' // Default for homepage
  }
}

const handleClickOutside = (event: Event) => {
  const target = event.target as Element
  if (!target.closest('.language-switcher')) {
    closeDropdown()
  }
}

onMounted(() => {
  detectCurrentLocale()
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
.language-switcher {
  position: relative;
  display: inline-block;
}

.dropdown-toggle {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
  background-color: white;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  transition: colors 200ms;
}

.dropdown-toggle:hover {
  background-color: #f9fafb;
}

.dropdown-toggle:focus {
  outline: none;
  box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.5), 0 0 0 4px rgba(99, 102, 241, 0.1);
}

.dropdown-menu {
  position: absolute;
  right: 0;
  z-index: 50;
  margin-top: 0.5rem;
  width: 10rem;
  background-color: white;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
  padding: 0.25rem 0;
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
  color: #374151;
  text-decoration: none;
  transition: colors 150ms;
}

.dropdown-item:hover {
  background-color: #f3f4f6;
  color: #111827;
}

.flag {
  font-size: 1rem;
  line-height: 1;
}

.flag-en::before { content: "🇺🇸"; }
.flag-nl::before { content: "🇳🇱"; }
.flag-fr::before { content: "🇫🇷"; }

.chevron {
  width: 1rem;
  height: 1rem;
  transition: transform 200ms;
}

.rotate-180 {
  transform: rotate(180deg);
}

/* Dropdown transitions */
.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 200ms ease-in-out;
}

.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: scale(0.95) translateY(-0.25rem);
}

.dropdown-enter-to,
.dropdown-leave-from {
  opacity: 1;
  transform: scale(1) translateY(0);
}
</style>