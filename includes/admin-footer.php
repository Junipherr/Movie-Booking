        </main>
    </div>

<script>
    /**
     * Admin Sidebar Toggle Functionality
     * Handles mobile sidebar open/close and overlay display
     * 
     * @requires: admin-header.php (defines sidebar, sidebarToggle, mobileOverlay elements)
     * @requires: data.js (loads movies data for dashboard stats)
     */

    // Get DOM elements for sidebar control
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const mobileOverlay = document.getElementById('mobileOverlay');

    // Toggle sidebar visibility on hamburger button click (mobile)
    sidebarToggle?.addEventListener('click', () => {
        sidebar.classList.toggle('-translate-x-full');
        // Show/hide overlay based on sidebar state
        mobileOverlay.style.display = sidebar.classList.contains('-translate-x-full') ? 'none' : 'block';
    });

    // Close sidebar when clicking on overlay (mobile)
    mobileOverlay?.addEventListener('click', () => {
        sidebar.classList.add('-translate-x-full');
        mobileOverlay.style.display = 'none';
    });

    /**
     * Close sidebar programmatically
     * Called by nav link click handler below
     * @global
     */
    window.closeSidebar = () => {
        sidebar.classList.add('-translate-x-full');
        mobileOverlay.style.display = 'none';
    };

    // Close sidebar when clicking nav link (mobile only, < 1024px)
    document.querySelectorAll('nav a[href]').forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth < 1024) {
                closeSidebar();
            }
        });
    });

    // Update total movies count on dashboard
    // Uses movies data from data.js (sample/demo data)
    // Note: Actual movie count comes from database query in admin-dashboard.php
    const totalMoviesEl = document.getElementById('totalMovies');
    if (totalMoviesEl && typeof movies !== 'undefined') {
        const totalMovies = Object.values(movies).reduce((sum, genre) => sum + genre.length, 0);
        totalMoviesEl.textContent = totalMovies;
    }
</script>
</body>
</html>
