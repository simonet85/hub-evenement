@props(['guest'])

@php
    $status = $guest->rsvp_status;
@endphp

<span class="status-pill status-pill-{{ $status->tone() }}">
    @switch($status)
        @case(\App\Enums\RsvpStatus::Attending)
            <svg class="h-3 w-3" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                <path d="M3 8.5 6.5 12 13 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            @break
        @case(\App\Enums\RsvpStatus::Declined)
            <svg class="h-3 w-3" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                <path d="M4 4l8 8M12 4l-8 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
            @break
        @default
            <svg class="h-3 w-3" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                <circle cx="8" cy="8" r="5.5" stroke="currentColor" stroke-width="1.5"/>
                <path d="M8 5v3.5l2 1.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
    @endswitch

    {{ $status->label() }}@if ($status === \App\Enums\RsvpStatus::Attending && $guest->rsvp_seats) · {{ $guest->rsvp_seats }} pers.@endif
</span>

@if ($guest->rsvp_is_late)
    <span class="status-pill status-pill-warning">Hors délai</span>
@endif
