@extends('admin.layouts.admin')

@section('title')
    @yield('title', 'Admin')
@endsection

@section('page-title')
    @yield('page-title', 'Yönetim Paneli')
@endsection

@push('topbar-actions')
    @stack('topbar-actions')
@endpush

@section('content')
    @yield('content')
@endsection
