<script>
window.VoucherFieldValidation = {
    errorHtml: function(msg) {
        return '<div class="ajax-valid-error"><i class="fa fa-exclamation-triangle"></i> ' + msg + '</div>';
    },

    clearErrors: function($form) {
        $form.find('.ajax-valid-error').remove();
        $form.find('.is-field-invalid').removeClass('is-field-invalid');
        $form.find('.select2-container.is-field-invalid').removeClass('is-field-invalid');
    },

    placeError: function($target, msg) {
        if (!$target || !$target.length) return;
        $target.addClass('is-field-invalid');
        const html = this.errorHtml(msg);
        const $s2 = $target.next('.select2-container');
        if ($s2.length) {
            $s2.addClass('is-field-invalid');
            $s2.before(html);
            return;
        }
        const $td = $target.closest('td');
        if ($td.length) {
            $td.find('.ajax-valid-error').remove();
            $td.prepend(html);
            return;
        }
        const $wrap = $target.closest('.col-md-1, .col-md-2, .col-md-3, .col-md-6, .col-md-7');
        if ($wrap.length) {
            $wrap.find('.ajax-valid-error').remove();
            $target.before(html);
            return;
        }
        $target.before(html);
    },

    applyErrors: function($form, errors) {
        this.clearErrors($form);
        if (!errors) return false;

        let hasErrors = false;
        $.each(errors, function(key, messages) {
            hasErrors = true;
            const msg = Array.isArray(messages) ? messages[0] : messages;
            let $target = null;

            if (key.indexOf('.') !== -1) {
                const parts = key.split('.');
                const fieldName = parts[0] + '[]';
                const index = parseInt(parts[1], 10);
                $target = $form.find('[name="' + fieldName + '"]').eq(index);
            } else {
                $target = $form.find('[name="' + key + '"], #' + key).first();
            }

            window.VoucherFieldValidation.placeError($target, msg);
        });

        if (hasErrors) {
            const $first = $form.find('.is-field-invalid, .select2-container.is-field-invalid').first();
            if ($first.length) {
                $('html, body').animate({ scrollTop: Math.max(0, $first.offset().top - 120) }, 200);
                $first.find('select:visible, input:visible').first().focus();
            }
        }

        return hasErrors;
    }
};

window.VoucherRowValidation = {
    validateLastRow: function($table) {
        let $lastTr = $table.find('tbody tr').last();
        if (!$lastTr.length) return true;

        let isValid = true;
        let $firstInvalid = null;

        // 1. Check Party / Account Select
        let $partyOrAcc = $lastTr.find('.rowPartySelect, .rowPartyName, .rowAccountSelect, .rowAccountSub, select[name="party_id[]"], select[name="row_account_id[]"], select[name="account_id[]"]').first();
        if ($partyOrAcc.length && !$partyOrAcc.val()) {
            isValid = false;
            $partyOrAcc.addClass('is-field-invalid');
            $partyOrAcc.next('.select2-container').addClass('is-field-invalid');
            if (!$firstInvalid) $firstInvalid = $partyOrAcc;
        }

        // 2. Check Amount / Debit / Credit Input
        let $amounts = $lastTr.find('input.row-amount, input.amount, input.row-debit, input.row-credit, input[name="amount[]"], input[name="debit[]"], input[name="credit[]"]');
        if ($amounts.length) {
            let hasValidNum = false;
            $amounts.each(function() {
                let v = parseFloat($(this).val());
                if (!isNaN(v) && v > 0) {
                    hasValidNum = true;
                }
            });
            if (!hasValidNum) {
                isValid = false;
                let $amtInput = $amounts.first();
                $amtInput.addClass('is-field-invalid');
                if (!$firstInvalid) $firstInvalid = $amtInput;
            }
        }

        if (!isValid) {
            if ($firstInvalid) {
                $firstInvalid.focus();
                if ($firstInvalid.hasClass('select2-hidden-accessible')) {
                    try { $firstInvalid.select2('open'); } catch(e) {}
                }
            }
            let msg = 'Pehle maujuda row ki details aur amount poori karein.';
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Line Incomplete',
                    text: msg,
                    timer: 2000,
                    showConfirmButton: false
                });
            } else if (typeof showAlert === 'function') {
                showAlert(msg, 'danger');
            } else {
                alert(msg);
            }
            return false;
        }
        return true;
    }
};

$(document).on('input change', 'form input, form select, form textarea', function() {
    const $el = $(this);
    $el.removeClass('is-field-invalid');
    $el.next('.select2-container').removeClass('is-field-invalid');
    $el.closest('td, .col-md-1, .col-md-2, .col-md-3, .col-md-6, .col-md-7').find('.ajax-valid-error').remove();
});
</script>
