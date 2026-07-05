    .stock-hold-page.container-fluid { padding: .15rem .3rem !important; }
    .stock-hold-page .main-content-inner { padding: 0 !important; background: #f4f7fa; min-height: 100vh; }
    .stock-hold-page .form-card { border-radius: 6px; border: none; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,.075); margin-bottom: .2rem !important; }
    .stock-hold-page .card-body { padding: .35rem .45rem !important; }
    .stock-hold-page .card-body.p-2 { padding: .35rem .45rem !important; }
    .stock-hold-page .card-footer { padding: .35rem .45rem !important; }
    .stock-hold-page .mb-2, .stock-hold-page .mb-3, .stock-hold-page .mb-4 { margin-bottom: .2rem !important; }
    .stock-hold-page .row.g-2 { --bs-gutter-x: .3rem; --bs-gutter-y: .12rem; }
    .stock-hold-page .form-control-sm, .stock-hold-page .form-select-sm,
    .stock-hold-page .form-control, .stock-hold-page .form-select {
        font-size: .76rem !important; height: 24px !important; min-height: 24px !important;
        padding: .05rem .35rem !important; border-radius: 4px !important; border: 1px solid #dee2e6 !important;
    }
    .stock-hold-page .form-label { font-size: .7rem !important; font-weight: 700 !important; color: #64748b !important; text-transform: uppercase; margin-bottom: 0 !important; }
    .stock-hold-page .select2-container--default .select2-selection--single {
        height: 24px !important; font-size: .76rem !important; border-radius: 4px !important; border: 1px solid #dee2e6 !important;
    }
    .stock-hold-page .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 22px !important; padding-left: 5px !important; }
    .stock-hold-page .select2-container--default .select2-selection--single .select2-selection__arrow { height: 22px !important; }
    .stock-hold-page #voucherTable { font-size: .76rem !important; }
    .stock-hold-page #voucherTable thead th {
        padding: 1px 4px !important; font-size: .7rem !important; height: 20px !important;
        background: #f8fafc !important; color: #475569 !important; font-weight: 700 !important;
        text-transform: uppercase; border-bottom: 2px solid #e2e8f0 !important;
    }
    .stock-hold-page #voucherTable tbody td { padding: 1px 4px !important; vertical-align: middle !important; border-bottom: 1px solid #f1f5f9 !important; }
    .stock-hold-page #voucherTable .form-control, .stock-hold-page #voucherTable .form-select { height: 22px !important; min-height: 22px !important; padding: 0 3px !important; font-size: .72rem !important; }
    .stock-hold-page #voucherTable tfoot td { padding: .15rem .3rem !important; }
    .stock-hold-page .bottom-bar-btns { gap: .3rem !important; }
    .stock-hold-page .bottom-bar-btns .btn { padding: .2rem .55rem !important; font-size: .76rem !important; }
    .stock-hold-page .header-info-box { background: #fff; border-left: 3px solid {{ $accentColor ?? '#3b82f6' }}; padding: .2rem .45rem !important; border-radius: 4px; }
    .stock-hold-page .header-info-box h6 { font-size: .82rem !important; margin: 0 !important; }
    .stock-hold-page .badge { font-size: 10px !important; padding: .15rem .45rem !important; }
    .stock-hold-page .gap-3 { gap: .3rem !important; }
    .stock-hold-page .alert { padding: .3rem .45rem !important; margin-bottom: .2rem !important; font-size: .78rem !important; }
    .stock-hold-page .ajax-valid-error {
        display: block; color: #dc3545 !important; font-size: 9px !important; font-weight: 700;
        line-height: 1.2; margin-bottom: 1px; white-space: normal;
    }
    .stock-hold-page .is-field-invalid,
    .stock-hold-page .select2-container.is-field-invalid .select2-selection--single {
        border-color: #dc3545 !important;
        box-shadow: 0 0 0 1px rgba(220, 53, 69, 0.25) !important;
    }
    .stock-hold-page .btn-xs { padding: 0 4px; font-size: 9px; line-height: 1.2; }
    .stock-hold-page .btn-mini { padding: 0 4px; font-size: 9px; height: 18px; display: inline-flex; align-items: center; justify-content: center; }
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
