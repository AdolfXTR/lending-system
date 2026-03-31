<?php
// ============================================================
//  includes/modern_footer.php
//  Reusable footer component
//  Usage: <?php require_once 'includes/modern_footer.php'; ?>
// ============================================================
?>
            </div>
            <!-- End Page Content -->

            <!-- Footer -->
            <footer class="footer">
                <p class="mb-0">&copy; <?= date('Y') ?> <?= APP_NAME ?> &mdash; Modern Lending Platform. All rights reserved.</p>
            </footer>
        </div>
    </div>

    <script>
        // ── Dark Mode Manager ───────────────────────────────
        class DarkModeManager {
            constructor() {
                this.storageKey = 'lending_system_dark_mode';
                this.init();
            }

            init() {
                const savedMode = localStorage.getItem(this.storageKey);
                if (savedMode) {
                    if (savedMode === 'true') {
                        this.enable();
                    } else {
                        this.disable();
                    }
                } else {
                    if (this.getSystemPreference()) {
                        this.enable();
                    }
                }
                this.watchSystemTheme();
            }

            getSystemPreference() {
                return window.matchMedia('(prefers-color-scheme: dark)').matches;
            }

            watchSystemTheme() {
                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                    if (!localStorage.getItem(this.storageKey)) {
                        if (e.matches) {
                            this.enable();
                        } else {
                            this.disable();
                        }
                    }
                });
            }

            enable() {
                document.body.classList.add('dark-mode');
                localStorage.setItem(this.storageKey, 'true');
                this.updateToggleIcon(true);
            }

            disable() {
                document.body.classList.remove('dark-mode');
                localStorage.setItem(this.storageKey, 'false');
                this.updateToggleIcon(false);
            }

            toggle() {
                if (document.body.classList.contains('dark-mode')) {
                    this.disable();
                } else {
                    this.enable();
                }
            }

            isDarkMode() {
                return document.body.classList.contains('dark-mode');
            }

            updateToggleIcon(isDark) {
                const toggleElement = document.querySelector('.dark-mode-label');
                if (!toggleElement) return;
                if (isDark) {
                    toggleElement.textContent = 'Light';
                } else {
                    toggleElement.textContent = 'Dark';
                }
            }
        }

        // Initialize dark mode on page load
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                window.darkModeManager = new DarkModeManager();
            });
        } else {
            window.darkModeManager = new DarkModeManager();
        }

        // ── Mobile Sidebar Toggle ───────────────────────────
        document.addEventListener('DOMContentLoaded', function() {
            // Add any modern dashboard JS interactions here
            
            // Example: Smooth page transitions
            const links = document.querySelectorAll('.sidebar__link');
            links.forEach(link => {
                link.addEventListener('click', function(e) {
                    // Optional: Add transition effect
                    document.querySelector('.page-content').style.opacity = '0.5';
                });
            });
        });

        // ── Format Currency Helper ──────────────────────────
        function formatCurrency(amount) {
            return new Intl.NumberFormat('en-PH', {
                style: 'currency',
                currency: 'PHP',
                minimumFractionDigits: 2
            }).format(amount);
        }

        // ── Format Date Helper ──────────────────────────────
        function formatDate(dateString) {
            return new Date(dateString).toLocaleDateString('en-PH', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }


        // ── Close Alert Messages ────────────────────────────
        document.querySelectorAll('.alert').forEach(alert => {
            const closeBtn = alert.querySelector('[data-close]');
            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    alert.style.opacity = '0';
                    setTimeout(() => alert.style.display = 'none', 300);
                });
            }
        });
    </script>
</body>
</html>
