{{-- Ad config for resources/js/ads.js. Renders nothing when ads are off. --}}
@php($ad = app(\App\Ads\AdSlot::class)->payload())

@if ($ad)
    <script type="application/json" id="bdg-ad-config">@json($ad, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)</script>
@endif
