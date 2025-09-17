/**
 * Google-like Autocomplete Component for Meilisearch
 * Ultra-fast search with instant suggestions
 */

function searchAutocomplete(config = {}) {
    return {
        // Configuration
        searchEndpoint: config.searchEndpoint || '/api/search/suggestions',
        formAction: config.formAction || '/campaigns',
        placeholder: config.placeholder || 'Search campaigns, organizations, causes...',
        minChars: config.minChars || 2,
        debounceMs: config.debounceMs || 150,
        maxSuggestions: config.maxSuggestions || 10,
        entityType: config.entityType || 'campaign',
        employeeOnly: config.employeeOnly || false,
        
        // State
        query: config.initialQuery || '',
        suggestions: [],
        recentSearches: [],
        popularSearches: [],
        isLoading: false,
        showDropdown: false,
        selectedIndex: -1,
        abortController: null,
        searchTimer: null,
        
        // Lifecycle
        init() {
            this.loadRecentSearches();
            this.loadPopularSearches();
            
            // Close dropdown on outside click
            document.addEventListener('click', (e) => {
                if (!this.$el.contains(e.target)) {
                    this.closeDropdown();
                }
            });
        },
        
        // Search handling
        async handleInput() {
            // Cancel previous request
            if (this.abortController) {
                this.abortController.abort();
            }
            
            // Clear timer
            if (this.searchTimer) {
                clearTimeout(this.searchTimer);
            }
            
            // Reset selection
            this.selectedIndex = -1;
            
            // Check minimum characters
            if (this.query.length < this.minChars) {
                this.suggestions = [];
                this.showDropdown = this.query.length === 0;
                return;
            }
            
            // Debounce search
            this.searchTimer = setTimeout(() => {
                this.performSearch();
            }, this.debounceMs);
        },
        
        async performSearch() {
            this.isLoading = true;
            this.showDropdown = true;
            
            // Create new abort controller
            this.abortController = new AbortController();
            
            try {
                const params = new URLSearchParams({
                    q: this.query,
                    type: this.entityType,
                    limit: this.maxSuggestions
                });
                
                if (this.employeeOnly) {
                    params.append('employee_only', 'true');
                }
                
                const response = await fetch(`${this.searchEndpoint}?${params}`, {
                    signal: this.abortController.signal,
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    }
                });
                
                if (!response.ok) throw new Error('Search failed');
                
                const data = await response.json();
                
                // Process suggestions - handle both direct array and data.suggestions format
                let suggestions = [];
                if (data.data && data.data.suggestions) {
                    suggestions = data.data.suggestions;
                } else if (data.suggestions) {
                    suggestions = data.suggestions;
                } else if (Array.isArray(data)) {
                    suggestions = data;
                }
                
                this.suggestions = this.processSuggestions(suggestions);
                
            } catch (error) {
                if (error.name !== 'AbortError') {
                    console.error('Search error:', error);
                    this.suggestions = [];
                }
            } finally {
                this.isLoading = false;
            }
        },
        
        processSuggestions(suggestions) {
            // Enhance suggestions with highlighting
            return suggestions.map(suggestion => {
                // Handle both object and string suggestions
                if (typeof suggestion === 'string') {
                    const regex = new RegExp(`(${this.escapeRegex(this.query)})`, 'gi');
                    return {
                        text: suggestion,
                        highlightedText: suggestion.replace(regex, '<mark>$1</mark>')
                    };
                }
                
                const text = suggestion.text || suggestion.title || suggestion.name || '';
                const regex = new RegExp(`(${this.escapeRegex(this.query)})`, 'gi');
                return {
                    ...suggestion,
                    text: text,
                    highlightedText: text.replace(regex, '<mark>$1</mark>')
                };
            });
        },
        
        // Keyboard navigation
        handleKeydown(event) {
            switch(event.key) {
                case 'ArrowDown':
                    event.preventDefault();
                    this.selectNext();
                    break;
                case 'ArrowUp':
                    event.preventDefault();
                    this.selectPrevious();
                    break;
                case 'Enter':
                    event.preventDefault();
                    this.selectCurrent();
                    break;
                case 'Escape':
                    this.closeDropdown();
                    break;
            }
        },
        
        selectNext() {
            const total = this.getAllSuggestions().length;
            if (total > 0) {
                this.selectedIndex = (this.selectedIndex + 1) % total;
                this.scrollToSelected();
            }
        },
        
        selectPrevious() {
            const total = this.getAllSuggestions().length;
            if (total > 0) {
                this.selectedIndex = this.selectedIndex <= 0 ? total - 1 : this.selectedIndex - 1;
                this.scrollToSelected();
            }
        },
        
        selectCurrent() {
            const allSuggestions = this.getAllSuggestions();
            if (this.selectedIndex >= 0 && this.selectedIndex < allSuggestions.length) {
                this.selectSuggestion(allSuggestions[this.selectedIndex]);
            } else if (this.query) {
                this.submitSearch();
            }
        },
        
        scrollToSelected() {
            this.$nextTick(() => {
                const selected = this.$refs.dropdown?.querySelector('.selected');
                if (selected) {
                    selected.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                }
            });
        },
        
        // Selection handling
        selectSuggestion(suggestion) {
            if (typeof suggestion === 'string') {
                this.query = suggestion;
            } else {
                this.query = suggestion.text || suggestion.title || suggestion;
            }
            
            this.saveToRecent(this.query);
            this.submitSearch();
        },
        
        submitSearch() {
            if (this.query.trim()) {
                this.saveToRecent(this.query);
                
                // Find the form - check if we're in one or look for search-form by ID
                let form = this.$el.closest('form');
                if (!form) {
                    form = document.getElementById('search-form');
                }
                
                if (form) {
                    // Update the search input value before submitting
                    const searchInput = form.querySelector('input[name="search"]');
                    if (searchInput) {
                        searchInput.value = this.query;
                    }
                    form.submit();
                } else {
                    // Fallback: navigate directly
                    window.location.href = `${this.formAction}?search=${encodeURIComponent(this.query)}`;
                }
            }
        },
        
        // Recent searches
        loadRecentSearches() {
            try {
                const stored = localStorage.getItem('recentSearches');
                this.recentSearches = stored ? JSON.parse(stored) : [];
            } catch (e) {
                this.recentSearches = [];
            }
        },
        
        saveToRecent(query) {
            if (!query) return;
            
            // Remove if already exists
            this.recentSearches = this.recentSearches.filter(s => s !== query);
            
            // Add to beginning
            this.recentSearches.unshift(query);
            
            // Keep only last 5
            this.recentSearches = this.recentSearches.slice(0, 5);
            
            // Save to localStorage
            try {
                localStorage.setItem('recentSearches', JSON.stringify(this.recentSearches));
            } catch (e) {
                console.error('Failed to save recent searches');
            }
        },
        
        clearRecentSearches() {
            this.recentSearches = [];
            try {
                localStorage.removeItem('recentSearches');
            } catch (e) {
                console.error('Failed to clear recent searches');
            }
        },
        
        // Popular searches
        async loadPopularSearches() {
            // This could be fetched from an API endpoint
            this.popularSearches = [
                'education',
                'healthcare',
                'disaster relief',
                'environment',
                'poverty'
            ];
        },
        
        // UI helpers
        getAllSuggestions() {
            if (this.query.length >= this.minChars) {
                return this.suggestions;
            }
            
            // Show recent and popular when no query
            return [
                ...this.recentSearches.map(s => ({ text: s, type: 'recent' })),
                ...this.popularSearches.map(s => ({ text: s, type: 'popular' }))
            ];
        },
        
        isSelected(index) {
            return this.selectedIndex === index;
        },
        
        getSuggestionIcon(suggestion) {
            if (suggestion.type === 'recent') return 'fas fa-clock';
            if (suggestion.type === 'popular') return 'fas fa-fire';
            if (suggestion.category === 'organization') return 'fas fa-building';
            if (suggestion.category === 'user') return 'fas fa-user';
            return 'fas fa-search';
        },
        
        closeDropdown() {
            this.showDropdown = false;
            this.selectedIndex = -1;
        },
        
        openDropdown() {
            this.showDropdown = true;
        },
        
        // Utility
        escapeRegex(str) {
            return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        }
    };
}

// Export for ES6 module
export { searchAutocomplete };