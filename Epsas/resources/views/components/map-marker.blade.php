@props([
    'lat' => -21.5355,
    'lng' => -64.7296,
    'label' => 'EPSAS El Portillo',
    'height' => '300px',
])

<x-geo-map
    :lat="(float) $lat"
    :lng="(float) $lng"
    :zoom="15"
    :height="$height"
    :markers="[[
        'type' => 'oficina',
        'lat' => (float) $lat,
        'lng' => (float) $lng,
        'title' => $label,
        'description' => 'Ubicacion institucional',
        'category' => 'Oficina',
    ]]"
    {{ $attributes }}
/>
