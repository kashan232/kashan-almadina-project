 <style>
     /* ERP Mega Menu & Normal Submenu Compact Styling */
     .nav-item .submenu,
     .mega-menu .submenu {
         background: #fff;
         padding: 12px;
         /* compact padding */
         border-radius: 6px;
         box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
     }

     .mega-menu .category-heading {
         font-size: 13px;
         font-weight: 600;
         color: #34495e;
         margin-bottom: 8px;
         padding-bottom: 4px;
         border-bottom: 1px solid #eaeaea;
     }

     .nav-item .submenu-item li,
     .mega-menu .submenu-item li {
         margin-bottom: 4px;
         /* less spacing */
     }

     .nav-item .submenu-item li a,
     .mega-menu .submenu-item li a {
         display: flex;
         align-items: center;
         font-size: 15px;
         /* smaller font */
         color: #555;
         padding: 4px 8px;
         /* compact padding */
         border-radius: 4px;
         transition: all 0.2s ease;
     }

     .nav-item .submenu-item li a i,
     .mega-menu .submenu-item li a i {
         font-size: 14px;
         margin-right: 6px;
         color: #2980b9;
         min-width: 18px;
         text-align: center;
     }

     .nav-item .submenu-item li a:hover,
     .mega-menu .submenu-item li a:hover {
         background: #f1f7fd;
         color: #2980b9;
         font-weight: 500;
     }

     /* Remove arrows from number input */

     /* Chrome, Safari, Edge */
     input[type=number]::-webkit-inner-spin-button,
     input[type=number]::-webkit-outer-spin-button {
         -webkit-appearance: none;
         margin: 0;
     }

     /* Firefox */
     input[type=number] {
         -moz-appearance: textfield;
     }

     /* 🎨 Soft Yellowish Theme for Entry Forms & Form Cards */
     body,
     .page-container,
     .main-content,
     .main-content-inner,
     .content-wrapper,
     .body-wrapper,
     .bodywrapper__inner,
     .main-container,
     .stock-hold-page,
     .purchase-page,
     .purchase-page-inner,
     .card,
     .card-body,
     .card-header,
     .card-footer,
     .form-card,
     form,
     .main-content .bg-white,
     .main-content .bg-light,
     .main-content .bg-light-subtle,
     .main-container.bg-white,
     .card-body.bg-white,
     .card.bg-white {
         background-color: #fffde7 !important;
     }

     /* ⚪ Clean White Background for DataTables & Listing Tables */
     .table,
     .table tbody,
     .table tbody tr,
     .table td,
     .dataTable,
     .dataTable tbody tr,
     .dataTable tbody td,
     .table-responsive .table {
         background-color: #ffffff !important;
     }

     /* 🖤 Dark Styled Header for All Tables */
     .table thead,
     .table thead tr,
     .table thead th,
     .table-sm thead th,
     .dataTable thead th,
     .main-content table.table thead th,
     .main-content table.table-sm thead th,
     .main-content table.dataTable thead th,
     #voucherTable thead th {
         background-color: #1e293b !important;
         color: #ffffff !important;
         font-weight: 600 !important;
         border-bottom: 2px solid #0f172a !important;
     }

     .table thead th a,
     .table thead th i {
         color: #ffffff !important;
     }

     /* ⚪ Pure White Form Inputs & Dropdowns ONLY */
     .form-control,
     .form-select,
     input[type="text"],
     input[type="number"],
     input[type="date"],
     input[type="time"],
     input[type="email"],
     input[type="password"],
     select,
     textarea,
     .select2-container--default .select2-selection--single,
     .select2-container--default .select2-selection--multiple,
     .select2-dropdown,
     .select2-results__option {
         background-color: #ffffff !important;
         border: 1px solid #94a3b8 !important;
         color: #0f172a !important;
     }

     .form-control:focus,
     .form-select:focus,
     .select2-container--default.select2-container--focus .select2-selection--single {
         background-color: #ffffff !important;
         border-color: #3b82f6 !important;
         box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.2) !important;
     }

     .form-control[readonly],
     .form-control:disabled,
     .form-select:disabled,
     input[readonly],
     input:disabled,
     select:disabled {
         background-color: #f1f5f9 !important;
         color: #475569 !important;
         border-color: #cbd5e1 !important;
     }

     /* Preserve Buttons, Badges, Modals, Toasts, Alerts, and Print Views */
     .btn,
     .badge,
     .alert,
     .modal-content,
     .modal-header,
     .modal-body,
     .modal-footer {
         background-color: initial;
     }

     @media print {
         body, .page-container, .main-content, .card, .table, .table td, .table th {
             background-color: #ffffff !important;
         }
     }
 </style>
@if(request()->is('reports*') || request()->is('general-ledger*'))
@include('admin_panel.reports.partials.report_global_zoom')
@endif
 <meta charset="UTF-8">
 <meta http-equiv="x-ua-compatible" content="ie=edge">
 <meta name="viewport" content="width=device-width, initial-scale=1">
 <meta name="description" content="Zare Bootstrap 4 Admin Template">
 <title>{{ ucwords(str_replace(['-', '_'], ' ', (in_array(request()->segment(1), ['add', 'edit', 'create', 'view']) && request()->segment(2)) ? request()->segment(1) . ' ' . request()->segment(2) : (request()->segment(1) ?: 'Home'))) }} | Al-Madina</title>

 <link rel="shortcut icon" type="image/png" href="{{ asset('amt-logo.png') }}">
 <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
 <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
 <link rel="stylesheet" href="{{ asset('assets/css/compact-listing.css') }}">
 <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

 {{-- Font Awesome --}}
 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
     integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg=="
     crossorigin="anonymous" referrerpolicy="no-referrer" />

 {{-- ✅ DataTables CSS --}}
 <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
 <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
 {{-- ✅ Select2 CSS --}}
 <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
