@props(['name', 'size' => 16])

@php
    $paths = [
        'check' => '<path d="M4 12.5 9.5 18 20 6.5"/>',
        'x' => '<path d="M6 6l12 12M18 6 6 18"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5.5l3.5 2"/>',
        'alert' => '<path d="M12 4 2.8 20h18.4L12 4Z"/><path d="M12 10v4"/><path d="M12 17.5h.01"/>',
        'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5"/><path d="M12 7.5h.01"/>',
        'gift' => '<path d="M4 11h16v9H4z"/><path d="M3 7h18v4H3z"/><path d="M12 7v13"/><path d="M12 7C10 7 7 6 7 4a2 2 0 0 1 4-.8L12 7Zm0 0c2 0 5-1 5-3a2 2 0 0 0-4-.8L12 7Z"/>',
        'dot' => '<circle cx="12" cy="12" r="4" fill="currentColor" stroke="none"/>',
    ];
@endphp

<svg
    {{ $attributes->merge([
        'width' => $size,
        'height' => $size,
        'viewBox' => '0 0 24 24',
        'fill' => 'none',
        'stroke' => 'currentColor',
        'stroke-width' => '1.75',
        'stroke-linecap' => 'round',
        'stroke-linejoin' => 'round',
        'aria-hidden' => 'true',
        'focusable' => 'false',
    ]) }}
>{!! $paths[$name] ?? '' !!}</svg>
