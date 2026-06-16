<?php
/**
 * SERVIS PAYMENT CALCULATOR - INCLUDE FILE
 * =========================================
 * File ini berisi JavaScript untuk auto-calculate diskon member dan total pembayaran.
 * Include file ini di semua tab actions (_servis_actions_tab*.php)
 *
 * Fitur:
 * - Auto apply diskon member berdasarkan kategori
 * - Auto calculate total diskon (member + tambahan)
 * - Auto calculate PPN
 * - Auto calculate total bayar
 * - Auto calculate kembalian
 */
?>
<script type="text/javascript">
// ============ PAYMENT CALCULATOR MODULE ============
(function() {
    // Wait for jQuery
    if (typeof jQuery === 'undefined') {
        document.addEventListener('DOMContentLoaded', initPaymentCalculator);
    } else {
        jQuery(document).ready(initPaymentCalculator);
    }

    function initPaymentCalculator() {
        var $ = jQuery;

        // ============ FORMAT FUNCTIONS ============
        // Format number to Rupiah format (with thousand separator)
        window.formatRupiah = function(number) {
            return Math.round(number).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        };

        // Parse Rupiah format to number
        window.parseRupiah = function(str) {
            if (!str) return 0;
            return parseFloat(String(str).replace(/\./g, '').replace(',', '.')) || 0;
        };

        // ============ MAIN CALCULATION FUNCTION ============
        window.calculatePaymentTotal = function() {
            // Parse subtotal (remove formatting)
            var subtotalStr = $('#txttotal').val() || '0';
            var subtotal = parseRupiah(subtotalStr);

            // Get member discount percentage
            var diskonMemberPersen = parseFloat($('#txtdiskon_member').val()) || 0;

            // Get additional discount percentage
            var diskonTambahanPersen = parseFloat($('#txtpotfaktur_persen').val()) || 0;

            // Calculate discounts
            var diskonMemberNom = subtotal * (diskonMemberPersen / 100);
            var diskonTambahanNom = subtotal * (diskonTambahanPersen / 100);
            var totalDiskon = diskonMemberNom + diskonTambahanNom;

            // Calculate after discount
            var setelahDiskon = subtotal - totalDiskon;

            // Get PPN percentage
            var ppnPersen = parseFloat($('#txtpajak_persen').val()) || 0;
            var ppnNom = setelahDiskon * (ppnPersen / 100);

            // Calculate total
            var totalBayar = setelahDiskon + ppnNom;

            // Format and update fields
            $('#txtpotfaktur_nom').val(formatRupiah(totalDiskon));
            $('#txtpajak_nom').val(formatRupiah(ppnNom));
            $('#txtnet').val(formatRupiah(totalBayar));

            // Store raw values in hidden fields
            if (!$('#txtpotfaktur_nom_raw').length) {
                $('<input type="hidden" id="txtpotfaktur_nom_raw" name="txtpotfaktur_nom_raw">').insertAfter('#txtpotfaktur_nom');
            }
            $('#txtpotfaktur_nom_raw').val(totalDiskon);

            if (!$('#txtpajak_nom_raw').length) {
                $('<input type="hidden" id="txtpajak_nom_raw" name="txtpajak_nom_raw">').insertAfter('#txtpajak_nom');
            }
            $('#txtpajak_nom_raw').val(ppnNom);

            if (!$('#txtnet_raw').length) {
                $('<input type="hidden" id="txtnet_raw" name="txtnet_raw">').insertAfter('#txtnet');
            }
            $('#txtnet_raw').val(totalBayar);

            // Store diskon member nominal
            if (!$('#txtdiskon_member_nom').length) {
                $('<input type="hidden" id="txtdiskon_member_nom" name="txtdiskon_member_nom">').insertAfter('#txtdiskon_member');
            }
            $('#txtdiskon_member_nom').val(diskonMemberNom);

            // Calculate change (kembalian)
            calculateKembalian();

            return {
                subtotal: subtotal,
                diskonMemberPersen: diskonMemberPersen,
                diskonMemberNom: diskonMemberNom,
                diskonTambahanPersen: diskonTambahanPersen,
                diskonTambahanNom: diskonTambahanNom,
                totalDiskon: totalDiskon,
                ppnPersen: ppnPersen,
                ppnNom: ppnNom,
                totalBayar: totalBayar
            };
        };

        // ============ KEMBALIAN CALCULATION ============
        window.calculateKembalian = function() {
            var totalBayar = parseRupiah($('#txtnet').val());
            var jumlahBayar = parseRupiah($('#txtbayar').val());
            var kembalian = jumlahBayar - totalBayar;

            if (kembalian < 0) kembalian = 0;

            $('#txtkembalian').val(formatRupiah(kembalian));

            // Store raw value
            if (!$('#txtkembalian_raw').length) {
                $('<input type="hidden" id="txtkembalian_raw" name="txtkembalian_raw">').insertAfter('#txtkembalian');
            }
            $('#txtkembalian_raw').val(kembalian);
        };

        // ============ AUTO APPLY MEMBER DISCOUNT ============
        window.autoApplyMemberDiscount = function() {
            var diskonMember = parseFloat($('#txtdiskon_member').val()) || 0;

            if (diskonMember > 0) {
                console.log('Auto-applying member discount: ' + diskonMember + '%');

                // Show notification if function exists
                if (typeof showNotification === 'function') {
                    showNotification('info', 'Diskon member ' + diskonMember + '% diterapkan otomatis');
                }
            }

            // Calculate total
            calculatePaymentTotal();
        };

        // ============ EVENT LISTENERS ============
        // When additional discount changes
        $('#txtpotfaktur_persen').on('input change', function() {
            calculatePaymentTotal();
        });

        // When PPN changes
        $('#txtpajak_persen').on('input change', function() {
            calculatePaymentTotal();
        });

        // When payment amount changes
        $('#txtbayar').on('input change blur', function() {
            // Remove formatting for calculation
            var val = $(this).val().replace(/\./g, '');
            $(this).val(formatRupiah(parseFloat(val) || 0));
            calculateKembalian();
        });

        // When subtotal changes (for dynamic updates)
        $('#txttotal').on('change', function() {
            calculatePaymentTotal();
        });

        // ============ INITIALIZATION ============
        // Auto apply member discount on page load
        autoApplyMemberDiscount();

        console.log('Payment Calculator initialized');
    }
})();
</script>
