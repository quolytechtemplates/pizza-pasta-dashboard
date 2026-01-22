            </div>
            
            <div class="py-4 px-4 text-center border-top mt-5">
                <p class="mb-0 text-muted">© 2025 Sistemi i Menaxhimit Shkollor - Zhvilluar nga <a href="#" class="text-decoration-none" style="color: var(--primary-color);">QUOLYTECH</a></p>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simplebar@latest/dist/simplebar.min.js"></script>
    <script>
        // Sidebar toggle for mobile
        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            document.querySelector('.left-sidebar').classList.toggle('show');
        });
        
        document.getElementById('sidebarClose')?.addEventListener('click', function() {
            document.querySelector('.left-sidebar').classList.remove('show');
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.left-sidebar');
            const toggle = document.getElementById('sidebarToggle');
            
            if (window.innerWidth < 992) {
                if (!sidebar.contains(event.target) && event.target !== toggle && !toggle?.contains(event.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });
    </script>
    
    <?php if (isset($extra_js)): ?>
        <?php echo $extra_js; ?>
    <?php endif; ?>
</body>
</html>
