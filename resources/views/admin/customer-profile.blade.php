@extends('admin.layouts.admin')

@section('title', 'Müşteri Profili')
@section('page-title', 'Müşteri Profili')

@section('content')
    <x-crm.customer-profile :customer="$customer" />
    <div class="mt-8">
        <x-crm.ai-suggestion :context="['ai_suggestion' => $aiSuggestion]" />
    </div>
@endsection
