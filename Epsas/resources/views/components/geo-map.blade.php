@props([
    'id' => 'geo-map-'.uniqid(),
    'markers' => [],
    'lat' => -21.5355,
    'lng' => -64.7296,
    'zoom' => 14,
    'height' => '380px',
    'picker' => false,
    'latInput' => null,
    'lngInput' => null,
    'emptyText' => 'Selecciona una ubicacion en el mapa.',
])

<div
    id="{{ $id }}"
    {{ $attributes->merge(['class' => 'geo-map overflow-hidden rounded-[1.5rem] border border-slate-200 bg-slate-100 shadow-sm']) }}
    style="height: {{ $height }};"
    data-geo-map
    data-lat="{{ $lat }}"
    data-lng="{{ $lng }}"
    data-zoom="{{ $zoom }}"
    data-picker="{{ $picker ? 'true' : 'false' }}"
    @if($latInput) data-lat-input="{{ $latInput }}" @endif
    @if($lngInput) data-lng-input="{{ $lngInput }}" @endif
    data-empty-text="{{ $emptyText }}"
    data-markers='@json($markers)'
></div>
