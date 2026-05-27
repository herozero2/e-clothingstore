    </main>

    <!-- Footer -->
    <footer class="footer">
        <p>&copy; 2025 E-Clothing Store. All Rights Reserved.</p>
    </footer>

    <script>
        document.querySelectorAll('.dropdown-toggle').forEach(function(el) {
            el.addEventListener('click', function(e) {
                e.preventDefault();
                this.parentElement.classList.toggle('open');
            });
        });

        const adminGlobalSearch = document.getElementById('adminGlobalSearch');
        const localSearch = document.getElementById('searchInput');

        function filterAdminPage(query) {
            const normalized = query.trim().toLowerCase();

            if (localSearch && localSearch !== adminGlobalSearch) {
                localSearch.value = query;
                localSearch.dispatchEvent(new Event('keyup', { bubbles: true }));
                localSearch.dispatchEvent(new Event('input', { bubbles: true }));
                return;
            }

            document.querySelectorAll('tbody tr, .stat-box, .dashboard-panel').forEach(function(item) {
                const matches = !normalized || item.innerText.toLowerCase().includes(normalized);
                item.classList.toggle('admin-hidden-by-search', !matches);
            });
        }

        if (adminGlobalSearch) {
            adminGlobalSearch.addEventListener('input', function() {
                filterAdminPage(this.value);
            });
        }
    </script>
</body>
</html>
