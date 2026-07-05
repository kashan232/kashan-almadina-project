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

$(document).on('input change', 'form input, form select, form textarea', function() {
    const $el = $(this);
    $el.removeClass('is-field-invalid');
    $el.next('.select2-container').removeClass('is-field-invalid');
    $el.closest('td, .col-md-1, .col-md-2, .col-md-3, .col-md-6, .col-md-7').find('.ajax-valid-error').remove();
});
</script>
