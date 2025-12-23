            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- Custom JS -->
    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
    
    <script>
        // Sidebar Toggle for Mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const isOpen = sidebar.classList.contains('show');
            
            if (isOpen) {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
            } else {
                sidebar.classList.add('show');
                overlay.classList.add('show');
            }
        }
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.querySelector('.sidebar-toggle');
            const overlay = document.getElementById('sidebarOverlay');
            
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && 
                    toggleBtn && !toggleBtn.contains(event.target) && 
                    sidebar.classList.contains('show')) {
                    sidebar.classList.remove('show');
                    overlay.classList.remove('show');
                }
            }
        });
        
        // Handle sidebar collapse events with Bootstrap 5
        document.addEventListener('DOMContentLoaded', function() {
            const collapseElements = document.querySelectorAll('#sidebar [data-bs-toggle="collapse"]');
            
            // Initialize chevron icons based on current state
            collapseElements.forEach(function(element) {
                const isExpanded = element.getAttribute('aria-expanded') === 'true';
                const chevron = element.querySelector('.fa-chevron-right, .fa-chevron-left');
                if (chevron) {
                    if (isExpanded) {
                        if (chevron.classList.contains('fa-chevron-right')) {
                            chevron.style.transform = 'rotate(90deg)';
                        } else {
                            chevron.style.transform = 'rotate(-90deg)';
                        }
                    }
                }
            });
            
            // Listen for Bootstrap collapse events
            const collapseTargets = document.querySelectorAll('#sidebar .collapse');
            collapseTargets.forEach(function(collapse) {
                collapse.addEventListener('show.bs.collapse', function() {
                    const trigger = document.querySelector('[href="#' + this.id + '"]');
                    if (trigger) {
                        trigger.setAttribute('aria-expanded', 'true');
                        const chevron = trigger.querySelector('.fa-chevron-right, .fa-chevron-left');
                        if (chevron) {
                            if (chevron.classList.contains('fa-chevron-right')) {
                                chevron.style.transform = 'rotate(90deg)';
                            } else {
                                chevron.style.transform = 'rotate(-90deg)';
                            }
                        }
                    }
                });
                
                collapse.addEventListener('hide.bs.collapse', function() {
                    const trigger = document.querySelector('[href="#' + this.id + '"]');
                    if (trigger) {
                        trigger.setAttribute('aria-expanded', 'false');
                        const chevron = trigger.querySelector('.fa-chevron-right, .fa-chevron-left');
                        if (chevron) {
                            chevron.style.transform = 'rotate(0deg)';
                        }
                    }
                });
            });
        });
        
        // Close sidebar on window resize if desktop
        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            if (window.innerWidth > 768) {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
            }
        });
        
        // Global Notification System - Reusable across all pages
        // Usage: showNotification('Message here', 'success', 5000) or showNotification('Error', 'error', 5000)
        function showNotification(message, type = 'success', duration = 0) {
            const notificationDiv = document.getElementById('pageNotification');
            if (!notificationDiv) {
                console.warn('Notification container not found. Make sure #pageNotification exists in the page.');
                return;
            }
            
            const alertClass = type === 'success' ? 'alert-success' : (type === 'error' ? 'alert-danger' : 'alert-info');
            const icon = type === 'success' ? 'fa-check-circle' : (type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle');
            
            notificationDiv.innerHTML = '<div class="alert ' + alertClass + ' alert-dismissible fade show"><i class="fas ' + icon + '"></i> <span>' + message + '</span><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            notificationDiv.style.display = 'block';
            
            // Auto-dismiss if duration is set (0 means don't auto-dismiss)
            if (duration > 0) {
                setTimeout(() => {
                    const alert = notificationDiv.querySelector('.alert');
                    if (alert) {
                        alert.classList.remove('show');
                        setTimeout(() => {
                            notificationDiv.style.display = 'none';
                            notificationDiv.innerHTML = '';
                        }, 300);
                    }
                }, duration);
            }
        }
        
        // Auto-dismiss existing error notifications on page load
        document.addEventListener('DOMContentLoaded', function() {
            const notificationDiv = document.getElementById('pageNotification');
            if (notificationDiv && notificationDiv.style.display === 'block') {
                const alert = notificationDiv.querySelector('.alert');
                if (alert && alert.classList.contains('alert-danger')) {
                    setTimeout(() => {
                        alert.classList.remove('show');
                        setTimeout(() => {
                            notificationDiv.style.display = 'none';
                            notificationDiv.innerHTML = '';
                        }, 300);
                    }, 5000);
                }
            }
        });
    </script>
    
    <?php if (isset($pageScripts)): ?>
        <?php foreach ($pageScripts as $script): ?>
            <script src="<?php echo BASE_URL . $script; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
