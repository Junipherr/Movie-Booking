<footer class="bg-neutral-950 border-t border-neutral-800 py-8 mt-12">
    <div class="max-w-7xl mx-auto px-4 md:px-8">
        <div class="flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <span class="text-netflix-red text-xl font-bold">NETFLIX</span><span class="text-white font-light">MOVIES</span>
            </div>
            <p class="text-gray-500 text-sm">© 2024 Netflix Movies. All rights reserved.</p>
        </div>
    </div>
</footer>

<script>
    document.getElementById('mobileMenuBtn')?.addEventListener('click', function() {
        console.log('Mobile menu');
    });
    document.getElementById('profileBtn')?.onclick = function() {
        console.log('Profile menu');
    };
</script>
</body>
</html>
