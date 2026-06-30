@extends('layouts.frontend')

@section('title', 'İletişim - Yalıhan Emlak')

@section('content')
    <x-yaliihan.contact-page :show-map="true" :show-form="true" :show-office-info="true" />
@endsection
