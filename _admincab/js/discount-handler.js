/**
 * Discount Handler for Service Input
 * Mengelola semua logika tampilan dan perhitungan diskon (Promo & Member)
 * 
 * Dependencies: jQuery, AutoNumeric
 */

var DiscountHandler = {
    // Check discount for a single item
    checkItemDiscount: function (noitem, noPelanggan, itemType, callback) {
        if (!noitem) return;

        // Get service date from picker if available, else today
        var serviceDate = $('#id-date-picker-1').val();
        if (serviceDate) {
            // Convert dd/mm/yyyy to yyyy-mm-dd
            var parts = serviceDate.split('/');
            if (parts.length == 3) {
                serviceDate = parts[2] + '-' + parts[1] + '-' + parts[0];
            }
        }

        $.ajax({
            url: 'ajax-get-item-discount.php',
            type: 'GET',
            dataType: 'json',
            data: {
                noitem: noitem,
                no_pelanggan: noPelanggan,
                item_type: itemType,
                tanggal: serviceDate
            },
            success: function (response) {
                if (callback) callback(response);
            },
            error: function (xhr, status, error) {
                console.error("Discount check failed:", error);
                if (callback) callback({ success: false, has_discount: false });
            }
        });
    },

    // Format discount label for UI
    getDiscountLabel: function (discountData) {
        if (!discountData.has_discount) {
            if (discountData.is_excluded_member) {
                return '<span class="label label-warning label-white middle">NO DISC</span>';
            }
            return '';
        }

        var labelClass = 'label-info';
        var labelText = '';
        var valueText = '';

        if (discountData.discount_type == 'persen') {
            valueText = parseFloat(discountData.discount_value) + '%';
        } else {
            valueText = 'Rp ' + Number(discountData.discount_value).toLocaleString('id-ID');
        }

        if (discountData.discount_source == 'promo') {
            labelClass = 'label-success';
            labelText = 'PROMO: ' + valueText;
        } else if (discountData.discount_source == 'member') {
            labelClass = 'label-info';
            labelText = 'MEMBER: ' + valueText;
        }

        return '<span class="label ' + labelClass + ' label-white middle" title="' + (discountData.promo_name || '') + '">' + labelText + '</span>';
    },

    // Calculate net price after discount
    calculateNetPrice: function (price, qty, discountData) {
        var subtotal = price * qty;
        var discountAmount = 0;

        if (discountData.has_discount) {
            if (discountData.discount_type == 'persen') {
                discountAmount = subtotal * (parseFloat(discountData.discount_value) / 100);
            } else {
                // Nominal discount is usually per item unit
                discountAmount = parseFloat(discountData.discount_value) * qty;
            }
        }

        return {
            subtotal: subtotal,
            discount: discountAmount,
            net: subtotal - discountAmount
        };
    }
};

// Global helper for simple access
function formatDiscountBadge(source, value, type, isExcluded) {
    if (source === 'none' || !source) {
        return isExcluded ? '<span class="label label-warning label-white middle" style="font-size: 10px;">NO DISC</span>' : '';
    }

    var color = source === 'promo' ? 'label-success' : 'label-info';
    var text = source === 'promo' ? 'PROMO' : 'MEMBER';
    var val = type === 'nominal' ? 'Rp' + Number(value).toLocaleString('id-ID') : Number(value) + '%';

    return '<span class="label ' + color + ' label-white middle" style="font-size: 10px;">' + text + ' ' + val + '</span>';
}
