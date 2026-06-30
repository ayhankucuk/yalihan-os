{{--
    Context7 ve MCP entegrasyonuna uygun ilan özellikleri sayfası örneği.
--}}
@extends('admin.layouts.context7')

@section('title', 'İlan Özellikleri')
@section('page-title', 'İlan Özellikleri')

@section('content')
    <x-ilan.property-features-mcp :property-id="$propertyId" :features="$features" />
@endsection
