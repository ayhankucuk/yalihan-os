{{--
    Context7 Component Library, 03-modul-ozellikler.md ve MCP entegrasyon rehberleri referans alınmıştır.
    Bu component, ilan özelliklerini MCP API'den dinamik olarak çeker, kategoriye göre gruplar ve <x-context7.badge> ile Context7 tasarım bütünlüğü sağlar.
@php
    // Controller'da $features = app(App\Services\MCP\PropertyFeatureService::class)->getFeaturesByProperty($propertyId);
@endphp
<x-ilan.feature-group-list :features="$features ?? []" />
