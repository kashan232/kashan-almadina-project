/**
 * Global American number formatting (1,234,567.89) for forms, listings, and totals.
 */
(function (window, $) {
    'use strict';

    if (window.USNumber && window.USNumber._initialized) {
        return;
    }

    // Money, quantity, and pricing fields only — not document / invoice / ID fields.
    var NUMERIC_NAME = /amount|price|qty|quantity|total|subtotal|net|gross|disc|discount|wht|tax|balance|debit|credit|rate|retail|cost|paid|due|remaining|loss|profit|commission|row-total|row_amount|row-amount|fee|charge|percent_amt|line_total|grand|sum|margin|markup|cash|bank|cheque|unit_price|unitprice|carriage|freight|bilty|fare|rent|salary|wage|allowance|deduction|advance|refund|penalty|fine|bonus|opening_balance|stock|_qty|qty_|_amount|amount_|_price|price_|_total|total_|_balance|balance_|_rate|rate_|_cost|cost_|_paid|paid_|_due|due_|rv-amount/i;

    // System reference numbers, IDs, and contact fields must stay plain (no commas).
    var IDENTIFIER_EXCLUDE = /invoice|voucher|reference|ref_no|manual_inv|gwn|adj_id|sjid|sj_id|pjid|pj_id|rvid|pvid|jvid|evid|ivid|avid|doc_no|doc_id|hold_voucher|sale_id|purchase_id|product_id|item_id|account_id|warehouse_id|mobile|phone|cnic|ntn|strn|postal|zip|token|barcode|sku|hsn|payment_date|payment_method|payment_id|receipt_date|receipt_from|receipt_to|bill_date|entry_date|invoice_main|inv_main|(?:^|[\[._-])(?:[^.\[]*_)?(id|no|num|number|code|serial)(?:$|[\].\[_-])/i;

    var USNumber = {
        _initialized: true,

        format: function (value, decimals) {
            decimals = decimals === undefined || decimals === null ? 0 : parseInt(decimals, 10);
            if (value === null || value === undefined || value === '') {
                return '';
            }

            var num = typeof value === 'number' ? value : this.parse(value);
            if (num === '' || isNaN(num)) {
                return String(value);
            }

            return num.toLocaleString('en-US', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            });
        },

        parse: function (value) {
            if (value === null || value === undefined || value === '') {
                return '';
            }
            if (typeof value === 'number') {
                return isFinite(value) ? value : 0;
            }

            var cleaned = String(value).replace(/,/g, '').trim();
            if (cleaned === '' || cleaned === '-') {
                return cleaned;
            }

            var num = parseFloat(cleaned);
            return isNaN(num) ? 0 : num;
        },

        decimalsFor: function (el) {
            var $el = $(el);
            var data = $el.data('usDecimals');
            if (data !== undefined && data !== '') {
                return parseInt(data, 10);
            }

            var step = parseFloat($el.attr('step'));
            if (step > 0 && step < 1) {
                var parts = String(step).split('.');
                return parts[1] ? parts[1].length : 2;
            }

            var blob = (($el.attr('name') || '') + ' ' + ($el.attr('class') || '')).toLowerCase();
            if (/qty|quantity|stock|pcs|units|count|available|reserved|physical|hold|release|transfer/.test(blob)) {
                return 0;
            }

            return 2;
        },

        shouldFormatInput: function (el) {
            var $el = $(el);
            if ($el.data('usNumber') === 'off' || $el.hasClass('no-us-format')) {
                return false;
            }

            var type = (el.type || '').toLowerCase();
            if (type === 'hidden' || type === 'checkbox' || type === 'radio' || type === 'file' ||
                type === 'date' || type === 'time' || type === 'datetime-local' || type === 'month' || type === 'week') {
                return false;
            }

            var blob = (($el.attr('name') || '') + ' ' + ($el.attr('id') || '') + ' ' + ($el.attr('class') || '')).toLowerCase();

            if (IDENTIFIER_EXCLUDE.test(blob)) {
                return false;
            }

            if ($el.data('usNumber') === 'on') {
                return true;
            }

            if (NUMERIC_NAME.test(blob)) {
                return true;
            }

            if ($el.hasClass('us-num-input')) {
                return true;
            }

            return false;
        },

        prepareInput: function (el) {
            var $el = $(el);
            if (!$el.hasClass('us-num-input')) {
                $el.addClass('us-num-input');
            }

            if (($el.attr('type') || '').toLowerCase() === 'number') {
                $el.attr('type', 'text');
                if (!$el.attr('inputmode')) {
                    $el.attr('inputmode', 'decimal');
                }
            }
        },

        formatInput: function (el, force) {
            if (!el || document.activeElement === el && !force) {
                return;
            }

            var $el = $(el);
            var raw = $el.val();
            if (raw === '' || raw === null || raw === undefined) {
                return;
            }

            var num = this.parse(raw);
            if (num === '' || num === '-' || isNaN(num)) {
                return;
            }

            $el.val(this.format(num, this.decimalsFor(el)));
        },

        unformatInput: function (el) {
            var $el = $(el);
            var raw = $el.val();
            if (raw === '' || raw === null || raw === undefined) {
                return;
            }

            var num = this.parse(raw);
            if (num === '' || num === '-') {
                return;
            }

            var decimals = this.decimalsFor(el);
            if (decimals > 0) {
                $el.val(Number(num).toFixed(decimals));
            } else {
                $el.val(String(Math.round(Number(num))));
            }
        },

        formatDisplayNode: function (el) {
            var $el = $(el);
            if ($el.data('usFormatted')) {
                return;
            }
            if ($el.data('usNumber') === 'off' || $el.hasClass('no-us-format')) {
                return;
            }
            if ($el.closest('[data-us-number="off"], .no-us-format').length) {
                return;
            }
            if ($el.children('input,select,textarea,button,a,.select2').length) {
                return;
            }

            var text = $el.text().trim().replace(/\u00a0/g, '');
            if (!text || !/^-?[\d,]+(\.\d+)?$/.test(text)) {
                return;
            }

            var plain = text.replace(/,/g, '');
            if (!/^-?\d+(\.\d+)?$/.test(plain)) {
                return;
            }

            var decimals = $el.data('usDecimals');
            if (decimals === undefined || decimals === '') {
                decimals = plain.indexOf('.') >= 0 ? plain.split('.')[1].length : 0;
            } else {
                decimals = parseInt(decimals, 10);
            }

            $el.text(this.format(parseFloat(plain), decimals));
            $el.data('usFormatted', true);
        },

        scanInputs: function (root) {
            var self = this;
            $(root).find('input, textarea').each(function () {
                var $el = $(this);
                if (!self.shouldFormatInput(this)) {
                    if ($el.hasClass('us-num-input')) {
                        self.unformatInput(this);
                        $el.removeClass('us-num-input');
                    }
                    return;
                }
                self.prepareInput(this);
                if (document.activeElement !== this) {
                    self.formatInput(this, true);
                }
            });
        },

        scanDisplays: function (root) {
            var self = this;
            $(root).find('[data-us-number], .us-num, td.text-end, td.text-right, th.text-end, th.text-right, .amount-cell, .num-display').each(function () {
                self.formatDisplayNode(this);
            });
        },

        unformatForm: function (form) {
            var self = this;
            $(form).find('.us-num-input').each(function () {
                self.unformatInput(this);
            });
        },

        init: function (root) {
            root = root || document;
            this.scanInputs(root);
            this.scanDisplays(root);
        }
    };

    window.USNumber = USNumber;
    window.formatUS = function (value, decimals) {
        return USNumber.format(value, decimals);
    };
    window.parseUS = function (value) {
        return USNumber.parse(value);
    };
    window.formatAmount = window.formatUS;

    if ($ && $.fn) {
        var _val = $.fn.val;
        $.fn.val = function (value) {
            if (arguments.length === 0) {
                var current = _val.call(this);
                if (this.length === 1 && this.hasClass('us-num-input') && typeof current === 'string' && current.indexOf(',') !== -1) {
                    var parsed = USNumber.parse(current);
                    if (parsed !== '' && parsed !== '-') {
                        var decimals = USNumber.decimalsFor(this[0]);
                        return decimals > 0 ? Number(parsed).toFixed(decimals) : String(Math.round(Number(parsed)));
                    }
                }
                return current;
            }

            var self = this;
            this.each(function () {
                var $el = $(this);
                if ($el.hasClass('us-num-input') && value !== null && value !== undefined && value !== '') {
                    var num = typeof value === 'number' ? value : USNumber.parse(String(value));
                    if (num !== '' && num !== '-' && !isNaN(num)) {
                        if (document.activeElement === this) {
                            _val.call($el, decimalsValue(num, $el));
                        } else {
                            _val.call($el, USNumber.format(num, USNumber.decimalsFor(this)));
                        }
                        return;
                    }
                }
                _val.call($el, value);
            });
            return this;
        };

        function decimalsValue(num, $el) {
            var decimals = USNumber.decimalsFor($el[0]);
            return decimals > 0 ? Number(num).toFixed(decimals) : String(Math.round(Number(num)));
        }

        $(document)
            .on('focus', '.us-num-input', function () {
                USNumber.unformatInput(this);
            })
            .on('blur', '.us-num-input', function () {
                USNumber.formatInput(this, true);
            })
            .on('submit', 'form', function () {
                USNumber.unformatForm(this);
            })
            .on('click', 'button[type="submit"], input[type="submit"], #saveDraftBtn, #postBtn, [data-form-guard-post], [data-form-guard-save]', function () {
                var form = $(this).closest('form')[0];
                if (form) {
                    USNumber.unformatForm(form);
                }
            })
            .on('draw.dt', function () {
                USNumber.init(document);
            });

        $(document).ajaxSend(function () {
            var form = $(document.activeElement).closest('form')[0];
            if (form) {
                USNumber.unformatForm(form);
            }
        });

        $(function () {
            USNumber.init(document);

            var timer = null;
            var observer = new MutationObserver(function () {
                clearTimeout(timer);
                timer = setTimeout(function () {
                    USNumber.init(document);
                }, 120);
            });

            observer.observe(document.body, { childList: true, subtree: true });
        });
    } else {
        document.addEventListener('DOMContentLoaded', function () {
            USNumber.init(document);
        });
    }
})(window, window.jQuery);
