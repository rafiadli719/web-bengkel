/**
 * ============================================
 * VALIDASI NOMOR POLISI INDONESIA
 * ============================================
 * Format yang valid:
 * 1. Format Standar: B 1234 ABC (kode wilayah + spasi + angka + spasi + huruf)
 * 2. Format TNI/POLRI: 1234-56 (angka-angka)
 * 3. Format Diplomatik: 12 CD 34 (angka + spasi + CD/CC/RI + spasi + angka)
 * 
 * Aturan:
 * - Kode wilayah: 1-2 huruf (A-Z)
 * - Nomor: 1-4 digit angka
 * - Seri: 1-3 huruf (A-Z)
 * - Otomatis uppercase
 * - Otomatis format dengan spasi
 */

(function($) {
    'use strict';

    // Daftar kode wilayah yang valid di Indonesia
    var validWilayahCodes = [
        // Sumatera
        'BL', 'BB', 'BK', 'BA', 'BD', 'BE', 'BG', 'BM', 'BN', 'BP', 'BT',
        // Jawa
        'A', 'B', 'D', 'E', 'F', 'G', 'H', 'K', 'L', 'M', 'N', 'P', 'R', 'S', 'T', 'W', 'Z',
        'AA', 'AB', 'AD', 'AE', 'AG', 'AK', 'AN', 'AP', 'AR', 'AS', 'AW',
        // Bali & Nusa Tenggara
        'DK', 'DR', 'EA', 'EB', 'ED', 'KH', 'KT',
        // Kalimantan
        'DA', 'KB', 'KU', 'KT',
        // Sulawesi
        'DB', 'DC', 'DD', 'DE', 'DG', 'DH', 'DI', 'DJ', 'DL', 'DM', 'DN', 'DP', 'DQ', 'DR', 'DT', 'DW',
        // Maluku & Papua
        'DE', 'DG', 'PA', 'PB'
    ];

    // Fungsi untuk validasi format nopol
    window.NopolValidator = {
        
        /**
         * Validasi format nomor polisi
         * @param {string} nopol - Nomor polisi yang akan divalidasi
         * @return {object} - {valid: boolean, message: string, formatted: string}
         */
        validate: function(nopol) {
            if (!nopol || nopol.trim() === '') {
                return {
                    valid: false,
                    message: 'Nomor polisi tidak boleh kosong',
                    formatted: ''
                };
            }

            // Bersihkan input (hapus spasi berlebih, uppercase)
            var cleaned = nopol.trim().toUpperCase().replace(/\s+/g, ' ');
            
            // Pattern 1: Format Standar (B 1234 ABC)
            var pattern1 = /^([A-Z]{1,2})\s*(\d{1,4})\s*([A-Z]{1,3})$/;
            var match1 = cleaned.match(pattern1);
            
            if (match1) {
                var wilayah = match1[1];
                var nomor = match1[2];
                var seri = match1[3];
                
                // Validasi kode wilayah
                if (!this.isValidWilayah(wilayah)) {
                    return {
                        valid: false,
                        message: 'Kode wilayah "' + wilayah + '" tidak valid. Contoh kode valid: B, D, AB, BK',
                        formatted: ''
                    };
                }
                
                // Validasi panjang nomor (1-4 digit)
                if (nomor.length < 1 || nomor.length > 4) {
                    return {
                        valid: false,
                        message: 'Nomor harus 1-4 digit',
                        formatted: ''
                    };
                }
                
                // Validasi panjang seri (1-3 huruf)
                if (seri.length < 1 || seri.length > 3) {
                    return {
                        valid: false,
                        message: 'Seri huruf harus 1-3 karakter',
                        formatted: ''
                    };
                }
                
                // Format dengan spasi yang benar
                var formatted = wilayah + ' ' + nomor + ' ' + seri;
                
                return {
                    valid: true,
                    message: 'Format nomor polisi valid',
                    formatted: formatted
                };
            }
            
            // Pattern 2: Format TNI/POLRI (1234-56)
            var pattern2 = /^(\d{4})-?(\d{2})$/;
            var match2 = cleaned.match(pattern2);
            
            if (match2) {
                var formatted = match2[1] + '-' + match2[2];
                return {
                    valid: true,
                    message: 'Format TNI/POLRI valid',
                    formatted: formatted
                };
            }
            
            // Pattern 3: Format Diplomatik (12 CD 34)
            var pattern3 = /^(\d{1,2})\s*(CD|CC|RI)\s*(\d{1,2})$/;
            var match3 = cleaned.match(pattern3);
            
            if (match3) {
                var formatted = match3[1] + ' ' + match3[2] + ' ' + match3[3];
                return {
                    valid: true,
                    message: 'Format diplomatik valid',
                    formatted: formatted
                };
            }
            
            // Jika tidak cocok dengan pattern manapun
            return {
                valid: false,
                message: 'Format nomor polisi tidak valid. Contoh: B 1234 ABC, 1234-56, atau 12 CD 34',
                formatted: ''
            };
        },
        
        /**
         * Cek apakah kode wilayah valid
         * @param {string} wilayah - Kode wilayah
         * @return {boolean}
         */
        isValidWilayah: function(wilayah) {
            return validWilayahCodes.indexOf(wilayah.toUpperCase()) !== -1;
        },
        
        /**
         * Format otomatis saat user mengetik
         * @param {string} input - Input dari user
         * @return {string} - Formatted string
         */
        autoFormat: function(input) {
            if (!input) return '';
            
            // Uppercase dan hapus karakter selain huruf, angka, spasi, dan dash
            var cleaned = input.toUpperCase().replace(/[^A-Z0-9\s-]/g, '');
            
            // Coba deteksi pattern dan format otomatis
            // Pattern standar: huruf + angka + huruf
            var match = cleaned.match(/^([A-Z]{1,2})\s*(\d{1,4})\s*([A-Z]{0,3})$/);
            if (match) {
                var result = match[1];
                if (match[2]) result += ' ' + match[2];
                if (match[3]) result += ' ' + match[3];
                return result;
            }
            
            return cleaned;
        },
        
        /**
         * Inisialisasi validasi pada input field
         * @param {string} selector - jQuery selector untuk input field
         * @param {object} options - Opsi konfigurasi
         */
        init: function(selector, options) {
            var self = this;
            var settings = $.extend({
                showError: true,
                errorClass: 'has-error',
                successClass: 'has-success',
                errorElement: null,
                onValidate: null,
                autoFormat: true,
                validateOnBlur: true,
                validateOnInput: false
            }, options);
            
            $(selector).each(function() {
                var $input = $(this);
                var $formGroup = $input.closest('.form-group');
                var $errorElement = settings.errorElement ? $(settings.errorElement) : 
                                   $formGroup.find('.help-block.nopol-error');
                
                // Buat error element jika belum ada
                if ($errorElement.length === 0 && settings.showError) {
                    $errorElement = $('<span class="help-block nopol-error" style="color: #d9534f;"></span>');
                    $input.closest('.col-sm-6, .col-sm-8, .col-sm-9').append($errorElement);
                }
                
                // Auto uppercase
                $input.on('input', function() {
                    var cursorPos = this.selectionStart;
                    var oldValue = this.value;
                    
                    if (settings.autoFormat) {
                        this.value = self.autoFormat(this.value);
                    } else {
                        this.value = this.value.toUpperCase().replace(/[^A-Z0-9\s-]/g, '');
                    }
                    
                    // Restore cursor position
                    if (this.value.length !== oldValue.length) {
                        this.setSelectionRange(cursorPos, cursorPos);
                    }
                    
                    // Validasi on input jika diaktifkan
                    if (settings.validateOnInput) {
                        validateInput();
                    }
                });
                
                // Validasi on blur
                if (settings.validateOnBlur) {
                    $input.on('blur', function() {
                        validateInput();
                    });
                }
                
                // Fungsi validasi
                function validateInput() {
                    var value = $input.val().trim();
                    
                    if (value === '') {
                        $formGroup.removeClass(settings.errorClass + ' ' + settings.successClass);
                        if ($errorElement) $errorElement.text('').hide();
                        return;
                    }
                    
                    var result = self.validate(value);
                    
                    if (result.valid) {
                        $formGroup.removeClass(settings.errorClass).addClass(settings.successClass);
                        if ($errorElement) {
                            $errorElement.html('<i class="ace-icon fa fa-check green"></i> ' + result.message).show();
                        }
                        // Set formatted value
                        $input.val(result.formatted);
                    } else {
                        $formGroup.removeClass(settings.successClass).addClass(settings.errorClass);
                        if ($errorElement) {
                            $errorElement.html('<i class="ace-icon fa fa-times red"></i> ' + result.message).show();
                        }
                    }
                    
                    // Callback
                    if (settings.onValidate && typeof settings.onValidate === 'function') {
                        settings.onValidate(result);
                    }
                }
                
                // Expose validate method
                $input.data('validate', validateInput);
            });
        }
    };
    
})(jQuery);
