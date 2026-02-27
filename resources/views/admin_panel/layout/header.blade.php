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
                             <a class="nav-link" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                  <span class="profile_name">Logout <i
                                         class="feather ft-chevron-down" style="display:none;"></i></span>
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

                     <button class="navbar-toggler align-self-center" type="button" data-toggle="minimize">
                         <span class="feather ft-menu text-white"></span>
                     </button>

                 </div>
             </div>
         </div>
         <div class="nav-bottom">
             <div class="container">
                 <ul class="nav page-navigation">
                     <li class="nav-item">
                         <a href="{{ url('/home') }}" class="nav-link"><i
                                 class="menu_icon feather ft-home"></i><span
                                 class="menu-title">Dashboard</span></a>

                     </li>

                     <li class="nav-item mega-menu">
                         <a href="#" class="nav-link"><i class="menu_icon ti-layout-slider"></i><span
                                 class="menu-title">Management</span><i class="menu-arrow"></i></a>
                         <div class="submenu">
                             <div class="col-group-wrapper row">

                                 <!-- Products & Categories -->
                                 <div class="col-group col-md-3">
                                     <p class="category-heading">Products & Categories</p>
                                     <ul class="submenu-item">
                                         <li><a href="{{url('products')}}"><i class="fas fa-box"></i> Products</a></li>
                                         {{-- <li><a href="{{route('discount.index')}}"><i class="fas fa-tags"></i> Discount Products</a>
                     </li> --}}
                     <li><a href="{{route('Category.home')}}"><i class="fas fa-list"></i> Category</a></li>
                     <li><a href="{{route('subcategory.home')}}"><i class="fas fa-th-list"></i> Sub Category</a></li>
                     <li><a href="{{route('Brand.home')}}"><i class="fas fa-trademark"></i> Brands</a></li>
                     <li><a href="{{route('Unit.home')}}"><i class="fas fa-balance-scale"></i> Units</a></li>
                 </ul>
             </div>

             <!-- Purchase & Inventory -->
             <div class="col-group col-md-3">
                 <p class="category-heading">Purchase & Inventory</p>
                 <ul class="submenu-item">

                     <li><a href="{{route('InwardGatepass.home')}}"><i class="fas fa-shopping-cart"></i> Inward Gatepass </a></li>
                     <li><a href="{{route('add_inwardgatepass')}}"><i class="fas fa-shopping-cart"></i> Add Inward Gatepass </a></li>
                     <li><a href="{{route('Purchase.home')}}"><i class="fas fa-shopping-cart"></i> Purchase</a></li>
                    <li><a href="{{route('purchase.return.home')}}"><i class="fas fa-undo"></i> Purchase Return</a></li>
                    <li><a href="{{route('stock-wastage.index')}}"><i class="fas fa-trash"></i> Stock Wastage</a></li>

                 </ul>
             </div>

             <!-- Accounts -->
             <div class="col-group col-md-3">
                 <p class="category-heading">Accounts</p>
                 <ul class="submenu-item">
                     {{-- <li><a href="{{url('narrations')}}"><i class="fas fa-file-alt"></i> Narration</a></li> --}}
                     <li><a href="{{url('vendor')}}"><i class="fas fa-truck"></i> Vendor</a></li>
                     <li><a href="{{url('warehouse')}}"><i class="fas fa-warehouse"></i> Warehouse</a></li>
                     <li><a href="{{url('warehouse_stocks')}}"><i class="fas fa-boxes"></i> Warehouse Stock</a></li>
                     <li><a href="{{url('stock_transfers')}}"><i class="fas fa-exchange-alt"></i> Stock Transfer</a></li>
                 </ul>
             </div>
             <!-- Customers & Sales -->
             <div class="col-group col-md-3">
                 <p class="category-heading">Sales & Customers</p>
                 <ul class="submenu-item">
                     <li><a href="{{url('sale')}}"><i class="fas fa-receipt"></i> Sales</a></li>
                     <li><a href="{{ route('stock-hold-list') }}"><i class="fas fa-receipt"></i> Stock hold</a></li>
                     <li><a href="{{ route('stock-relase-list') }}"><i class="fas fa-receipt"></i> Stock Realase</a></li>
                     <li><a href="{{url('customers')}}"><i class="fas fa-user"></i> Customer</a></li>
                     <li><a href="{{url('sales-officers')}}"><i class="fas fa-user-tie"></i> Sales Officer</a></li>
                     <li><a href="{{url('zone')}}"><i class="fas fa-map-marker-alt"></i> Zone</a></li>
                 </ul>
             </div>
         </div>
 </div>
 </li>
 {{-- @if (auth()->user()->email === 'admin@admin.com') --}}
 <li class="nav-item">
     <a href="#" class="nav-link"><i class="menu_icon feather ft-clipboard"></i><span
             class="menu-title">User Managment</span><i class="menu-arrow"></i></a>
     <div class="submenu">
         <ul class="submenu-item">
             <li class="nav-item"><a class="nav-link" href="{{ route('users.index') }}"><i
                         class="fa-solid fa-users mr-2"></i><span>Users</span></a></li>
             <li class="nav-item"><a class="nav-link" href="{{ route('roles.index') }}"><i
                         class="fa-solid fa-user-lock mr-2"></i><span>Roles</span></a></li>
             <li class="nav-item"><a class="nav-link"
                     href="{{ route('permissions.index') }}"><i
                         class="fa-solid fa-user-lock mr-2"></i><span>Permissions</span></a>
             </li>
             <li class="nav-item"><a class="nav-link" href="{{ route('branch.index') }}"><i
                         class="fa-solid fa-code-branch mr-2"></i><span>Branches</span></a></li>
         </ul>
     </div>
 </li>
 <li class="nav-item">
     <a href="#" class="nav-link">
         <i class="menu_icon feather ft-clipboard"></i>
         <span class="menu-title">Vouchers</span>
         <i class="menu-arrow"></i>
     </a>
     <div class="submenu">
         <ul class="submenu-item">
             <li class="nav-item">
                 <a class="nav-link" href="{{ route('view_all') }}">
                     <i class="fa-solid fa-money-bill-wave mr-2"></i>
                     <span>Char Of Accounts </span>
                 </a>
             </li>
             <li class="nav-item">
                 <a class="nav-link" href="{{ route('narrations.index') }}">
                     <i class="fa-solid fa-money-bill-wave mr-2"></i>
                     <span>Narrations</span>
                 </a>
             </li>
             <li class="nav-item">
                 <a class="nav-link" href="{{ route('all-recepit-vochers') }}">
                     <i class="fa-solid fa-wallet mr-2"></i>
                     <span>Receipts Voucher</span>
                 </a>
             </li>
             <li class="nav-item">
                 <a class="nav-link" href="{{ route('all-Payment-vochers') }}">
                     <i class="fa-solid fa-wallet mr-2"></i>
                     <span>Payment Voucher</span>
                 </a>
             </li>
             <li class="nav-item">
                 <a class="nav-link" href="{{ route('all-expense-vochers') }}">
                     <i class="fa-solid fa-money-bill-wave mr-2"></i>
                     <span>Expense Voucher</span>
                 </a>
             </li>



             <li class="nav-item">
                 <a class="nav-link" href="{{ route('vouchers.index', 'journal voucher') }}">
                     <i class="fa-solid fa-wallet mr-2"></i>
                     <span>Journal Voucher</span>
                 </a>
             </li>



             <!-- <li class="nav-item">
                 <a class="nav-link" href="{{ route('vouchers.index', 'income voucher') }}">
                     <i class="fa-solid fa-wallet mr-2"></i>
                     <span>Income Voucher</span>
                 </a>
             </li>
             <li class="nav-item">
                 <a class="nav-link" href="{{ route('vouchers.index', 'Discount voucher') }}">
                     <i class="fa-solid fa-wallet mr-2"></i>
                     <span>Discount Voucher</span>
                 </a>
             </li>
             <li class="nav-item">
                 <a class="nav-link" href="{{ route('vouchers.index', 'Wht voucher') }}">
                     <i class="fa-solid fa-wallet mr-2"></i>
                     <span>Wht Voucher</span>
                 </a>
             </li> -->

         </ul>
     </div>
 </li>

 {{-- @endif --}}

 </ul>
 </div>
 </div>
 </nav>