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
            justify-content: space-between;
            align-items: center;
        }
        .page-navigation .nav-item .submenu {
            position: static !important;
            width: 100% !important;
            display: none;
            background: #212b36 !important;
            box-shadow: none !important;
            border: none !important;
        }
        .page-navigation .nav-item.show-submenu .submenu {
            display: block !important;
        }
        .page-navigation .nav-item .submenu .submenu-item {
            padding-left: 20px !important;
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
        }
        .nav-bottom {
            display: none !important;
        }
        .nav-bottom.header-toggled {
            display: block !important;
            height: auto !important;
            z-index: 1000 !important;
            position: relative !important;
        }
    }
</style>
<div class="container-scroller">
      <nav class="rt_nav_header horizontal-layout col-lg-12 col-12 p-0">
          <div class="top_nav flex-grow-1">
              <div class="container d-flex flex-row h-100 align-items-center">
                  <div class="text-center rt_nav_wrapper d-flex align-items-center">
                      <a class="nav_logo rt_logo" href="index.html"><img
                              src="{{ asset('assets/images/WIJDAN-removebg-preview.png') }}" alt="logo" /></a>
                  </div>
                  <div class="nav_wrapper_main d-flex align-items-center justify-content-between flex-grow-1">
                      <ul class="navbar-nav navbar-nav-right mr-0 ml-auto">
                          <li class="nav-item">
                              <span class="nav-link text-white me-3" style="cursor: default;">
                                  <i class="fa-solid fa-user-circle me-1"></i> {{ Auth::user()->name }}
                              </span>
                          </li>
                          <li class="nav-item">
                              <a class="nav-link" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                   <span class="profile_name"><i class="fa-solid fa-sign-out-alt me-1"></i> Logout</span>
                              </a>
                              <div class="dropdown-menu dropdown-menu-right navbar-dropdown pt-2" style="display:none;"
                                  aria-labelledby="profileDropdown">
                                  <span role="separator" class="divider"></span>
                                  <form id="logout-form" method="POST" action="{{ route('logout') }}">
                                      @csrf
                                      <button type="submit" class="dropdown-item">
                                          <i class="ti-power-off text-dark mr-3"></i> Logout
                                      </button>
                                  </form>
                              </div>
                          </li>
                      </ul>

                      <button class="navbar-toggler align-self-center" type="button">
                          <i class="fa-solid fa-bars text-white" style="font-size: 20px;"></i>
                      </button>

                  </div>
              </div>
          </div>
          <div class="nav-bottom">
              <div class="container">
                  <ul class="nav page-navigation">
                      @can('Dashboard')
                      <li class="nav-item">
                          <a href="{{ url('/home') }}" class="nav-link"><i
                                  class="menu_icon feather ft-home"></i><span
                                  class="menu-title">Dashboard</span></a>

                      </li>
                      @endcan

                      {{-- Management Section --}}
                      {{-- Products Section --}}
                      @canany(['Products', 'Category', 'Sub Category', 'Brands', 'Units'])
                      <li class="nav-item">
                          <a href="#" class="nav-link"><i class="menu_icon fas fa-box"></i><span class="menu-title">Products</span><i class="menu-arrow"></i></a>
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
                          <a href="#" class="nav-link"><i class="menu_icon fas fa-shopping-cart"></i><span class="menu-title">Purchase</span><i class="menu-arrow"></i></a>
                          <div class="submenu">
                              <ul class="submenu-item">
                                  @can('Inward Gatepass')
                                  <li><a href="{{route('InwardGatepass.home')}}"><i class="fas fa-file-invoice mr-2"></i> Inward Gatepass</a></li>
                                  <li><a href="{{route('add_inwardgatepass')}}"><i class="fas fa-plus-circle mr-2"></i> Add Gatepass</a></li>
                                  @endcan
                                  @can('Purchase')
                                  <li><a href="{{route('Purchase.home')}}"><i class="fas fa-shopping-bag mr-2"></i> Purchase</a></li>
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
                      @canany(['Warehouse', 'Stock Transfer'])
                      <li class="nav-item">
                          <a href="#" class="nav-link"><i class="menu_icon fas fa-warehouse"></i><span class="menu-title">Inventory</span><i class="menu-arrow"></i></a>
                          <div class="submenu">
                              <ul class="submenu-item">
                                  @can('Warehouse')
                                  <li><a href="{{url('warehouse')}}"><i class="fas fa-building mr-2"></i> Warehouse</a></li>
                                  <li><a href="{{url('warehouse_stocks')}}"><i class="fas fa-boxes mr-2"></i> Warehouse Stock</a></li>
                                  @endcan
                                  @can('Stock Transfer')
                                  <li><a href="{{url('stock_transfers')}}"><i class="fas fa-exchange-alt mr-2"></i> Stock Transfer</a></li>
                                  @endcan
                              </ul>
                          </div>
                      </li>
                      @endcanany

                      {{-- Sales Section --}}
                      @canany(['Sales', 'Sale Return', 'Stock Hold', 'Customer', 'Sales Officer', 'Zone'])
                      <li class="nav-item">
                          <a href="#" class="nav-link"><i class="menu_icon fas fa-receipt"></i><span class="menu-title">Sales</span><i class="menu-arrow"></i></a>
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
                          <a href="#" class="nav-link"><i class="menu_icon fas fa-shield-alt"></i><span class="menu-title">Claims</span><i class="menu-arrow"></i></a>
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
                          <a href="#" class="nav-link"><i class="menu_icon feather ft-clipboard"></i><span
                                  class="menu-title">User Management</span><i class="menu-arrow"></i></a>
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
                                  <li class="nav-item"><a class="nav-link" href="{{ route('user-group.index') }}"><i
                                              class="fa-solid fa-users-rectangle mr-2"></i><span>User Groups</span></a></li>
                              </ul>
                          </div>
                      </li>
                      @endcanany

                      {{-- Vouchers Section --}}
                      @canany(['Chart Of Accounts', 'Narrations', 'Receipts Voucher', 'Payment Voucher', 'Expense Voucher', 'Income Voucher', 'Journal Voucher', 'Adjustment Voucher'])
                      <li class="nav-item">
                          <a href="#" class="nav-link">
                              <i class="menu_icon feather ft-clipboard"></i>
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
                                  <li class="nav-item">
                                      <a class="nav-link" href="{{ route('all-adjustment-vochers') }}">
                                          <i class="fa-solid fa-adjust mr-2"></i>
                                          <span>Adjustment Voucher</span>
                                      </a>
                                  </li>

                              </ul>
                          </div>
                      </li>
                      @endcanany

                      {{-- Tools Section --}}
                      @can('Rollback')
                      <li class="nav-item">
                          <a href="#" class="nav-link">
                              <i class="menu_icon fas fa-tools"></i>
                              <span class="menu-title">Tools</span>
                              <i class="menu-arrow"></i>
                          </a>
                          <div class="submenu">
                              <ul class="submenu-item">
                                  <li class="nav-item">
                                      <a class="nav-link" href="{{ route('rollback.index') }}">
                                          <i class="fas fa-undo-alt mr-2"></i>
                                          <span>Rollback Posting</span>
                                      </a>
                                  </li>
                              </ul>
                          </div>
                      </li>
                      @endcan

                      {{-- Reports Section --}}
                      @can('Reports')
                      <li class="nav-item">
                          <a href="#" class="nav-link">
                              <i class="menu_icon feather ft-bar-chart"></i>
                              <span class="menu-title">Reports</span>
                              <i class="menu-arrow"></i>
                          </a>
                          <div class="submenu">
                              <ul class="submenu-item">
                                  <li class="nav-item">
                                      <a class="nav-link" href="{{ route('reports.dashboard') }}">
                                          <i class="fa-solid fa-dashboard mr-2"></i>
                                          <span>Dashboard</span>
                                      </a>
                                  </li>
                                  <li class="nav-item">
                                      <a class="nav-link" href="{{ route('reports.sales.index') }}">
                                          <i class="fa-solid fa-file-invoice mr-2"></i>
                                          <span>Sales Report</span>
                                      </a>
                                  </li>
                                  <li class="nav-item">
                                      <a class="nav-link" href="{{ route('reports.purchase.index') }}">
                                          <i class="fa-solid fa-cart-shopping mr-2"></i>
                                          <span>Purchase Report</span>
                                      </a>
                                  </li>
                              </ul>
                          </div>
                      </li>
                      @endcan


                  </ul>
              </div>
          </div>
      </nav>
      <script>
          document.addEventListener('DOMContentLoaded', function() {
              const toggler = document.querySelector('.navbar-toggler');
              const navBottom = document.querySelector('.nav-bottom');
              
              if (toggler) {
                  toggler.onclick = function() {
                      if (navBottom) {
                          navBottom.classList.toggle('header-toggled');
                      }
                  };
              }

              const navItems = document.querySelectorAll('.page-navigation .nav-item');
              navItems.forEach(item => {
                  const link = item.querySelector('.nav-link');
                  const submenu = item.querySelector('.submenu');
                  
                  if (submenu && link) {
                      link.onclick = function(e) {
                          if (window.innerWidth <= 991) {
                              e.preventDefault();
                              const wasActive = item.classList.contains('show-submenu');
                              
                              // Close all submenus
                              navItems.forEach(i => i.classList.remove('show-submenu'));
                              
                              // Toggle current if it wasn't active
                              if (!wasActive) {
                                  item.classList.add('show-submenu');
                              }
                          }
                      };
                  }
              });
          });
      </script>