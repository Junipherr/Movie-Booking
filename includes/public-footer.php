<?php
/**
 * Public Page Footer Component
 * 
 * Common HTML footer for all public-facing pages.
 * Closes main tag and body/html structure opened by public-header.php
 * 
 * @used-by: All public pages (index.php, login.php, register.php, etc.)
 * 
 * @see includes/public-header.php (complementary header opening)
 * @see includes/admin-footer.php (admin pages footer)
 */
?>

<!-- Footer Section - CineMovie branding and copyright -->
<footer class="bg-neutral-950 border-t border-neutral-800 py-8 mt-12">
    <div class="max-w-7xl mx-auto px-4 md:px-8">
        <div class="flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <!-- Brand name in Netflix-red color -->
                <span class="text-netflix-red text-xl font-bold">CineMovie</span>
            </div>
            <!-- Copyright notice -->
            <p class="text-gray-500 text-sm">© 2026 CineMovie. All rights reserved.</p>
        </div>
    </div>
</footer>

<!-- JavaScript for mobile menu and profile dropdown interactions -->
<script>
    // Mobile menu button click handler - toggles mobile navigation
    document.getElementById('mobileMenuBtn')?.addEventListener('click', function() {
        console.log('Mobile menu');
    });
    // Profile button click handler - toggles user dropdown menu
    document.getElementById('profileBtn')?.onclick = function() {
        console.log('Profile menu');
    };
</script>
</body>
</html>
