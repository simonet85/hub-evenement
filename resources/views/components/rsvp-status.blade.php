@props(['guest'])

@php
    $status = $guest->rsvp_status;
    $icon = match ($status) {
        \App\Enums\RsvpStatus::Pending => 'clock',
        \App\Enums\RsvpStatus::Attending => 'check',
        \App\Enums\RsvpStatus::Declined => 'x',
    };
@endphp

<x-status-pill :tone="$status->tone()" :icon="$icon">{{ $status->label() }}@if ($status === \App\Enums\RsvpStatus::Attending && $guest->rsvp_seats) · {{ $guest->rsvp_seats }} pers.@endif</x-status-pill>

@if ($guest->rsvp_is_late)
    <x-status-pill tone="warning" icon="clock">Hors délai</x-status-pill>
@endif
