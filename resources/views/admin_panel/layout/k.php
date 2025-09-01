{{-- @include('admin_panel.layout.header') --}}

{{-- @yield('content')
@include('admin_panel.layout.footer') --}}


<!DOCTYPE html>
<html class="no-js" lang="zxx">

<head>

    <!--=========================*
                Met Data
    *===========================-->
    <meta charset="UTF-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Zare Bootstrap 4 Admin Template">

    <!--=========================*
              Page Title
    *===========================-->
    <title>Home 2 | Zare Bootstrap 4 Admin Template</title>

    <!--=========================*
                Favicon
    *===========================-->
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('assets/images/favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">

    <link rel="stylesheet" href="{{ asset('assets/css/owl.carousel.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/owl.theme.default.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/font-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/themify-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/ionicons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/et-line.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/feather.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/flag-icon.min.css') }}">
    <script src="{{ asset('assets/js/modernizr-2.8.3.min.js') }}"></script>
    <link rel="stylesheet" href="{{ asset('assets/css/metisMenu.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/slicknav.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendors/am-charts/css/am-charts.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendors/charts/morris-bundle/morris.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendors/charts/c3charts/c3.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendors/data-table/css/jquery.dataTables.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendors/data-table/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendors/data-table/css/responsive.bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendors/data-table/css/responsive.jqueryui.min.css') }}">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    {{-- Online Links --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/brands.min.css"
        integrity="sha512-58P9Hy7II0YeXLv+iFiLCv1rtLW47xmiRpC1oFafeKNShp8V5bKV/ciVtYqbk2YfxXQMt58DjNfkXFOn62xE+g=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
        integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/brands.min.css"
        integrity="sha512-58P9Hy7II0YeXLv+iFiLCv1rtLW47xmiRpC1oFafeKNShp8V5bKV/ciVtYqbk2YfxXQMt58DjNfkXFOn62xE+g=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    {{-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous"> --}}
</head>

<body>
    <!--[if lt IE 8]>
<p class="browserupgrade">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience.</p>
<![endif]-->

    <!--=========================*
         Page Container
*===========================-->
    <div class="container-scroller">
        <!--=========================*
              Navigation
    *===========================-->
        <nav class="rt_nav_header horizontal-layout col-lg-12 col-12 p-0">
            <div class="top_nav flex-grow-1">
                <div class="container d-flex flex-row h-100 align-items-center">
                    <!--=========================*
                              Logo
                *===========================-->
                    <div class="text-center rt_nav_wrapper d-flex align-items-center">
                        <a class="nav_logo rt_logo" href="index.html"><img
                                src="{{ asset('assets/images/WIJDAN-removebg-preview.png') }}" alt="logo" /></a>
                        {{-- <a class="nav_logo nav_logo_mob" href="index.html"><img src="{{asset('assets/images/WIJDAN-removebg-preview.png')}}" alt="logo"/></a> --}}
                    </div>
                    <!--=========================*
                           End Logo
               *===========================-->
                    <div class="nav_wrapper_main d-flex align-items-center justify-content-between flex-grow-1">
                        <ul class="navbar-nav navbar-nav-right mr-0 ml-auto">
                            <!--==================================*
                                 Notification Section
                        *====================================-->
                            <li class="nav-item dropdown">
                                <a class="nav-link count-indicator dropdown-toggle" id="notificationDropdown"
                                    href="#" data-toggle="dropdown">
                                    <i class="feather ft-bell"></i>
                                    <span class="count">4</span>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right navbar-dropdown rt-notification-list"
                                    aria-labelledby="notificationDropdown">
                                    <div class="dropdown-item">
                                        <p class="mb-0 font-weight-normal float-left">You have 3 new notifications</p>
                                        <a href="#" class="view_btn">view all</a>
                                    </div>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item rt-notification-item">
                                        <div class="rt-notification-thumbnail">
                                            <div class="rt-notification-icon bg_blue">
                                                <i class="ti-map-alt bg_blue mx-0"></i>
                                            </div>
                                        </div>
                                        <div class="rt-notification-item-content">
                                            <h6 class="rt-notification-subject font-weight-normal text-dark mb-1">You
                                                added your Location</h6>
                                            <p class="font-weight-light small-text mb-0">
                                                Just now
                                            </p>
                                        </div>
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item rt-notification-item">
                                        <div class="rt-notification-thumbnail">
                                            <div class="rt-notification-icon bg_warning">
                                                <i class="ti-bolt-alt bg_warning mx-0"></i>
                                            </div>
                                        </div>
                                        <div class="rt-notification-item-content">
                                            <h6 class="rt-notification-subject font-weight-normal text-dark mb-1">Your
                                                Subscription Expired</h6>
                                            <p class="font-weight-light small-text mb-0">
                                                30 Seconds ago
                                            </p>
                                        </div>
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item rt-notification-item">
                                        <div class="rt-notification-thumbnail">
                                            <div class="rt-notification-icon bg_danger">
                                                <i class="ti-heart bg_danger mx-0"></i>
                                            </div>
                                        </div>
                                        <div class="rt-notification-item-content">
                                            <h6 class="rt-notification-subject font-weight-normal text-dark mb-1">Some
                                                special like you</h6>
                                            <p class="font-weight-light small-text mb-0">
                                                Just Now
                                            </p>
                                        </div>
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item rt-notification-item">
                                        <div class="rt-notification-thumbnail">
                                            <div class="rt-notification-icon bg_info">
                                                <i class="ti-comments bg_info mx-0"></i>
                                            </div>
                                        </div>
                                        <div class="rt-notification-item-content">
                                            <h6 class="rt-notification-subject font-weight-normal text-dark mb-1">New
                                                Commetns On Post</h6>
                                            <p class="font-weight-light small-text mb-0">
                                                Just Now
                                            </p>
                                        </div>
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item rt-notification-item">
                                        <div class="rt-notification-thumbnail">
                                            <div class="rt-notification-icon bg_secondary">
                                                <i class="ti-settings bg_secondary mx-0"></i>
                                            </div>
                                        </div>
                                        <div class="rt-notification-item-content">
                                            <h6 class="rt-notification-subject font-weight-normal text-dark mb-1">You
                                                changed your Settings</h6>
                                            <p class="font-weight-light small-text mb-0">
                                                Just Now
                                            </p>
                                        </div>
                                    </a>
                                </div>
                            </li>
                            <!--==================================*
                                 End Notification Section
                        *====================================-->
                            <!--==================================*
                                 Message Section
                        *====================================-->
                            <li class="nav-item dropdown">
                                <a class="nav-link count-indicator dropdown-toggle" id="messageDropdown"
                                    href="#" data-toggle="dropdown" aria-expanded="false">
                                    <i class="feather ft-mail mx-0"></i>
                                    <span class="count">5</span>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right navbar-dropdown rt-notification-list"
                                    aria-labelledby="messageDropdown">
                                    <div class="dropdown-item">
                                        <p class="mb-0 font-weight-normal float-left">You have 3 New Messages</p>
                                        <a href="#" class="view_btn">view all</a>
                                    </div>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item rt-notification-item">
                                        <div class="rt-notification-thumbnail">
                                            <img src="images/author/author-img1.jpg" class="profile-pic"
                                                alt="image">
                                        </div>
                                        <div class="rt-notification-item-content flex-grow">
                                            <h6 class="rt-notification-subject ellipsis font-weight-medium">Jhon Doe
                                                <span class="float-right font-weight-light small-text">3:15 PM</span>
                                            </h6>
                                            <p class="font-weight-light small-text">
                                                Hello are you there?
                                            </p>
                                        </div>
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item rt-notification-item">
                                        <div class="rt-notification-thumbnail">
                                            <img src="images/author/author-img2.jpg" class="profile-pic"
                                                alt="image">
                                        </div>
                                        <div class="rt-notification-item-content flex-grow">
                                            <h6 class="rt-notification-subject ellipsis font-weight-medium">David Boos
                                                <span class="float-right font-weight-light small-text">1:25 PM</span>
                                            </h6>
                                            <p class="font-weight-light small-text">
                                                Waiting for your Response...
                                            </p>
                                        </div>
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item rt-notification-item">
                                        <div class="rt-notification-thumbnail">
                                            <img src="images/user.jpg" class="profile-pic" alt="image">
                                        </div>
                                        <div class="rt-notification-item-content flex-grow">
                                            <h6 class="rt-notification-subject ellipsis font-weight-medium"> Jason Roy
                                                <span class="float-right font-weight-light small-text">5:21 PM</span>
                                            </h6>
                                            <p class="font-weight-light small-text">
                                                Hi there, Hope you are well
                                            </p>
                                        </div>
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item rt-notification-item">
                                        <div class="rt-notification-thumbnail">
                                            <img src="images/author/author-img3.jpg" class="profile-pic"
                                                alt="image">
                                        </div>
                                        <div class="rt-notification-item-content flex-grow">
                                            <h6 class="rt-notification-subject ellipsis font-weight-medium"> Malika Roy
                                                <span class="float-right font-weight-light small-text">2:30 PM</span>
                                            </h6>
                                            <p class="font-weight-light small-text">
                                                Your Product Dispatched ...
                                            </p>
                                        </div>
                                    </a>
                                </div>
                            </li>
                            <!--==================================*
                                 End Message Section
                        *====================================-->
                            <!--==================================*
                                 Profile Menu
                        *====================================-->
                            <li class="nav-item nav-profile dropdown">
                                <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown"
                                    id="profileDropdown">
                                    <span class="profile_name">{{ Auth::user()->name }} <i
                                            class="feather ft-chevron-down"></i></span>
                                    <img src="assets/images/user.jpg" alt="profile" />
                                </a>
                                <div class="dropdown-menu dropdown-menu-right navbar-dropdown pt-2"
                                    aria-labelledby="profileDropdown">
                                    <a class="dropdown-item">
                                        <i class="ti-user text-dark mr-3"></i> Profile
                                    </a>
                                    <a class="dropdown-item">
                                        <i class="ti-settings text-dark mr-3"></i> Account Settings
                                    </a>
                                    <span role="separator" class="divider"></span>
                                    {{-- <a class="dropdown-item"> --}}
                                    {{-- <i class="ti-power-off text-dark mr-3"></i> --}}
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item">
                                            <i class="ti-power-off text-dark mr-3"></i> Logout
                                        </button>
                                    </form>
                                    {{-- </a> --}}
                                </div>
                            </li>
                            <!--==================================*
                                 End Profile Menu
                        *====================================-->
                        </ul>
                        <!--=========================*
                               Mobile Menu
                   *===========================-->
                        <button class="navbar-toggler align-self-center" type="button" data-toggle="minimize">
                            <span class="feather ft-menu text-white"></span>
                        </button>
                        <!--=========================*
                           End Mobile Menu
                   *===========================-->
                    </div>
                </div>
            </div>
            <div class="nav-bottom">
                <div class="container">
                    <ul class="nav page-navigation">
                        <!--=========================*
                              Home
                    *===========================-->
                        <li class="nav-item">
                            <a href="{{ url('/home') }}" class="nav-link"><i
                                    class="menu_icon feather ft-home"></i><span
                                    class="menu-title">Dashboard</span></a>

                        </li>
                        <!--=========================*
                              UI Features
                    *===========================-->
                        <li class="nav-item mega-menu">
                            <a href="#" class="nav-link"><i class="menu_icon ti-layout-slider"></i><span
                                    class="menu-title">Management</span><i class="menu-arrow"></i></a>
                            <div class="submenu">
                                <div class="col-group-wrapper row">
                                    <div class="col-group col-md-4 mb-mob-0">
                                        <div class="row">
                                            <div class="col-12">
                                                <!--=========================*
                                                      Basic Elements
                                                *===========================-->
                                                <p class="category-heading">Product Managment</p>
                                                <div class="submenu-item">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <ul>
                                                                <li class="nav-item"><a class="nav-link"
                                                                        href="{{ route('Category.home') }}"><i
                                                                            class="menu_icon ti-alert"></i><span>Category</span></a>
                                                                </li>
                                                                <li class="nav-item"><a class="nav-link"
                                                                        href="{{ route('subcategory.home') }}"><i
                                                                            class="menu_icon ti-id-badge"></i><span>Sub
                                                                            Category</span></a></li>
                                                                <li class="nav-item"><a class="nav-link"
                                                                        href="{{ route('Brand.home') }}"><i
                                                                            class="menu_icon ti-smallcap"></i><span>Brands</span></a>
                                                                </li>
                                                                <li class="nav-item"><a class="nav-link"
                                                                        href="{{ route('Unit.home') }}"><i
                                                                            class="menu_icon ion-ios-photos"></i><span>Units</span></a>
                                                                </li>
                                                                <li class="nav-item"><a class="nav-link"
                                                                        href="{{ route('products.index') }}"><i
                                                                            class="menu_icon icon-basket"></i><span>Products</span></a>
                                                                </li>

                                                                {{-- <li class="nav-item"><a class="nav-link" href="accordion.html"><i class="menu_icon ti-layout-accordion-separated"></i><span>Accordion</span></a></li>
                                                                <li class="nav-item"><a class="nav-link" href="buttons.html"><i class="menu_icon icon-focus"></i><span>Buttons</span></a></li>
                                                                <li class="nav-item"><a class="nav-link" href="badges.html"><i class="menu_icon icon-ribbon"></i><span>Badges</span></a></li> --}}

                                                            </ul>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <ul>

                                                                <li class="nav-item"><a class="nav-link"
                                                                        href="{{ url('warehouse') }}"><i
                                                                            class="menu_icon ti-alert"></i><span>warehouse</span></a>
                                                                </li>
                                                                <li class="nav-item"><a class="nav-link"
                                                                        href="{{ url('vendor') }}"><i
                                                                            class="menu_icon ti-alert"></i><span>vendor</span></a>
                                                                </li>
                                                                <li class="nav-item"><a class="nav-link"
                                                                        href="{{ url('customers') }}"><i
                                                                            class="menu_icon ti-alert"></i><span>customer</span></a>
                                                                </li>
                                                                <li class="nav-item"><a class="nav-link"
                                                                        href="{{ url('zone') }}"><i
                                                                            class="menu_icon ti-alert"></i><span>zone</span></a>
                                                                </li>
                                                                <li class="nav-item"><a class="nav-link"
                                                                        href="{{ url('sales-officers') }}"><i
                                                                            class="menu_icon ti-alert"></i><span>Sales
                                                                            Officer</span></a></li>
                                                                <li class="nav-item"><a class="nav-link"
                                                                        href="{{ url('transport') }}"><i
                                                                            class="menu_icon ti-alert"></i><span>Transport</span></a>
                                                                </li>
                                                                <li class="nav-item"><a class="nav-link"
                                                                        href="{{ url('narrations') }}"><i
                                                                            class="menu_icon ti-alert"></i><span>Narration</span></a>
                                                                </li>
                                                                {{-- <li class="nav-item"><a class="nav-link" href="carousel.html"><i class="menu_icon ti-layout-slider"></i><span>Carousels</span></a></li>
                                                                <li class="nav-item"><a class="nav-link" href="dropdown.html"><i class="menu_icon icon-layers"></i><span>Dropdown</span></a></li>
                                                                <li class="nav-item"><a class="nav-link" href="tabs.html"><i class="menu_icon ti-layout-tab"></i><span>Tabs</span></a></li> --}}

                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-group col-md-4">
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="submenu-item pt-5 mt-2 pt-mob-0 mt-mob-0">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <ul>
                                                                {{-- <li class="nav-item"><a class="nav-link" href="list-group.html"><i class="menu_icon ti-list"></i><span>List Group</span></a></li>
                                                                <li class="nav-item"><a class="nav-link" href="modals.html"><i class="menu_icon ti-layers-alt"></i><span>Modals</span></a></li>
                                                                <li class="nav-item"><a class="nav-link" href="pagination.html"><i class="menu_icon ion-android-more-horizontal"></i><span>Pagination</span></a></li> --}}
                                                            </ul>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <ul>
                                                                {{-- <li class="nav-item"><a class="nav-link" href="progressbar.html"><i class="menu_icon ion-ios-settings-strong"></i><span>Progressbar</span></a></li> --}}
                                                                {{-- <li class="nav-item"><a class="nav-link" href="grid.html"><i class="menu_icon ti-layout-grid4"></i><span>Grid</span></a></li> --}}
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!--=========================*
                                          Icons
                                *===========================-->
                                    <div class="col-group col-md-4">
                                        <p class="category-heading">Products</p>
                                        <ul class="submenu-item">
                                            {{-- <li class="nav-item"><a class="nav-link" href="font-awesome.html"><i class="menu_icon ti-flag-alt"></i> <span>Font Awesome</span></a></li> --}}
                                            {{-- <li class="nav-item"><a class="nav-link" href="themify.html"><i class="menu_icon ti-themify-favicon"></i><span>Themify</span></a></li> --}}
                                            {{-- <li class="nav-item"><a class="nav-link" href="ionicons.html"><i class="menu_icon ion-ionic"></i><span>Ionicons V2</span></a></li> --}}
                                            {{-- @if (auth()->user()->can('View Product') || auth()->user()->email === 'admin@admin.com') --}}
                                            {{-- @endif --}}
                                            <li class="nav-item"><a class="nav-link"
                                                    href="{{ route('Purchase.home') }}"><i
                                                        class="menu_icon icon-basket"></i><span>Purchase</span></a>
                                            </li>
                                            <li class="nav-item"><a class="nav-link"
                                                    href="{{ route('sale.index') }}"><i
                                                        class="menu_icon icon-basket"></i><span>Sale</span></a>
                                            </li>
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
                                        <a class="nav-link" href="{{ route('vouchers.index', 'expense voucher') }}">
                                            <i class="fa-solid fa-money-bill-wave mr-2"></i>
                                            <span>Expense Voucher</span>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a class="nav-link" href="{{ route('vouchers.index', 'receipt voucher') }}">
                                            <i class="fa-solid fa-wallet mr-2"></i>
                                            <span>Receipts Voucher</span>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a class="nav-link" href="{{ route('vouchers.index', 'journal voucher') }}">
                                            <i class="fa-solid fa-wallet mr-2"></i>
                                            <span>Journal Voucher</span>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a class="nav-link" href="{{ route('vouchers.index', 'payment voucher') }}">
                                            <i class="fa-solid fa-wallet mr-2"></i>
                                            <span>Payment Voucher</span>
                                        </a>
                                    </li>

                                    <li class="nav-item">
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
                                    </li>

                                </ul>
                            </div>
                        </li>

                        {{-- @endif --}}
                        {{-- <!--=========================*
                              Advance Kit
                    *===========================-->
                        <li class="nav-item">
                            <a href="#" class="nav-link"><i class="menu_icon feather ft-briefcase"></i><span class="menu-title">Advance Kit</span><i class="menu-arrow"></i></a>
                            <div class="submenu">
                                <ul class="submenu-item">
                                    <li class="nav-item"><a class="nav-link" href="toastr.html"><i class="menu_icon ti-layout-cta-left"></i> <span>Toastr</span></a></li>
                                    <li class="nav-item"><a class="nav-link" href="sweet-notification.html"><i class="menu_icon ti-layout-media-overlay-alt-2"></i> <span>Sweet Alert</span></a></li>
                                    <li class="nav-item"><a class="nav-link" href="cropper.html"><i class="menu_icon ion-crop"></i> <span>Image Cropper</span></a></li>
                                    <li class="nav-item"><a class="nav-link" href="loaders.html"><i class="menu_icon ion-load-a"></i> <span>Css Loaders</span></a></li>
                                    <li class="nav-item"><a class="nav-link" href="app-tour.html"><i class="menu_icon ti-flag-alt"></i> <span>App Tour</span></a></li>
                                    <li class="nav-item"><a class="nav-link" href="ladda-button.html"><i class="menu_icon ion-load-b"></i> <span>Ladda Button</span></a></li>
                                    <li class="nav-item"><a class="nav-link" href="dropzone.html"><i class="menu_icon ti-layout-placeholder"></i> <span>Dropzone</span></a></li>
                                </ul>
                            </div>
                        </li>
                        <!--=========================*
                                 Forms
                    *===========================-->
                        <li class="nav-item">
                            <a href="#" class="nav-link"><i class="menu_icon feather ft-clipboard"></i><span class="menu-title">Forms</span><i class="menu-arrow"></i></a>
                            <div class="submenu">
                                <ul class="submenu-item">
                                    <li class="nav-item"><a class="nav-link" href="form-basic.html"><i class="menu_icon ion-edit"></i><span>Basic ELements</span></a></li>
                                    <li class="nav-item"><a class="nav-link" href="form-layouts.html"><i class="menu_icon ti-layout-grid2-thumb"></i><span>Form Layouts</span></a></li>
                                    <li class="nav-item"><a class="nav-link" href="form-groups.html"><i class="menu_icon ion-ios-paper"></i><span>Input Groups</span></a></li>
                                    <li class="nav-item"><a class="nav-link" href="form-validation.html"><i class="menu_icon ion-android-cancel"></i><span>Form Validation</span></a></li>
                                </ul>
                            </div>
                        </li>
                        <!--=========================*
                                  Maps
                    *===========================-->
                        <li class="nav-item">
                            <a href="#" class="nav-link"><i class="menu_icon feather ft-map-pin"></i><span
                                class="menu-title">Maps</span><i class="menu-arrow"></i></a>
                            <div class="submenu">
                                <ul class="submenu-item">
                                    <li class="nav-item"><a class="nav-link" href="google-maps.html"><i class="menu_icon icon-map"></i><span>Google Maps</span></a></li>
                                    <li class="nav-item"><a class="nav-link" href="am-maps.html"><i class="menu_icon icon-map-pin"></i><span>AM Chart Maps</span></a></li>
                                </ul>
                            </div>
                        </li>
                        <li class="nav-item mega-menu">
                            <a href="#" class="nav-link"><i class="menu_icon feather ft-file-text"></i><span class="menu-title">Data</span><i class="menu-arrow"></i></a>
                            <div class="submenu">
                                <div class="col-group-wrapper row">
                                    <!--=========================*
                                          Tables
                                *===========================-->
                                    <div class="col-group col-md-3">
                                        <p class="category-heading">Table</p>
                                        <ul class="submenu-item">
                                            <li class="nav-item"><a class="nav-link" href="basic-table.html"><i class="menu_icon ion-ios-grid-view"></i><span>Basic Tables</span></a></li>
                                            <li class="nav-item"><a class="nav-link" href="datatable.html"><i class="menu_icon ti-layout-slider-alt"></i><span>Datatable</span></a></li>
                                            <li class="nav-item"><a class="nav-link" href="js-grid.html"><i class="menu_icon ti-view-list-alt"></i><span>Js Grid Table</span></a></li>
                                        </ul>
                                    </div>
                                    <!--=========================*
                                          Editors
                                *===========================-->
                                    <div class="col-group col-md-3">
                                        <p class="category-heading">Editors</p>
                                        <ul class="submenu-item">
                                            <li class="nav-item"><a class="nav-link" href="text-editor.html"><i class="menu_icon ti-uppercase"></i><span>Text Editor</span></a></li>
                                            <li class="nav-item"><a class="nav-link" href="code-editor.html"><i class="menu_icon ion-code"></i><span>Code Editor</span></a></li>
                                        </ul>
                                    </div>
                                    <!--=========================*
                                          Charts
                                *===========================-->
                                    <div class="col-group col-md-6">
                                        <p class="category-heading">Charts</p>
                                        <div class="submenu-item">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <ul>
                                                        <li class="nav-item"><a class="nav-link" href="chart-js.html"><i class="menu_icon feather ft-bar-chart"></i><span>Chart Js</span></a></li>
                                                        <li class="nav-item"><a class="nav-link" href="morris-charts.html"><i class="menu_icon feather ft-bar-chart-2"></i><span>Morris Chart Js</span></a></li>
                                                    </ul>
                                                </div>
                                                <div class="col-md-6">
                                                    <ul>
                                                        <li class="nav-item"><a class="nav-link" href="c3-chart.html"><i class="menu_icon feather ft-bar-chart-line"></i><span>C3 Chart Js</span></a></li>
                                                        <li class="nav-item"><a class="nav-link" href="chartist.html"><i class="menu_icon feather ft-bar-chart-line-"></i><span>Chartist Js</span></a></li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <li class="nav-item mega-menu">
                            <a href="#" class="nav-link"><i class="menu_icon feather ft-layers"></i><span class="menu-title">Pages</span><i class="menu-arrow"></i></a>
                            <div class="submenu">
                                <div class="col-group-wrapper row">
                                    <!--=========================*
                                          Error Pages
                                *===========================-->
                                    <div class="col-group col-md-3">
                                        <p class="category-heading">Error Pages</p>
                                        <ul class="submenu-item">
                                            <li class="nav-item"><a class="nav-link" href="404.html"><i class="menu_icon ti-unlink"></i><span>404</span></a></li>
                                            <li class="nav-item"><a class="nav-link" href="500.html"><i class="menu_icon ti-close"></i><span>500</span></a></li>
                                            <li class="nav-item"><a class="nav-link" href="505.html"><i class="menu_icon ti-na"></i><span>505</span></a></li>
                                        </ul>
                                    </div>
                                    <!--=========================*
                                          Other Pages
                                *===========================-->
                                    <div class="col-group col-md-3">
                                        <p class="category-heading">Other</p>
                                        <ul class="submenu-item">
                                            <li class="nav-item"><a class="nav-link" href="blank.html"><i class="menu_icon feather ft-file"></i><span>Blank Page</span></a></li>
                                            <li class="nav-item"><a class="nav-link" href="invoice.html"><i class="menu_icon feather ft-paperclip"></i><span>Invoice</span></a></li>
                                            <li class="nav-item"><a class="nav-link" href="pricing.html"><i class="menu_icon feather ft-dollar-sign"></i><span>Pricing</span></a></li>
                                            <li class="nav-item"><a class="nav-link" href="profile.html"><i class="menu_icon feather ft-user-check"></i><span>Profile</span></a></li>
                                            <li class="nav-item"><a class="nav-link" href="timeline.html"><i class="menu_icon feather ft-clock"></i><span>Timeline</span></a></li>
                                        </ul>
                                    </div>
                                    <!--=========================*
                                          Session
                                *===========================-->
                                    <div class="col-group col-md-6">
                                        <p class="category-heading">Session</p>
                                        <div class="submenu-item">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <ul>
                                                        <li class="nav-item"><a class="nav-link" href="login.html"><i class="menu_icon feather ft-log-in"></i><span>Login</span></a></li>
                                                        <li class="nav-item"><a class="nav-link" href="register.html"><i class="menu_icon ion-person-add"></i><span>Register</span></a></li>
                                                        <li class="nav-item"><a class="nav-link" href="lock.html"><i class="menu_icon ti-lock"></i><span>Lock Screen</span></a></li>
                                                    </ul>
                                                </div>
                                                <div class="col-md-6">
                                                    <ul>
                                                        <li class="nav-item"><a class="nav-link" href="reset-password.html"><i class="menu_icon feather ft-lock"></i><span>Reset Password</span></a></li>
                                                        <li class="nav-item"><a class="nav-link" href="forgot-password.html"><i class="menu_icon ti-bookmark-alt"></i><span>Forgot Password</span></a></li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link"><i class="menu_icon feather ft-zap"></i><span class="menu-title">Apps</span><i class="menu-arrow"></i></a>
                            <div class="submenu">
                                <ul class="submenu-item">
                                    <!--=========================*
                                          Calendar
                                *===========================-->
                                    <li class="nav-item"><a class="nav-link" href="full-calendar.html"><i class="menu_icon feather ft-calendar"></i><span>Calendar</span></a></li>
                                    <!--=========================*
                                          Email
                                *===========================-->
                                    <li class="nav-item"><a class="nav-link" href="inbox.html" aria-expanded="true"><i class="menu_icon feather ft-mail"></i><span>Email</span></a></li>
                                    <!--=========================*
                                          Gallery
                                *===========================-->
                                    <li class="nav-item"><a class="nav-link" href="gallery.html"><i class="menu_icon feather ft-image"></i><span>Gallery</span></a></li>
                                </ul>
                            </div>
                        </li> --}}
                    </ul>
                </div>
            </div>
        </nav>

        @yield('content')

        <footer>
            <div class="footer-area">
                <p>&copy; Copyright 2025. All right reserved. Prowave Software Solutions.</p>
            </div>
        </footer>
        <!--=================================*
                End Footer Section
    *===================================-->

    </div>
    <!--=========================*
        End Page Container
*===========================-->


    <!--=========================*
            Scripts
*===========================-->

    <!-- Jquery Js -->

    <script src="assets/js/jquery.min.js"></script>
    <!-- bootstrap 4 js -->
    <script src="assets/js/popper.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <!-- Owl Carousel Js -->
    <script src="assets/js/owl.carousel.min.js"></script>
    <!-- Metis Menu Js -->
    <script src="assets/js/metisMenu.min.js"></script>
    <!-- SlimScroll Js -->
    <script src="assets/js/jquery.slimscroll.min.js"></script>
    <!-- Slick Nav -->
    <script src="assets/js/jquery.slicknav.min.js"></script>

    <!--=========================*
        This Page Script
*===========================-->
    <!-- start amchart js -->
    <script src="assets/vendors/am-charts/js/ammap.js"></script>
    <script src="assets/vendors/am-charts/js/worldLow.js"></script>
    <script src="assets/vendors/am-charts/js/continentsLow.js"></script>
    <script src="assets/vendors/am-charts/js/light.js"></script>
    <!-- maps js -->
    <script src="assets/js/am-maps.js"></script>

    <!--Morris Chart-->
    <script src="assets/vendors/charts/morris-bundle/raphael.min.js"></script>
    <script src="assets/vendors/charts/morris-bundle/morris.js"></script>

    <!--Chart Js-->
    <script src="assets/vendors/charts/charts-bundle/Chart.bundle.js"></script>

    <!-- C3 Chart -->
    <script src="assets/vendors/charts/c3charts/c3.min.js"></script>
    <script src="assets/vendors/charts/c3charts/d3-5.4.0.min.js"></script>

    <!-- Data Table js -->
    <script src="assets/vendors/data-table/js/jquery.dataTables.js"></script>
    <script src="assets/vendors/data-table/js/jquery.dataTables.min.js"></script>
    <script src="assets/vendors/data-table/js/dataTables.bootstrap4.min.js"></script>
    <script src="assets/vendors/data-table/js/dataTables.responsive.min.js"></script>
    <script src="assets/vendors/data-table/js/responsive.bootstrap.min.js"></script>

    <!--Sparkline Chart-->
    <script src="assets/vendors/charts/sparkline/jquery.sparkline.js"></script>

    <!--Home Script-->
    <script src="assets/js/home.js"></script>

    <!-- Main Js -->
    <script src="assets/js/main.js"></script>
    {{-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q" crossorigin="anonymous"></script> --}}

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @yield('js')

</body>

</html>
