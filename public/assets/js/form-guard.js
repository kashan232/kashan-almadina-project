/**
 * Global unsaved-form guard for add/edit screens.
 * Shows SweetAlert + sound when leaving a dirty form without saving draft.
 */
(function (window, document) {
    'use strict';

    if (window.FormGuard && window.FormGuard._initialized) {
        return;
    }

    var state = {
        baseline: '',
        dirty: false,
        enabled: false,
        bypassNavigation: false,
        recentSaveAction: null,
        confirming: false,
        target: null,
    };

    var SAVE_BTN_SELECTORS = [
        '#saveDraftBtn',
        '[data-form-guard-save]',
        '#saveBtn',
        '#btnSaveDraft',
    ].join(',');

    var POST_BTN_SELECTORS = [
        '#postBtn',
        '[data-form-guard-post]',
    ].join(',');

    function $(sel, root) {
        return (root || document).querySelector(sel);
    }

    function $$(sel, root) {
        return Array.prototype.slice.call((root || document).querySelectorAll(sel));
    }

    function isViewOnlyForm(form) {
        if (!form) return true;
        if (form.classList.contains('no-form-guard') || form.getAttribute('data-form-guard') === 'off') {
            return true;
        }
        if (form.classList.contains('view-mode') || form.classList.contains('form-locked')) {
            if (form.classList.contains('view-mode')) return true;
            if (form.querySelector('#saveDraftBtn[disabled], #saveDraftBtn.d-none') && !form.querySelector('#saveDraftBtn:not([disabled])')) {
                return true;
            }
        }
        if (form.classList.contains('delete-form') || form.closest('.d-inline')) {
            return true;
        }
        return false;
    }

    function countEditableFields(root) {
        var nodes = root.querySelectorAll(
            'input:not([type="hidden"]):not([type="button"]):not([type="submit"]):not([readonly]):not([disabled]),' +
            'select:not([disabled]), textarea:not([readonly]):not([disabled])'
        );
        var count = 0;
        nodes.forEach(function (el) {
            if (el.offsetParent === null && el.type !== 'hidden') {
                return;
            }
            count++;
        });
        return count;
    }

    function findGuardTarget() {
        var explicit = document.querySelector('[data-form-guard="on"]');
        if (explicit) return explicit;

        var draftForm = document.querySelector('#saveDraftBtn');
        if (draftForm) {
            var f = draftForm.closest('form');
            if (f && !isViewOnlyForm(f)) return f;
        }

        var forms = $$('form[method="POST"], form[method="post"], form:not([method])');
        for (var i = 0; i < forms.length; i++) {
            var form = forms[i];
            if (isViewOnlyForm(form)) continue;
            if (form.getAttribute('method') && form.getAttribute('method').toLowerCase() === 'get') continue;
            if (countEditableFields(form) >= 2) return form;
        }

        return null;
    }

    function serializeTarget(target) {
        if (!target) return '';
        if (target.tagName === 'FORM') {
            var fd = new FormData(target);
            fd.delete('_token');
            var parts = [];
            fd.forEach(function (value, key) {
                parts.push(key + '=' + String(value));
            });
            parts.sort();
            return parts.join('&');
        }
        var fields = target.querySelectorAll('input, select, textarea');
        var chunks = [];
        fields.forEach(function (el) {
            if (!el.name || el.type === 'hidden' || el.name === '_token') return;
            if (el.type === 'checkbox' || el.type === 'radio') {
                if (el.checked) chunks.push(el.name + '=' + el.value);
                return;
            }
            chunks.push(el.name + '=' + String(el.value));
        });
        chunks.sort();
        return chunks.join('&');
    }

    function captureBaseline() {
        state.baseline = serializeTarget(state.target);
        state.dirty = false;
    }

    function checkDirty() {
        if (!state.enabled || !state.target) {
            state.dirty = false;
            return false;
        }
        state.dirty = serializeTarget(state.target) !== state.baseline;
        return state.dirty;
    }

    function playAlertSound() {
        try {
            var AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) return;
            var ctx = new AudioCtx();
            [880, 660].forEach(function (freq, idx) {
                var osc = ctx.createOscillator();
                var gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.value = freq;
                gain.gain.value = 0.12;
                osc.connect(gain);
                gain.connect(ctx.destination);
                var start = ctx.currentTime + (idx * 0.22);
                osc.start(start);
                osc.stop(start + 0.18);
            });
        } catch (e) {
            /* silent fallback */
        }
    }

    function confirmLeave() {
        if (typeof Swal === 'undefined') {
            return Promise.resolve(window.confirm(
                'You have unsaved changes. Kindly complete and save this form, otherwise you will lose your data. Leave anyway?'
            ));
        }

        state.confirming = true;
        return Swal.fire({
            title: 'Unsaved Changes',
            html: 'Kindly complete and save this form, otherwise you will lose your data.<br><br>Do you want to leave without saving?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Leave Anyway',
            cancelButtonText: 'Stay on Page',
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            allowOutsideClick: false,
            focusCancel: true,
            didOpen: function () {
                playAlertSound();
            },
        }).then(function (result) {
            state.confirming = false;
            return !!(result && result.isConfirmed);
        });
    }

    function shouldIgnoreLink(link, event) {
        if (!link || !link.getAttribute('href')) return true;
        if (link.hasAttribute('download')) return true;
        if (link.getAttribute('target') === '_blank') return true;
        if (event && (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey)) return true;
        if (link.dataset.formGuardIgnore === 'true') return true;
        if (link.classList.contains('no-form-guard')) return true;

        var href = link.getAttribute('href').trim();
        if (!href || href === '#' || href.indexOf('#') === 0) return true;
        if (/^(javascript:|mailto:|tel:)/i.test(href)) return true;

        if (link.closest('.swal2-container')) return true;
        if (link.closest('[data-form-guard-ignore]')) return true;

        return false;
    }

    function isSamePageLink(href) {
        try {
            var url = new URL(href, window.location.origin);
            return url.pathname === window.location.pathname && url.search === window.location.search;
        } catch (e) {
            return false;
        }
    }

    function markClean() {
        captureBaseline();
        state.bypassNavigation = false;
        state.recentSaveAction = null;
    }

    function markSaved() {
        markClean();
    }

    function armSaveAction(type) {
        state.recentSaveAction = type || 'save';
        window.setTimeout(function () {
            if (state.recentSaveAction === type) {
                state.recentSaveAction = null;
            }
        }, 15000);
    }

    function initGuard() {
        if (document.body && document.body.getAttribute('data-form-guard') === 'off') {
            return;
        }

        state.target = findGuardTarget();
        if (!state.target) {
            state.enabled = false;
            return;
        }

        state.enabled = true;
        window.setTimeout(captureBaseline, 400);

        var onChange = function () {
            if (!state.bypassNavigation) {
                checkDirty();
            }
        };

        state.target.addEventListener('input', onChange, true);
        state.target.addEventListener('change', onChange, true);

        state.target.addEventListener('submit', function () {
            state.bypassNavigation = true;
            markClean();
        });

        if (window.jQuery) {
            jQuery(document).on('change', '.select2-hidden-accessible', onChange);
        }
    }

    document.addEventListener('click', function (event) {
        var saveBtn = event.target.closest(SAVE_BTN_SELECTORS);
        if (saveBtn) {
            armSaveAction('draft');
            return;
        }
        var postBtn = event.target.closest(POST_BTN_SELECTORS);
        if (postBtn) {
            armSaveAction('post');
        }
    }, true);

    document.addEventListener('click', function (event) {
        if (state.bypassNavigation || state.confirming || !state.enabled) return;
        if (!checkDirty()) return;

        var link = event.target.closest('a[href]');
        if (!link || shouldIgnoreLink(link, event)) return;

        var href = link.getAttribute('href');
        if (isSamePageLink(href)) return;

        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();

        confirmLeave().then(function (leave) {
            if (!leave) return;
            state.bypassNavigation = true;
            markClean();
            window.location.href = href;
        });
    }, true);

    window.addEventListener('beforeunload', function (event) {
        if (state.bypassNavigation || state.confirming || !state.enabled) return;
        if (!checkDirty()) return;
        event.preventDefault();
        event.returnValue = '';
    });

    if (window.jQuery) {
        jQuery(document).ajaxSuccess(function (_event, xhr, settings) {
            if (!state.recentSaveAction) return;
            var url = String(settings && settings.url ? settings.url : '').toLowerCase();
            var looksLikeSave = /save|store|update|draft|ajax\/save|ajax\/post/.test(url);
            if (!looksLikeSave) return;
            if (!(xhr.status >= 200 && xhr.status < 300)) return;

            var ok = true;
            try {
                var json = xhr.responseJSON || JSON.parse(xhr.responseText || '{}');
                if (json && (json.success === false || json.error)) ok = false;
            } catch (e) {
                ok = true;
            }

            if (ok) {
                markSaved();
            }
        });
    }

    document.addEventListener('DOMContentLoaded', initGuard);
    window.addEventListener('load', function () {
        if (!state.enabled) initGuard();
    });

    window.FormGuard = {
        _initialized: true,
        isDirty: function () {
            return checkDirty();
        },
        markClean: markClean,
        markSaved: markSaved,
        refresh: function () {
            initGuard();
            captureBaseline();
        },
        attach: function (selector) {
            var el = typeof selector === 'string' ? document.querySelector(selector) : selector;
            if (!el) return;
            el.setAttribute('data-form-guard', 'on');
            initGuard();
        },
    };

    document.addEventListener('form-guard:saved', markSaved);
})(window, document);
