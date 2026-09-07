    .stock-hold-page.container-fluid { padding: .4rem .6rem !important; }
    .stock-hold-page .main-content-inner { padding: 0 !important; background: #f4f7fa; min-height: 100vh; }
    .stock-hold-page .form-card { border-radius: 6px; border: none; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,.075); margin-bottom: .4rem !important; }
    .stock-hold-page .card-body { padding: .6rem .75rem !important; }
    .stock-hold-page .card-body.p-2 { padding: .6rem .75rem !important; }
    .stock-hold-page .card-footer { padding: .5rem .75rem !important; }
    .stock-hold-page .mb-2, .stock-hold-page .mb-3, .stock-hold-page .mb-4 { margin-bottom: .4rem !important; }
    .stock-hold-page .row.g-2 { --bs-gutter-x: .5rem; --bs-gutter-y: .3rem; }
    .stock-hold-page .form-control-sm, .stock-hold-page .form-select-sm,
    .stock-hold-page .form-control, .stock-hold-page .form-select {
        font-size: .85rem !important; height: 32px !important; min-height: 32px !important;
        padding: .2rem .5rem !important; border-radius: 4px !important; border: 1px solid #dee2e6 !important;
    }
    .stock-hold-page .form-label { font-size: .78rem !important; font-weight: 700 !important; color: #475569 !important; text-transform: uppercase; margin-bottom: .2rem !important; letter-spacing: 0.3px; }
    .stock-hold-page .select2-container--default .select2-selection--single {
        height: 32px !important; font-size: .85rem !important; border-radius: 4px !important; border: 1px solid #dee2e6 !important;
    }
    .stock-hold-page .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 30px !important; padding-left: 8px !important; }
    .stock-hold-page .select2-container--default .select2-selection--single .select2-selection__arrow { height: 30px !important; }
    .stock-hold-page #voucherTable { font-size: .85rem !important; }
    .stock-hold-page #voucherTable thead th {
        padding: 6px 8px !important; font-size: .78rem !important; height: 30px !important;
        background: #f8fafc !important; color: #334155 !important; font-weight: 700 !important;
        text-transform: uppercase; border-bottom: 2px solid #cbd5e1 !important;
    }
    .stock-hold-page #voucherTable tbody td { padding: 4px 6px !important; vertical-align: middle !important; border-bottom: 1px solid #e2e8f0 !important; }
    .stock-hold-page #voucherTable .form-control, .stock-hold-page #voucherTable .form-select { height: 28px !important; min-height: 28px !important; padding: 2px 6px !important; font-size: .82rem !important; }
    .stock-hold-page #voucherTable tfoot td { padding: .4rem .5rem !important; }
    .stock-hold-page .total-highlight-input {
        font-weight: 800 !important;
        font-size: 1.05rem !important;
        color: #0f172a !important;
        background-color: #f8fafc !important;
        border: 1px solid #94a3b8 !important;
    }
    .stock-hold-page .bottom-bar-btns { gap: .5rem !important; }
    .stock-hold-page .bottom-bar-btns .btn { padding: .35rem .85rem !important; font-size: .85rem !important; font-weight: 600; }
    .stock-hold-page .header-info-box { background: #fff; border-left: 4px solid {{ $accentColor ?? '#3b82f6' }}; padding: .4rem .75rem !important; border-radius: 4px; }
    .stock-hold-page .header-info-box h6 { font-size: .9rem !important; margin: 0 !important; font-weight: 700; }
    .stock-hold-page .badge { font-size: 11px !important; padding: .25rem .55rem !important; }
    .stock-hold-page .gap-3 { gap: .5rem !important; }
    .stock-hold-page .alert { padding: .45rem .75rem !important; margin-bottom: .4rem !important; font-size: .82rem !important; }
    .stock-hold-page .ajax-valid-error {
        display: block; color: #dc3545 !important; font-size: 11px !important; font-weight: 700;
        line-height: 1.2; margin-top: 2px; margin-bottom: 2px; white-space: normal;
    }
    .stock-hold-page .is-field-invalid,
    .stock-hold-page .select2-container.is-field-invalid .select2-selection--single {
        border-color: #dc3545 !important;
        box-shadow: 0 0 0 1px rgba(220, 53, 69, 0.25) !important;
    }
    .stock-hold-page #btnAddRow {
        padding: .25rem .75rem !important;
        font-size: .8rem !important;
        font-weight: 600 !important;
    }
    .stock-hold-page input[type="date"],
    .stock-hold-page input[type="time"] {
        min-width: 125px;
    }
    .stock-hold-page .header-datetime-box {
        background: #f1f5f9;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 3px 10px;
        font-size: 11px;
        font-weight: 600;
        color: #334155;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .stock-hold-page .header-datetime-box i {
        color: #64748b;
    }
    .posted-watermark {
        position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-30deg);
        font-size: 6rem; color: rgba(220, 53, 69, 0.05); font-weight: 900; text-transform: uppercase;
        pointer-events: none; z-index: 1000; border: 8px solid rgba(220, 53, 69, 0.05); padding: 10px 40px; border-radius: 15px;
    }
    .form-locked { background-color: #f8f9fa !important; position: relative; }
    .form-locked input, .form-locked .select2-container--default .select2-selection--single,
    .form-locked .select2-container, .form-locked select, .form-locked textarea {
        pointer-events: none !important; opacity: 0.85 !important; background-color: #f1f3f5 !important; cursor: not-allowed !important;
    }
    .form-locked .removeRow, .form-locked #btnAddRow, .form-locked #saveDraftBtn { display: none !important; }
    .form-locked #editInvoiceBtn, .form-locked #newInvoiceBtn, .form-locked #realPrintBtn,
    .form-locked #postBtn, .form-locked #exitBtn, .form-locked #deleteBtn, .form-locked #unpostBtn {
        pointer-events: auto !important; opacity: 1 !important;
    }
    .form-locked.view-mode #saveDraftBtn,
    .form-locked.view-mode #editInvoiceBtn,
    .form-locked.view-mode #postBtn,
    .form-locked.view-mode #deleteBtn,
    .form-locked.view-mode #unpostBtn {
        display: none !important;
    }
    .form-locked.view-mode #realPrintBtn,
    .form-locked.view-mode #exitBtn,
    .form-locked.view-mode #newInvoiceBtn {
        pointer-events: auto !important;
        opacity: 1 !important;
        display: inline-block !important;
    }
    .ajax-valid-error { color: #dc3545; font-size: 9px; font-weight: 700; margin-top: 1px; display: block; }
