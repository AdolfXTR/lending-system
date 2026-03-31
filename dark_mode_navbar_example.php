<?php
/**
 * ============================================================
 *  Dark Mode Example - Navbar with Toggle
 *  Copy this code into your navbar/header files
 * ============================================================
 */
?>

<!-- NAVBAR WITH DARK MODE TOGGLE -->
<nav class="navbar">
    <div class="navbar__left">
        <h2 class="navbar__title">Lending Dashboard</h2>
    </div>

    <div class="navbar__right">
        <!-- Dark Mode Toggle Button -->
        <div class="navbar__item" 
             id="dark-mode-toggle" 
             onclick="window.darkModeManager.toggle()" 
             style="cursor: pointer; user-select: none; padding: 8px 12px; border-radius: 6px; transition: all 0.2s;"
             onmouseover="this.style.background='var(--bg-hover)'"
             onmouseout="this.style.background='transparent'">
            <span style="font-size: 18px;">🌙</span>
            <span>Dark</span>
        </div>

        <!-- User Profile -->
        <div class="navbar__user">
            <div class="navbar__avatar">JD</div>
            <span>John Doe</span>
        </div>
    </div>
</nav>

<!-- Script - Add this ONCE in your header/footer -->
<script src="assets/js/dark-mode.js"></script>

<!-- ============================================================
     USAGE NOTES
     ============================================================
     
     1. Include the dark-mode.js script in your page:
        <script src="assets/js/dark-mode.js"></script>
     
     2. Add the toggle button to your navbar (shown above)
     
     3. Click the button to toggle between light/dark mode
     
     4. The preference is saved automatically in localStorage
     
     5. On page load, it will check:
        - Saved preference first
        - Otherwise use system preference
        - If no system preference, default to light
     
     ============================================================
-->

<style>
    /* Optional: Custom styling for the toggle button */
    #dark-mode-toggle {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 8px 12px;
        border-radius: 6px;
        transition: all 0.2s ease;
        font-weight: 500;
    }

    #dark-mode-toggle:hover {
        background: var(--bg-hover);
    }

    /* Smooth transition for all elements when switching theme */
    * {
        transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease;
    }
</style>
