</div><!-- end ls-page -->

<div style="text-align:center;padding:24px;font-size:12px;color:#9ca3af;border-top:1px solid rgba(0,0,0,.06);margin-top:40px;font-family:'Plus Jakarta Sans',sans-serif;">
    &copy; <?= date('Y') ?> LendingSystem &mdash; All rights reserved.
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= APP_URL ?>/assets/js/dark-mode.js"></script>

<!-- Backup dark mode initialization -->
<script>
// Fallback initialization
setTimeout(() => {
    if (!window.darkModeManager) {
        console.log('Fallback: Initializing dark mode...');
        window.darkModeManager = new DarkModeManager();
    }
}, 100);
</script>
</body>
</html>