/**
 * American number formatting (1,234,567.89) disabled per user request.
 * Plain numeric inputs are restored across all system forms.
 */
(function (window) {
    'use strict';

    var USNumber = {
        _initialized: true,
        format: function (value) {
            return value === null || value === undefined ? '' : String(value);
        },
        parse: function (value) {
            if (value === null || value === undefined || value === '') return '';
            var cleaned = String(value).replace(/,/g, '').trim();
            var num = parseFloat(cleaned);
            return isNaN(num) ? 0 : num;
        },
        decimalsFor: function () { return 2; },
        shouldFormatInput: function () { return false; },
        prepareInput: function () {},
        formatInput: function () {},
        unformatInput: function () {},
        formatDisplayNode: function () {},
        scanInputs: function () {},
        scanDisplays: function () {},
        unformatForm: function () {},
        init: function () {}
    };

    window.USNumber = USNumber;
    window.formatUS = function (value) { return USNumber.format(value); };
    window.parseUS = function (value) { return USNumber.parse(value); };
    window.formatAmount = window.formatUS;
})(window);

