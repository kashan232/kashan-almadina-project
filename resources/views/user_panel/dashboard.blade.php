@extends('admin_panel.layout.app')

@section('content')
<div class="main-content">
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12 text-center mt-5">
                <x-amt-logo width="160px" style="margin-bottom: 20px;" alt="Al Madina Traders Logo" />
                <h1 class="mt-4" style="color: #2d3e50; font-weight: 700;">Welcome, {{ Auth::user()->name }}!</h1>
                <p class="text-muted">You are logged in to the AL MADINA TRADERS Portal.</p>
            </div>
        </div>
    </div>
</div>
@endsection
