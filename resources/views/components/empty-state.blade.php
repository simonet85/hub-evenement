@props(['title', 'description' => null, 'script' => null])

<div {{ $attributes->merge(['class' => 'empty-state']) }}>
    @if ($script)
        <p class="empty-state-script" aria-hidden="true">{{ $script }}</p>
    @endif

    <p @class(['font-display text-heading text-ink', 'mt-3' => $script])>{{ $title }}</p>

    @if ($description)
        <p class="mx-auto mt-2 max-w-prose text-body text-ink-muted">{{ $description }}</p>
    @endif

    @if (trim((string) $slot) !== '')
        <div class="mt-5 flex flex-wrap justify-center gap-2">{{ $slot }}</div>
    @endif
</div>
