// Main JavaScript File
// Business Management System

$(document).ready(function() {
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
    
    // Confirm delete actions
    $('.btn-delete').on('click', function(e) {
        if (!confirm('کیا آپ واقعی یہ ریکارڈ حذف کرنا چاہتے ہیں؟')) {
            e.preventDefault();
        }
    });
    
    // Format currency inputs
    $('.currency-input').on('blur', function() {
        var value = parseFloat($(this).val()) || 0;
        $(this).val(value.toFixed(2));
    });
    
    // Calculate totals in forms
    $('.calculate-total').on('input', function() {
        calculateTotal();
    });
    
    function calculateTotal() {
        var total = 0;
        $('.item-amount').each(function() {
            total += parseFloat($(this).val()) || 0;
        });
        $('#total_amount').val(total.toFixed(2));
        
        var discount = parseFloat($('#discount').val()) || 0;
        var netAmount = total - discount;
        $('#net_amount').val(netAmount.toFixed(2));
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

