@props(['value' => 0, 'max' => 100, 'label' => null, 'valueLabel' => null])

@php
    $max = max(1, (float) $max);
    $value = max(0, min((float) $value, $max));
    $percent = round($value / $max * 100, 1);
@endphp

<div {{ $attributes }}>
    @if ($label || $valueLabel)
        <div class="mb-2 flex items-baseline justify-between gap-3 text-caption text-ink-muted">
            @if ($label)
                <span>{{ $label }}</span>
            @endif
            @if ($valueLabel)
                <span class="tabular text-ink">{{ $valueLabel }}</span>
            @endif
        </div>
    @endif

    <div
        class="progress"
        role="progressbar"
        aria-valuemin="0"
        aria-valuemax="{{ (int) $max }}"
        aria-valuenow="{{ (int) $value }}"
        @if ($label) aria-label="{{ $label }}" @endif
    >
        <div class="progress-bar" style="--progress: {{ $percent }}%"></div>
    </div>
</div>
