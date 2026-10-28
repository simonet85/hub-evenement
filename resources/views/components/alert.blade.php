@props(['tone' => 'info', 'title' => null, 'icon' => null])

@php
    $tones = ['info', 'success', 'warning', 'danger'];
    $tone = in_array($tone, $tones, true) ? $tone : 'info';
    $role = $tone === 'danger' ? 'alert' : 'status';
    $iconName = $icon ?? match ($tone) {
        'success' => 'check',
        'warning' => 'alert',
        'danger' => 'alert',
        default => 'info',
    };
@endphp

<div {{ $attributes->merge(['class' => "alert alert-{$tone}", 'role' => $role]) }}>
    @if (is_string($iconName) && $iconName !== '')
        <x-icon :name="$iconName" :size="18" />
    @endif
    <div class="min-w-0">
        @if ($title)
            <p class="alert-title">{{ $title }}</p>
        @endif
        <div @class(['mt-1' => $title])>{{ $slot }}</div>
    </div>
</div>
