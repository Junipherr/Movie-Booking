        </main>
    </div>

<script>
    // Sidebar toggle (centralized)
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const mobileOverlay = document.getElementById('mobileOverlay');

    sidebarToggle?.addEventListener('click', () => {
        sidebar.classList.toggle('-translate-x-full');
        mobileOverlay.style.display = sidebar.classList.contains('-translate-x-full') ? 'none' : 'block';
    });

    mobileOverlay?.addEventListener('click', () => {
        sidebar.classList.add('-translate-x-full');
        mobileOverlay.style.display = 'none';
    });

    window.closeSidebar = () => {
        sidebar.classList.add('-translate-x-full');
        mobileOverlay.style.display = 'none';
    };

    // Close sidebar on nav link click (mobile only)
    document.querySelectorAll('nav a[href]').forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth < 1024) {
                closeSidebar();
            }
        });
    });

    // Update total movies (for dashboard)
    const totalMoviesEl = document.getElementById('totalMovies');
    if (totalMoviesEl && typeof movies !== 'undefined') {
        const totalMovies = Object.values(movies).reduce((sum, genre) => sum + genre.length, 0);
        totalMoviesEl.textContent = totalMovies;
    }
</script>
</body>
</html>
