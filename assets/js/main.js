// Main JavaScript File
// Business Management System

// Global Notification System - Use the one from footer.php
// This is just a placeholder to ensure function exists before footer loads
if (typeof showNotification === 'undefined') {
    function showNotification(message, type, duration) {
        type = type || 'success';
        duration = duration || 5000;
        const notificationDiv = document.getElementById('pageNotification');
        if (!notificationDiv) return;
        
        const alertClass = type === 'success' ? 'alert-success' : 
                          (type === 'error' || type === 'danger' ? 'alert-danger' : 
                          (type === 'warning' ? 'alert-warning' : 'alert-info'));
        const iconClass = type === 'success' ? 'fa-check-circle' : 
                         (type === 'error' || type === 'danger' ? 'fa-exclamation-circle' :
                         (type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle'));
        
        notificationDiv.innerHTML = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                <i class="fas ${iconClass}"></i> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        
        notificationDiv.style.display = 'block';
        
        // Auto-hide after duration
        if (duration > 0) {
            setTimeout(function() {
                const alert = notificationDiv.querySelector('.alert');
                if (alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                    setTimeout(function() {
                        notificationDiv.style.display = 'none';
                    }, 300);
                }
            }, duration);
        }
    }
}

// Convert inline alerts to notifications on page load
$(document).ready(function() {
    // Convert existing success/error alerts to notifications
    $('.alert-success, .alert-danger, .alert-warning, .alert-info').each(function() {
        const $alert = $(this);
        const message = $alert.text().trim();
        const type = $alert.hasClass('alert-success') ? 'success' :
                    $alert.hasClass('alert-danger') ? 'error' :
                    $alert.hasClass('alert-warning') ? 'warning' : 'info';
        
        // Only convert if it's not inside a modal or specific container
        if (!$alert.closest('.modal, #itemFormMessage').length) {
            showNotification(message, type);
            $alert.remove();
        }
    });
    
    // Auto-hide alerts after 5 seconds (except those with no-auto-hide class)
    setTimeout(function() {
        $('.alert:not(.no-auto-hide)').fadeOut('slow');
    }, 5000);
    
    // Confirm delete actions
    $('.btn-delete').on('click', function(e) {
        if (!confirm('کیا آپ واقعی یہ ریکارڈ حذف کرنا چاہتے ہیں؟')) {
            e.preventDefault();
        }
    });
    
    // Format currency inputs - use global formatNumber function
    $('.currency-input').on('blur', function() {
        var value = parseFloat($(this).val()) || 0;
        if (typeof formatNumber === 'function') {
            $(this).val(formatNumber(value));
        } else {
            // Fallback if formatNumber not available
            if (value % 1 === 0) {
                $(this).val(value.toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0}));
            } else {
                $(this).val(value.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            }
        }
    });
    
    // Calculate totals in forms
    $('.calculate-total').on('input', function() {
        calculateTotal();
    });
    
    function calculateTotal() {
        var total = 0;
        $('.item-amount').each(function() {
            total += parseFloat(String($(this).val()).replace(/,/g, '')) || 0;
        });
        if (typeof formatNumber === 'function') {
            $('#total_amount').val(formatNumber(total));
        } else {
            $('#total_amount').val(total % 1 === 0 ? total.toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0}) : total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
        }
        
        var discount = parseFloat(String($('#discount').val()).replace(/,/g, '')) || 0;
        var netAmount = total - discount;
        if (typeof formatNumber === 'function') {
            $('#net_amount').val(formatNumber(netAmount));
        } else {
            $('#net_amount').val(netAmount % 1 === 0 ? netAmount.toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0}) : netAmount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
        }
    }
});

// Print function
function printPage() {
    window.print();
}

// Export to PDF (placeholder)
function exportToPDF() {
    alert('PDF ایکسپورٹ فیچر جلد شامل کیا جائے گا');
}

