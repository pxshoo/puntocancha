@props(['club' => null])
@if($club)
    @php($palette = config('branding.palettes.'.$club->brand_palette, config('branding.palettes.original')))
    <style data-club-theme="{{ $club->id }}">
        :root { @foreach($palette['light'] as $token => $rgb) --brand-{{ $token }}: {{ $rgb }}; @endforeach }
        :root.dark { @foreach($palette['dark'] as $token => $rgb) --brand-{{ $token }}: {{ $rgb }}; @endforeach }
    </style>
@endif
