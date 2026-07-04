  <style>
    /* Navbar Responsiveness Fix */
    @media (max-width: 991px) {
        .nav-bottom .container {
            padding: 0;
            width: 100%;
        }
        .page-navigation {
            flex-direction: column !important;
            border: none !important;
            width: 100%;
        }
        .page-navigation .nav-item {
            width: 100%;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .page-navigation .nav-item .nav-link {
            padding: 15px 20px !important;
            display: flex;
            justify-content: flex-start;
            align-items: center;
            color: #ffffff !important;
        }
        .page-navigation .nav-item .nav-link .menu_icon {
            width: 25px;
            text-align: center;
        }
        .page-navigation .nav-item .nav-link .menu-title {
            flex-grow: 1;
            margin-left: 10px;
        }
        .page-navigation .nav-item .nav-link .menu-arrow {
            margin-left: auto;
        }
        .page-navigation .nav-item .nav-link .menu_icon, 
        .page-navigation .nav-item .nav-link .menu-title,
        .page-navigation .nav-item .nav-link .menu-arrow {
            color: #ffffff !important;
        }
        .page-navigation .nav-item .submenu {
            position: static !important;
            width: 100% !important;
            display: none;
            background: #1e2630 !important;
            box-shadow: none !important;
            border: none !important;
        }
        .page-navigation .nav-item.show-submenu .submenu {
            display: block !important;
        }
        .page-navigation .nav-item .submenu.submenu-scroll {
            max-height: 280px;
            overflow-y: auto;
            overflow-x: hidden;
        }
        .page-navigation .nav-item .submenu .submenu-item {
            padding-left: 20px !important;
        }
        .page-navigation .nav-item .submenu .submenu-item li a {
            color: #d1d5db !important;
            padding: 10px 0 !important;
            display: flex !important;
            align-items: center !important;
            flex-direction: row !important;
            text-decoration: none;
        }
        .page-navigation .nav-item .submenu .submenu-item li a i {
            margin-right: 12px !important;
            width: 20px !important;
            text-align: center !important;
            display: inline-block !important;
            color: #d1d5db !important;
            padding: 0 !important;
        }
        .page-navigation .nav-item .submenu .submenu-item li a span {
            display: inline-block !important;
            color: #d1d5db !important;
            padding: 0 !important;
        }
        .page-navigation .nav-item .submenu .submenu-item li a:hover,
        .page-navigation .nav-item .submenu .submenu-item li a:hover span,
        .page-navigation .nav-item .submenu .submenu-item li a:hover i {
            color: #ffffff !important;
        }
        .menu-arrow {
            transform: rotate(0deg);
            transition: transform 0.3s;
        }
        .show-submenu .menu-arrow {
            transform: rotate(90deg);
        }
        
        /* Force Toggler Visibility */
        .navbar-toggler {
            display: block !important;
            border: none;
            background: transparent;
            padding: 10px;
            cursor: pointer;
            z-index: 1001;
            opacity: 1 !important;
        }
        .nav-bottom {
            display: none !important;
            opacity: 1 !important;
            background-color: #212b36 !important; /* solid background to prevent transparency */
            position: absolute !important;
            top: 100%;
            left: 0;
            width: 100%;
            z-index: 9999 !important;
            box-shadow: 0 10px 20px rgba(0,0,0,0.5);
        }
        .nav-bottom.header-toggled {
            display: block !important;
            height: auto !important;
        }
    }
    
    /* Desktop Navbar Merged Styles */
    @media (min-width: 992px) {
        .rt_nav_header .top_nav {
            padding: 5px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .rt_nav_header .nav-bottom {
            display: flex !important;
            align-items: center;
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
            margin-left: 10px;
            margin-right: 10px;
        }
        .page-navigation {
            display: flex;
            flex-direction: row;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            background: transparent !important;
            border: none !important;
            margin: 0 !important;
            gap: 2px;
        }
        .page-navigation .nav-item {
            border: none !important;
            position: relative !important;
            padding-bottom: 8px;
            margin-bottom: -8px;
        }
        .page-navigation .nav-item .nav-link {
            color: #ffffff !important;
            padding: 5px 8px !important;
            font-size: 13px !important;
            font-weight: 600;
            white-space: nowrap;
            transition: all 0.3s ease;
        }
        .page-navigation .nav-item .nav-link:hover,
        .page-navigation .nav-item:hover .nav-link {
            color: #ffffff !important;
            background: rgba(255,255,255,0.25);
            border-radius: 6px;
        }

        /* Dashboard specific override to fix blue color */
        .page-navigation .nav-item a[href*="home"] {
            color: #ffffff !important;
        }

          /* Style dropdown menus for desktop */
        .rt_nav_header.horizontal-layout .nav-bottom .page-navigation > .nav-item:not(.mega-menu) .submenu,
        .page-navigation .nav-item .submenu {
            background: #ffffff !important;
            border-radius: 6px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1) !important;
            border: 1px solid rgba(0,0,0,0.05);
            margin-top: 0 !important;
            padding: 5px 0 !important;
            position: absolute !important;
            top: 100% !important;
            left: 0;
            min-width: 220px;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transform: translateY(0);
            animation: none !important;
            transition: opacity 0.2s ease, visibility 0.2s ease;
        }
        /* Show on hover */
        .rt_nav_header.horizontal-layout .nav-bottom .page-navigation > .nav-item:not(.mega-menu):hover .submenu,
        .page-navigation .nav-item:hover .submenu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        .page-navigation .nav-item .submenu .submenu-item {
            margin: 0;
            padding: 0;
            list-style: none;
        }
        
        .rt_nav_header.horizontal-layout .nav-bottom .page-navigation>.nav-item:not(.mega-menu) .submenu ul {
            width: auto !important;
            padding: 0px !important;
        }
        
        .page-navigation .nav-item .submenu .submenu-item li a {
            color: #333333 !important;
            font-size: 13px !important;
            padding: 6px 15px !important;
            font-weight: 500;
        }
        .page-navigation .nav-item .submenu .submenu-item li a:hover {
            background: #f4f6f9 !important;
            color: #0d6efd !important;
            padding-left: 20px !important; /* tiny hover effect */
            transition: all 0.2s;
        }
        .page-navigation .nav-item .submenu .submenu-item li a i {
            color: #6c757d !important;
        }
        .page-navigation .nav-item .submenu .submenu-item li a:hover i {
            color: #0d6efd !important;
        }

        /* Scrollable dropdown for long menus (Reports, etc.) */
        .page-navigation .nav-item .submenu.submenu-scroll {
            max-height: min(420px, calc(100vh - 120px));
            overflow-y: auto;
            overflow-x: hidden;
            overscroll-behavior: contain;
            -webkit-overflow-scrolling: touch;
        }
        .page-navigation .nav-item .submenu.submenu-scroll::-webkit-scrollbar {
            width: 6px;
        }
        .page-navigation .nav-item .submenu.submenu-scroll::-webkit-scrollbar-track {
            background: #f1f3f5;
            border-radius: 3px;
        }
        .page-navigation .nav-item .submenu.submenu-scroll::-webkit-scrollbar-thumb {
            background: #adb5bd;
            border-radius: 3px;
        }
        .page-navigation .nav-item .submenu.submenu-scroll::-webkit-scrollbar-thumb:hover {
            background: #868e96;
        }

        /* Nested submenu inside Reports dropdown (inline accordion) */
        .submenu-item > li.has-nested-submenu {
            position: relative;
            list-style: none;
        }
        .submenu-item > li.has-nested-submenu > .nav-link.nested-toggle {
            display: flex !important;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            user-select: none;
        }
        .submenu-item > li.has-nested-submenu > .nav-link.nested-toggle .nested-arrow {
            font-size: 10px;
            margin-left: 8px;
            flex-shrink: 0;
            transition: transform 0.2s ease;
            color: #6c757d !important;
        }
        .submenu-item > li.has-nested-submenu.show-nested > .nav-link.nested-toggle {
            background: #f4f6f9 !important;
            color: #0d6efd !important;
        }
        .submenu-item > li.has-nested-submenu.show-nested > .nav-link.nested-toggle .nested-arrow {
            color: #0d6efd !important;
            transform: rotate(90deg);
        }
        .submenu-item .nested-submenu {
            display: none;
            list-style: none;
            margin: 0;
            padding: 0;
            background: #f8f9fb;
            border-left: 3px solid #0d6efd;
        }
        .submenu-item > li.has-nested-submenu.show-nested > .nested-submenu {
            display: block;
        }
        .submenu-item .nested-submenu li a {
            display: flex !important;
            align-items: center;
            padding: 6px 15px 6px 28px !important;
            color: #333 !important;
            font-size: 12px !important;
            text-decoration: none;
        }
        .submenu-item .nested-submenu li a:hover {
            background: #eef2f7 !important;
            color: #0d6efd !important;
        }
        .submenu-item .nested-submenu li a i {
            margin-right: 8px !important;
            color: #6c757d !important;
            width: 16px;
            text-align: center;
        }
        @media (max-width: 991px) {
            .submenu-item > li.has-nested-submenu > .nav-link.nested-toggle {
                color: #d1d5db !important;
                padding: 10px 0 !important;
            }
            .submenu-item > li.has-nested-submenu.show-nested > .nav-link.nested-toggle {
                color: #ffffff !important;
            }
            .submenu-item .nested-submenu {
                background: rgba(255,255,255,0.04);
                border-left-color: rgba(255,255,255,0.35);
            }
            .submenu-item .nested-submenu li a {
                color: #d1d5db !important;
                padding: 8px 0 8px 24px !important;
            }
            .submenu-item .nested-submenu li a:hover,
            .submenu-item .nested-submenu li a:hover span,
            .submenu-item .nested-submenu li a:hover i {
                color: #ffffff !important;
            }
        }
        
        .menu-arrow {
            margin-left: 4px;
            font-size: 9px;
            display: inline-block;
            vertical-align: middle;
            transition: transform 0.3s ease;
        }
        .page-navigation .nav-item:hover .menu-arrow {
            transform: rotate(180deg);
        }
        
        .menu-arrow::before {
            content: none !important;
            display: none !important;
        }
        .menu-arrow::after {
            content: '\25BC' !important; /* Native downward caret */
            font-family: sans-serif !important;
            opacity: 0.9;
            display: inline-block !important;
        }
        /* Hide any default arrows from the nav-link itself */
        .page-navigation .nav-item .nav-link::after,
        .page-navigation .nav-item .nav-link::before {
            content: none !important;
            display: none !important;
        }
        
        .navbar-toggler {
            display: none !important;
        }
    }
    
    /* Dashboard specific override to fix blue color */
    .page-navigation .nav-item a[href*="home"] {
        color: #ffffff !important;
    }
    
    /* Global header background (Blue) + full bar sticky/fixed on scroll */
    .rt_nav_header.horizontal-layout {
        position: sticky !important;
        top: 0 !important;
        left: 0;
        right: 0;
        z-index: 1030 !important;
        background: linear-gradient(90deg, #0a3d62 0%, #3c6382 100%) !important;
        border-bottom: 2px solid #072a44;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
        overflow: visible !important;
    }

    /* Keep menu inside header — theme was fixing only .nav-bottom and losing blue bar */
    .rt_nav_header.horizontal-layout .nav-bottom,
    .rt_nav_header.horizontal-layout.fixed-on-scroll .nav-bottom {
        position: static !important;
        top: auto !important;
        left: auto !important;
        right: auto !important;
        width: auto !important;
        background: transparent !important;
        box-shadow: none !important;
        border: none !important;
    }

    @media (max-width: 991px) {
        .rt_nav_header.horizontal-layout {
            position: fixed !important;
        }
    }
</style>
<div class="container-scroller">
      <nav class="rt_nav_header horizontal-layout col-lg-12 col-12 p-0" style="opacity: 1 !important; overflow: visible !important;">
          <div class="top_nav flex-grow-1" style="overflow: visible !important;">
              <div class="container-fluid px-2 d-flex flex-row h-100 align-items-center justify-content-between">
                  <!-- BRAND NAME -->
                  <div class="d-flex align-items-center me-2">
                      <a class="nav_logo rt_logo text-decoration-none d-flex align-items-center" href="{{ url('/home') }}">
                          <x-amt-logo height="42px" style="background:#fff;border-radius:4px;padding:2px 6px;" />
                      </a>
                  </div>

                  <!-- MAIN NAVIGATION -->
                  <div class="nav-bottom flex-grow-1">
                      <div class="container-fluid p-0">
                          <ul class="nav page-navigation d-flex justify-content-center">
                      @can('Dashboard')
                      <li class="nav-item">
                          <a href="{{ url('/home') }}" class="nav-link"><span
                                  class="menu-title text-white">Dashboard</span></a>

                      </li>
                      @endcan

                      {{-- Management Section --}}
                      {{-- Products Section --}}
                      @canany(['Products', 'Category', 'Sub Category', 'Brands', 'Units'])
                      <li class="nav-item">
                          <a href="#" class="nav-link"><span class="menu-title">Products</span><i class="menu-arrow"></i></a>
                          <div class="submenu">
                              <ul class="submenu-item">
                                  @can('Products')
                                  <li><a href="{{url('products')}}"><i class="fas fa-box mr-2"></i> Products</a></li>
                                  @endcan
                                  @can('Category')
                                  <li><a href="{{route('Category.home')}}"><i class="fas fa-list mr-2"></i> Category</a></li>
                                  @endcan
                                  @can('Sub Category')
                                  <li><a href="{{route('subcategory.home')}}"><i class="fas fa-th-list mr-2"></i> Sub Category</a></li>
                                  @endcan
                                  @can('Brands')
                                  <li><a href="{{route('Brand.home')}}"><i class="fas fa-trademark mr-2"></i> Brands</a></li>
                                  @endcan
                                  @can('Units')
                                  <li><a href="{{route('Unit.home')}}"><i class="fas fa-balance-scale mr-2"></i> Units</a></li>
                                  @endcan
                              </ul>
                          </div>
                      </li>
                      @endcanany

                      {{-- Purchase Section --}}
                      @canany(['Inward Gatepass', 'Purchase', 'Stock Wastage', 'Vendor'])
                      <li class="nav-item">
                          <a href="#" class="nav-link"><span class="menu-title">Purchase</span><i class="menu-arrow"></i></a>
                          <div class="submenu">
                              <ul class="submenu-item">
                                  @can('Inward Gatepass')
                                  <li><a href="{{route('InwardGatepass.home')}}"><i class="fas fa-file-invoice mr-2"></i> Inward Gatepass</a></li>
                                  <li><a href="{{route('add_inwardgatepass')}}"><i class="fas fa-plus-circle mr-2"></i> Add Gatepass</a></li>
                                  @endcan
                                  @can('Purchase')
                                  <li><a href="{{route('Purchase.home')}}"><i class="fas fa-shopping-bag mr-2"></i> Purchase</a></li>
                                  @endcan
                                  @can('Purchase Return')
                                  <li><a href="{{route('purchase.return.home')}}"><i class="fas fa-undo mr-2"></i> Purchase Return</a></li>
                                  @endcan
                                  @can('Stock Wastage')
                                  <li><a href="{{route('stock-wastage.index')}}"><i class="fas fa-trash mr-2"></i> Stock Wastage</a></li>
                                  @endcan
                                  @can('Vendor')
                                  <li><a href="{{url('vendor')}}"><i class="fas fa-truck mr-2"></i> Vendor</a></li>
                                  @endcan
                              </ul>
                          </div>
                      </li>
                      @endcanany

                      {{-- Inventory Section --}}
                      @canany(['Warehouse', 'Warehouse Stock', 'Stock Transfer'])
                      <li class="nav-item">
                          <a href="#" class="nav-link"><span class="menu-title">Inventory</span><i class="menu-arrow"></i></a>
                          <div class="submenu">
                              <ul class="submenu-item">
                                  @can('Warehouse')
                                  <li><a href="{{url('warehouse')}}"><i class="fas fa-building mr-2"></i> Warehouse</a></li>
                                  @endcan
                                  @can('Warehouse Stock')
                                  <li><a href="{{url('warehouse_stocks')}}"><i class="fas fa-boxes mr-2"></i> Warehouse Stock</a></li>
                                  @endcan
                                  @can('Stock Transfer')
                                  <li><a href="{{url('stock_transfers')}}"><i class="fas fa-exchange-alt mr-2"></i> Stock Transfer</a></li>
                                  <li><a href="{{route('stock_transfers.pending')}}"><i class="fas fa-clock mr-2"></i> Pending Transfer Requests</a></li>
                                  @endcan
                              </ul>
                          </div>
                      </li>
                      @endcanany

                      {{-- Sales Section --}}
                      @canany(['Sales', 'Sale Return', 'Stock Hold', 'Customer', 'Sales Officer', 'Zone'])
                      <li class="nav-item">
                          <a href="#" class="nav-link"><span class="menu-title">Sales</span><i class="menu-arrow"></i></a>
                          <div class="submenu">
                              <ul class="submenu-item">
                                  @can('Sales')
                                  <li><a href="{{url('sale')}}"><i class="fas fa-cash-register mr-2"></i> Sales</a></li>
                                  @endcan
                                  @can('Sale Return')
                                  <li><a href="{{route('sale.return.home')}}"><i class="fas fa-undo mr-2"></i> Sale Return</a></li>
                                  @endcan
                                  @can('Stock Hold')
                                  <li><a href="{{ route('stock-hold-list') }}"><i class="fas fa-pause mr-2"></i> Stock Hold</a></li>
                                  @endcan
                                  @can('Stock Release')
                                  <li><a href="{{ route('stock-relase-list') }}"><i class="fas fa-play mr-2"></i> Stock Release</a></li>
                                  @endcan
                                  @can('Customer')
                                  <li><a href="{{url('customers')}}"><i class="fas fa-user-friends mr-2"></i> Customer</a></li>
                                  @endcan
                                  @can('Sales Officer')
                                  <li><a href="{{url('sales-officers')}}"><i class="fas fa-user-tie mr-2"></i> Sales Officer</a></li>
                                  @endcan
                                  @can('Zone')
                                  <li><a href="{{url('zone')}}"><i class="fas fa-map-marked-alt mr-2"></i> Zone</a></li>
                                  @endcan
                              </ul>
                          </div>
                      </li>
                      @endcanany

                      {{-- Claims Section --}}
                      @canany(['Customer Claim', 'Claim Acceptance', 'Claim Receipt'])
                      <li class="nav-item">
                          <a href="#" class="nav-link"><span class="menu-title">Claims</span><i class="menu-arrow"></i></a>
                          <div class="submenu">
                              <ul class="submenu-item">
                                  @can('Customer Claim')
                                  <li><a href="{{ route('customer-claims.index') }}"><i class="fas fa-shield-alt mr-2"></i> Customer Claim</a></li>
                                  @endcan
                                  @can('Claim Acceptance')
                                  <li><a href="{{ route('claim-acceptance.index') }}"><i class="fas fa-check-double mr-2"></i> Claim Acceptance</a></li>
                                  @endcan
                                  @can('Claim Receipt')
                                  <li><a href="{{ route('claim-item-receipt.index') }}"><i class="fas fa-file-invoice-dollar mr-2"></i> Claim Receipt/Credits</a></li>
                                  @endcan
                              </ul>
                          </div>
                      </li>
                      @endcanany

                      {{-- User Management Section --}}
                      @canany(['Users', 'Roles', 'Permissions', 'Branches'])
                      <li class="nav-item">
                          <a href="#" class="nav-link"><span
                                  class="menu-title">User</span><i class="menu-arrow"></i></a>
                          <div class="submenu">
                              <ul class="submenu-item">
                                  @can('Users')
                                  <li class="nav-item"><a class="nav-link" href="{{ route('users.index') }}"><i
                                              class="fa-solid fa-users mr-2"></i><span>Users</span></a></li>
                                  @endcan
                                  @can('Roles')
                                  <li class="nav-item"><a class="nav-link" href="{{ route('roles.index') }}"><i
                                              class="fa-solid fa-user-lock mr-2"></i><span>Roles</span></a></li>
                                  @endcan
                                  @can('Permissions')
                                  <li class="nav-item"><a class="nav-link" href="{{ route('permissions.index') }}"><i
                                              class="fa-solid fa-user-lock mr-2"></i><span>Permissions</span></a></li>
                                  @endcan
                                  @can('Branches')
                                  <li class="nav-item"><a class="nav-link" href="{{ route('branch.index') }}"><i
                                              class="fa-solid fa-code-branch mr-2"></i><span>Branches</span></a></li>
                                  @endcan
                                  @can('User Groups')
                                  <li class="nav-item"><a class="nav-link" href="{{ route('user-group.index') }}"><i
                                              class="fa-solid fa-users-rectangle mr-2"></i><span>User Groups</span></a></li>
                                  @endcan
                              </ul>
                          </div>
                      </li>
                      @endcanany

                      {{-- Vouchers Section --}}
                      @canany(['Chart Of Accounts', 'Narrations', 'Receipts Voucher', 'Payment Voucher', 'Expense Voucher', 'Income Voucher', 'Journal Voucher', 'Adjustment Voucher'])
                      <li class="nav-item">
                          <a href="#" class="nav-link">
                              <span class="menu-title">Vouchers</span>
                              <i class="menu-arrow"></i>
                          </a>
                          <div class="submenu">
                              <ul class="submenu-item">
                                  @can('Chart Of Accounts')
                                  <li class="nav-item">
                                      <a class="nav-link" href="{{ route('view_all') }}">
                                          <i class="fa-solid fa-money-bill-wave mr-2"></i>
                                          <span>Char Of Accounts </span>
                                      </a>
                                  </li>
                                  @endcan
                                  @can('Narrations')
                                  <li class="nav-item">
                                      <a class="nav-link" href="{{ route('coa.narration') }}">
                                          <i class="fa-solid fa-money-bill-wave mr-2"></i>
                                          <span>Narrations</span>
                                      </a>
                                  </li>
                                  @endcan
                                  @can('Receipts Voucher')
                                  <li class="nav-item">
                                      <a class="nav-link" href="{{ route('all-recepit-vochers') }}">
                                          <i class="fa-solid fa-wallet mr-2"></i>
                                          <span>Receipts Voucher</span>
                                      </a>
                                  </li>
                                  @endcan
                                  @can('Payment Voucher')
                                  <li class="nav-item">
                                      <a class="nav-link" href="{{ route('all-Payment-vochers') }}">
                                          <i class="fa-solid fa-wallet mr-2"></i>
                                          <span>Payment Voucher</span>
                                      </a>
                                  </li>
                                  @endcan
                                  @can('Expense Voucher')
                                  <li class="nav-item">
                                      <a class="nav-link" href="{{ route('all-expense-vochers') }}">
                                          <i class="fa-solid fa-money-bill-wave mr-2"></i>
                                          <span>Expense Voucher</span>
                                      </a>
                                  </li>
                                  @endcan
                                  @can('Income Voucher')
                                  <li class="nav-item">
                                      <a class="nav-link" href="{{ route('all-income-vochers') }}">
                                          <i class="fa-solid fa-line-chart mr-2"></i>
                                          <span>Income Voucher</span>
                                      </a>
                                  </li>
                                  @endcan
                                  @can('Journal Voucher')
                                  <li class="nav-item">
                                      <a class="nav-link" href="{{ route('all-journal-vochers') }}">
                                          <i class="fa-solid fa-wallet mr-2"></i>
                                          <span>Journal Voucher</span>
                                      </a>
                                  </li>
                                  @endcan
                                  @can('Adjustment Voucher')
                                  <li class="nav-item">
                                      <a class="nav-link" href="{{ route('all-adjustment-vochers') }}">
                                          <i class="fa-solid fa-adjust mr-2"></i>
                                          <span>Adjustment Voucher</span>
                                      </a>
                                  </li>
                                  @endcan

                              </ul>
                          </div>
                      </li>
                      @endcanany

                      {{-- Tools Section --}}
                      @canany(['Rollback Posting', 'General Ledger'])
                      <li class="nav-item">
                          <a href="#" class="nav-link">
                              <span class="menu-title">Tools</span>
                              <i class="menu-arrow"></i>
                          </a>
                          <div class="submenu">
                              <ul class="submenu-item">
                                  @can('Rollback Posting')
                                  <li class="nav-item">
                                      <a class="nav-link" href="{{ route('rollback.index') }}">
                                          <i class="fas fa-undo-alt mr-2"></i>
                                          <span>Rollback Posting</span>
                                      </a>
                                  </li>
                                  @endcan
                                  @can('General Ledger')
                                  <li class="nav-item">
                                      <a class="nav-link" href="{{ route('general-ledger.index') }}">
                                          <i class="fas fa-book mr-2"></i>
                                          <span>General Ledger</span>
                                      </a>
                                  </li>
                                  @endcan
                              </ul>
                          </div>
                      </li>
                      @endcanany

                      {{-- Reports Section --}}
                      @canany(['Reports Dashboard', 'Sales Report', 'Purchase Report', 'Claim Report', 'Claim Acceptance Report', 'Claim Receipt Report', 'Stock Wastage Report', 'Stock Transfer Report', 'Receipt Voucher Report', 'Payment Voucher Report', 'Expense Voucher Report', 'Income Voucher Report', 'Journal Voucher Report', 'Adjustment Voucher Report'])
                      <li class="nav-item">
                          <a href="#" class="nav-link">
                              <span class="menu-title">Reports</span>
                              <i class="menu-arrow"></i>
                          </a>
                          <div class="submenu submenu-scroll">
                              <ul class="submenu-item">
                                  @can('Reports Dashboard')
                                  <li class="nav-item">
                                      <a class="nav-link" href="{{ route('reports.dashboard') }}">
                                          <i class="fa-solid fa-dashboard mr-2"></i>
                                          <span>Dashboard</span>
                                      </a>
                                  </li>
                                  @endcan
                                  @can('Sales Report')
                                  <li class="nav-item">
                                      <a class="nav-link" href="{{ route('reports.sales.index') }}">
                                          <i class="fa-solid fa-file-invoice mr-2"></i>
                                          <span>Sales Report</span>
                                      </a>
                                  </li>
                                  @endcan
                                  @can('Purchase Report')
                                  <li class="nav-item">
                                      <a class="nav-link" href="{{ route('reports.purchase.index') }}">
                                          <i class="fa-solid fa-cart-shopping mr-2"></i>
                                          <span>Purchase Report</span>
                                      </a>
                                  </li>
                                  @endcan

                                  @canany(['Claim Report', 'Claim Acceptance Report', 'Claim Receipt Report'])
                                  <li class="nav-item has-nested-submenu">
                                      <a href="javascript:void(0)" class="nav-link nested-toggle">
                                          <span><i class="fa-solid fa-handshake mr-2"></i> Claim Reports</span>
                                          <i class="fa-solid fa-chevron-right nested-arrow"></i>
                                      </a>
                                      <ul class="nested-submenu">
                                          @can('Claim Report')
                                          <li>
                                              <a href="{{ route('reports.claim.index') }}">
                                                  <i class="fa-solid fa-handshake"></i>
                                                  <span>Claim Report</span>
                                              </a>
                                          </li>
                                          @endcan
                                          @can('Claim Acceptance Report')
                                          <li>
                                              <a href="{{ route('reports.claim-acceptance.index') }}">
                                                  <i class="fa-solid fa-check-double"></i>
                                                  <span>Claim Acceptance Report</span>
                                              </a>
                                          </li>
                                          @endcan
                                          @can('Claim Receipt Report')
                                          <li>
                                              <a href="{{ route('reports.claim-item-receipt.index') }}">
                                                  <i class="fa-solid fa-file-invoice-dollar"></i>
                                                  <span>Claim Receipt Report</span>
                                              </a>
                                          </li>
                                          @endcan
                                      </ul>
                                  </li>
                                  @endcanany

                                  <li class="nav-item has-nested-submenu">
                                      <a href="javascript:void(0)" class="nav-link nested-toggle">
                                          <span><i class="fa-solid fa-boxes-stacked mr-2"></i> Stock Reports</span>
                                          <i class="fa-solid fa-chevron-right nested-arrow"></i>
                                      </a>
                                      <ul class="nested-submenu">
                                          <li>
                                              <a href="{{ route('reports.stock.index') }}">
                                                  <i class="fa-solid fa-boxes-stacked"></i>
                                                  <span>Stock Report</span>
                                              </a>
                                          </li>
                                          <li>
                                              <a href="{{ route('reports.stock-ledger.index') }}">
                                                  <i class="fa-solid fa-book"></i>
                                                  <span>Item Stock Ledger</span>
                                              </a>
                                          </li>
                                          @can('Stock Wastage Report')
                                          <li>
                                              <a href="{{ route('reports.stock-wastage.index') }}">
                                                  <i class="fa-solid fa-trash"></i>
                                                  <span>Stock Wastage Report</span>
                                              </a>
                                          </li>
                                          @endcan
                                          @can('Stock Transfer Report')
                                          <li>
                                              <a href="{{ route('reports.stock-transfer.index') }}">
                                                  <i class="fa-solid fa-exchange-alt"></i>
                                                  <span>Stock Transfer Report</span>
                                              </a>
                                          </li>
                                          @endcan
                                      </ul>
                                  </li>

                                  @canany(['Receipt Voucher Report', 'Payment Voucher Report', 'Expense Voucher Report', 'Income Voucher Report', 'Journal Voucher Report', 'Adjustment Voucher Report'])
                                  <li class="nav-item has-nested-submenu">
                                      <a href="javascript:void(0)" class="nav-link nested-toggle">
                                          <span><i class="fa-solid fa-receipt mr-2"></i> Voucher Reports</span>
                                          <i class="fa-solid fa-chevron-right nested-arrow"></i>
                                      </a>
                                      <ul class="nested-submenu">
                                          @can('Receipt Voucher Report')
                                          <li>
                                              <a href="{{ route('reports.receipt-voucher.index') }}">
                                                  <i class="fa-solid fa-receipt"></i>
                                                  <span>Receipt Voucher Report</span>
                                              </a>
                                          </li>
                                          @endcan
                                          @can('Payment Voucher Report')
                                          <li>
                                              <a href="{{ route('reports.payment-voucher.index') }}">
                                                  <i class="fa-solid fa-money-bill-transfer"></i>
                                                  <span>Payment Voucher Report</span>
                                              </a>
                                          </li>
                                          @endcan
                                          @can('Expense Voucher Report')
                                          <li>
                                              <a href="{{ route('reports.expense-voucher.index') }}">
                                                  <i class="fa-solid fa-file-invoice"></i>
                                                  <span>Expense Voucher Report</span>
                                              </a>
                                          </li>
                                          @endcan
                                          @can('Income Voucher Report')
                                          <li>
                                              <a href="{{ route('reports.income-voucher.index') }}">
                                                  <i class="fa-solid fa-hand-holding-dollar"></i>
                                                  <span>Income Voucher Report</span>
                                              </a>
                                          </li>
                                          @endcan
                                          @can('Journal Voucher Report')
                                          <li>
                                              <a href="{{ route('reports.journal-voucher.index') }}">
                                                  <i class="fa-solid fa-book"></i>
                                                  <span>Journal Voucher Report</span>
                                              </a>
                                          </li>
                                          @endcan
                                          @can('Adjustment Voucher Report')
                                          <li>
                                              <a href="{{ route('reports.adjustment-voucher.index') }}">
                                                  <i class="fa-solid fa-sliders"></i>
                                                  <span>Adjustment Voucher Report</span>
                                              </a>
                                          </li>
                                          @endcan
                                      </ul>
                                  </li>
                                  @endcanany
                              </ul>
                          </div>
                      </li>
                      @endcanany

                      <li class="nav-item">
                          <a href="{{ route('reports.unpost-entries.index') }}" class="nav-link">
                              <span class="menu-title">Un Post Entries</span>
                          </a>
                      </li>

                  </ul>
                      </div>
                  </div>

                  <!-- PROFILE & MOBILE TOGGLE -->
                  <div class="nav_wrapper_main d-flex align-items-center justify-content-end ms-3">
                      <ul class="navbar-nav navbar-nav-right d-none d-lg-flex flex-row align-items-center m-0">
                          <li class="nav-item me-3">
                              <span class="nav-link text-white p-0" style="cursor: default;">
                                  <i class="fa-solid fa-user-circle me-1"></i> {{ Auth::user()->name ?? 'Admin' }}
                              </span>
                          </li>
                          <li class="nav-item">
                              <a class="nav-link text-white p-0" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                   <span class="profile_name"><i class="fa-solid fa-sign-out-alt me-1"></i> Logout</span>
                              </a>
                              <form id="logout-form" method="POST" action="{{ route('logout') }}" style="display:none;">@csrf</form>
                          </li>
                      </ul>
                      
                      <button class="navbar-toggler align-self-center d-lg-none ms-3" type="button" onclick="var nb = document.querySelector('.nav-bottom'); if(nb.style.display==='block'){nb.style.setProperty('display', 'none', 'important');}else{nb.style.setProperty('display', 'block', 'important');} event.stopPropagation();">
                          <i class="fa-solid fa-bars text-white" style="font-size: 20px;"></i>
                      </button>
                  </div>

              </div>
          </div>
      </nav>
      <script>
          document.addEventListener('DOMContentLoaded', function() {


              const navItems = document.querySelectorAll('.page-navigation .nav-item');
              navItems.forEach(item => {
                  const link = item.querySelector('.nav-link');
                  const submenu = item.querySelector('.submenu');
                  
                  if (submenu && link) {
                      // Remove any existing click handlers
                      link.onclick = null;
                      
                      // Add new click handler
                      link.addEventListener('click', function(e) {
                          if (window.innerWidth <= 991) {
                              e.preventDefault();
                              e.stopPropagation();
                              
                              const isCurrentlyOpen = submenu.style.display === 'block';
                              
                              // Close all submenus first
                              document.querySelectorAll('.page-navigation .submenu').forEach(sub => {
                                  sub.style.setProperty('display', 'none', 'important');
                              });
                              document.querySelectorAll('.page-navigation .nav-item').forEach(i => {
                                  i.classList.remove('show-submenu');
                              });
                              
                              // Open the clicked one if it wasn't already open
                              if (!isCurrentlyOpen) {
                                  submenu.style.setProperty('display', 'block', 'important');
                                  item.classList.add('show-submenu');
                              }
                          }
                      }, true); // Use capture phase to intercept before template scripts
                  }
              });

              // Nested report submenus (Claim, Stock, Voucher) — click to expand inline
              document.querySelectorAll('.submenu.submenu-scroll').forEach(submenuEl => {
                  submenuEl.addEventListener('click', function(e) {
                      const toggle = e.target.closest('.has-nested-submenu > .nested-toggle');
                      if (!toggle) {
                          return;
                      }
                      e.preventDefault();
                      e.stopPropagation();

                      const parent = toggle.closest('.has-nested-submenu');
                      const willOpen = !parent.classList.contains('show-nested');

                      document.querySelectorAll('.has-nested-submenu').forEach(item => {
                          item.classList.remove('show-nested');
                      });

                      if (willOpen) {
                          parent.classList.add('show-nested');
                      }
                  });
              });

              document.addEventListener('click', function(e) {
                  if (e.target.closest('.has-nested-submenu') || e.target.closest('.submenu.submenu-scroll')) {
                      return;
                  }
                  document.querySelectorAll('.has-nested-submenu').forEach(item => {
                      item.classList.remove('show-nested');
                  });
              });
          });
      </script>