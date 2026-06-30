@extends('admin.layouts.master')

@section('title', 'Konum Ayarları')

@section('content')
    <div class="container mt-4">
        <h2>Konum Ayarları</h2>
        <div class="card mt-3">
            <div class="p-6">
                <h5>Ülkeler</h5>
                <ul>
                    @foreach ($locations['ülkeler'] as $ulke)
                        <li>{{ $ulke }}</li>
                    @endforeach
                </ul>
                <h5>İller</h5>
                <ul>
                    @foreach ($locations['iller'] as $il)
                        <li>{{ $il }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endsection
