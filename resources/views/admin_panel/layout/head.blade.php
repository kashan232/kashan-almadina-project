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
 </style>
 <meta charset="UTF-8">
 <meta http-equiv="x-ua-compatible" content="ie=edge">
 <meta name="viewport" content="width=device-width, initial-scale=1">
 <meta name="description" content="Zare Bootstrap 4 Admin Template">
 <title>Home</title>

 <link rel="shortcut icon" type="image/x-icon" href="{{ asset('assets/images/favicon.png') }}">
 <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
 <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">

 {{-- Font Awesome --}}
 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
     integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg=="
     crossorigin="anonymous" referrerpolicy="no-referrer" />

 {{-- ✅ DataTables CSS --}}
 <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
 <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">

 {{-- jQuery + Bootstrap --}}
 <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
 <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>