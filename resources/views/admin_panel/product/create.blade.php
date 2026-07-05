@extends('admin_panel.layout.app')

@section('content')
<div class="main-wrapper">
    <div class="page-wrapper">
        <div class="content">
            @include('admin_panel.product._form')
        </div>
    </div>
</div>
@endsection

@section('scripts')
@include('admin_panel.product._form_scripts')
@endsection
