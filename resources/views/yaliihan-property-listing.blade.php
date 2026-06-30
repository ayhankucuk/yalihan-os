@extends('layouts.frontend')

@section('title', 'Emlak İlanları - Yalıhan Emlak')

@section('content')
    <x-yaliihan.property-listing :show-filters="true" :show-sort="true" :show-view-toggle="true" view-mode="grid" :pagination="true" />
@endsection
