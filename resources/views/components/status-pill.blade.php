@props(['tone' => 'neutral', 'icon' => null])

@php
    $tones = ['neutral', 'success', 'warning', 'danger'];
    $tone = in_array($tone, $tones, true) ? $tone : 'neutral';
    $iconName = $icon ?? match ($tone) {
        'success' => 'check',
        'warning' => 'alert',
        'danger' => 'x',
        default => null,
    };
@endphp

<span {{ $attributes->merge(['class' => "status-pill status-pill-{$tone}"]) }}>
    @if (is_string($iconName) && $iconName !== '')
        <x-icon :name="$iconName" />
    @endif
    {{ $slot }}
</span>
