<!DOCTYPE html>
<html class="no-js" lang="zxx">

<head>
    @include('admin_panel.layout.head')
</head>

<body>
    @include('admin_panel.layout.header')

    @yield('content')

    @include('admin_panel.layout.footer')

    @yield('scripts')

</body>

</html>