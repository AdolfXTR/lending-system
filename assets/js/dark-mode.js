/**
 * ============================================================
 *  Dark Mode Toggle - Lending System
 *  Handles automatic detection and manual toggling
 * ============================================================
 */

class DarkModeManager {
    constructor() {
        this.storageKey = 'lending_system_dark_mode';
        this.init();
    }

    /**
     * Initialize dark mode on page load
     */
    init() {
        // Check saved preference first
        const savedMode = localStorage.getItem(this.storageKey);
        
        if (savedMode) {
            // Use saved preference
            if (savedMode === 'true') {
                this.enable();
            } else {
                this.disable();
            }
        } else {
            // Check system preference
            if (this.getSystemPreference()) {
                this.enable();
            }
        }

        // Listen for system theme changes
        this.watchSystemTheme();
    }

    /**
     * Get system color scheme preference
     */
    getSystemPreference() {
        return window.matchMedia('(prefers-color-scheme: dark)').matches;
    }

    /**
     * Watch for system theme changes
     */
    watchSystemTheme() {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            // Only apply if user hasn't saved a preference
            if (!localStorage.getItem(this.storageKey)) {
                if (e.matches) {
                    this.enable();
                } else {
                    this.disable();
                }
            }
        });
    }

    /**
     * Enable dark mode
     */
    enable() {
        console.log('Enabling dark mode...');
        document.body.classList.add('dark-mode');
        localStorage.setItem(this.storageKey, 'true');
        console.log('Body classes:', document.body.classList.toString());
        this.updateToggleIcon(true);
    }

    /**
     * Disable dark mode
     */
    disable() {
        console.log('Disabling dark mode...');
        document.body.classList.remove('dark-mode');
        localStorage.setItem(this.storageKey, 'false');
        console.log('Body classes:', document.body.classList.toString());
        this.updateToggleIcon(false);
    }

    /**
     * Toggle dark mode on/off
     */
    toggle() {
        if (document.body.classList.contains('dark-mode')) {
            this.disable();
        } else {
            this.enable();
        }
    }

    /**
     * Check if dark mode is active
     */
    isDarkMode() {
        return document.body.classList.contains('dark-mode');
    }

    /**
     * Update toggle button icon/text
     */
    updateToggleIcon(isDark) {
        const toggleBtn = document.getElementById('dark-mode-toggle');
        if (!toggleBtn) return;

        const iconSpan = toggleBtn.querySelector('span:first-child');
        const labelSpan = toggleBtn.querySelector('.dark-mode-label');
        
        if (isDark) {
            if (iconSpan) iconSpan.textContent = '☀️';
            if (labelSpan) labelSpan.textContent = 'Light';
            toggleBtn.title = 'Switch to light mode';
            toggleBtn.setAttribute('aria-label', 'Switch to light mode');
        } else {
            if (iconSpan) iconSpan.textContent = '🌙';
            if (labelSpan) labelSpan.textContent = 'Dark';
            toggleBtn.title = 'Switch to dark mode';
            toggleBtn.setAttribute('aria-label', 'Switch to dark mode');
        }
    }
}

// Initialize on DOM ready
console.log('Dark mode script loading...');
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        console.log('DOM loaded, initializing dark mode...');
        window.darkModeManager = new DarkModeManager();
    });
} else {
    console.log('DOM already loaded, initializing dark mode...');
    window.darkModeManager = new DarkModeManager();
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DarkModeManager;
}
