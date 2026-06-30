{{--
    csp-script.blade.php
    Nonce-aware CDN script tag bileşeni.
    Kullanım: <x-csp-script src="https://cdn.jsdelivr.net/npm/chart.js" />
--}}
<script src="{{ $src }}"
    @isset($integrity) integrity="{{ $integrity }}" crossorigin="anonymous" @endisset
    nonce="{{ $cspNonce ?? '' }}"
    {{ $attributes }}></script>
