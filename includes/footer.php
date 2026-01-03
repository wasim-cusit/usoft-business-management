            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- Inputmask for phone number formatting -->
    <script src="https://cdn.jsdelivr.net/npm/inputmask@5.0.8/dist/inputmask.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/inputmask@5.0.8/dist/jquery.inputmask.min.js"></script>
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
        
        // Global Number Formatting Helper - Remove .00 for whole numbers
        function formatNumber(value) {
            var num = parseFloat(value) || 0;
            if (num % 1 === 0) {
                // Whole number - no decimals
                return num.toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0});
            } else {
                // Has decimals - show 2 decimal places
                return num.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            }
        }
        
        // Global Notification System - Reusable across all pages
        // Usage: showNotification('Message here', 'success', 5000) or showNotification('Error', 'error', 5000)
        // Override if already defined
        window.showNotification = function(message, type = 'success', duration = 5000) {
            const notificationDiv = document.getElementById('pageNotification');
            if (!notificationDiv) {
                console.warn('Notification container not found. Make sure #pageNotification exists in the page.');
                return;
            }
            
            const alertClass = type === 'success' ? 'alert-success' : 
                              (type === 'error' || type === 'danger' ? 'alert-danger' : 
                              (type === 'warning' ? 'alert-warning' : 'alert-info'));
            const icon = type === 'success' ? 'fa-check-circle' : 
                        (type === 'error' || type === 'danger' ? 'fa-exclamation-circle' : 
                        (type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle'));
            
            // Escape HTML to prevent XSS
            const escapedMessage = message.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
            
            notificationDiv.innerHTML = '<div class="alert ' + alertClass + ' alert-dismissible fade show" role="alert"><i class="fas ' + icon + '"></i> <span>' + escapedMessage + '</span><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            notificationDiv.style.display = 'block';
            
            // Auto-dismiss if duration is set (0 means don't auto-dismiss)
            if (duration > 0) {
                setTimeout(() => {
                    const alert = notificationDiv.querySelector('.alert');
                    if (alert) {
                        const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                        bsAlert.close();
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
        
        // Fix multiple modal backdrop issue - ensure backdrops are properly cleaned up
        document.addEventListener('DOMContentLoaded', function() {
            // Clean up any existing backdrops on page load
            const existingBackdrops = document.querySelectorAll('.modal-backdrop');
            existingBackdrops.forEach(backdrop => backdrop.remove());
            
            // Listen for modal hide events and clean up backdrops
            document.addEventListener('hidden.bs.modal', function(event) {
                // Remove all backdrops except the one for the currently open modal
                const openModals = document.querySelectorAll('.modal.show');
                const backdrops = document.querySelectorAll('.modal-backdrop');
                
                // If no modals are open, remove all backdrops
                if (openModals.length === 0) {
                    backdrops.forEach(backdrop => backdrop.remove());
                } else {
                    // Keep only one backdrop if a modal is still open
                    if (backdrops.length > 1) {
                        for (let i = 1; i < backdrops.length; i++) {
                            backdrops[i].remove();
                        }
                    }
                }
            });
            
            // Also clean up on modal show to prevent accumulation
            document.addEventListener('show.bs.modal', function(event) {
                const backdrops = document.querySelectorAll('.modal-backdrop');
                // Keep only one backdrop
                if (backdrops.length > 1) {
                    for (let i = 1; i < backdrops.length; i++) {
                        backdrops[i].remove();
                    }
                }
            });
        });
    </script>
    
    <?php if (isset($pageScripts)): ?>
        <?php foreach ($pageScripts as $script): ?>
            <script src="<?php echo BASE_URL . $script; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
