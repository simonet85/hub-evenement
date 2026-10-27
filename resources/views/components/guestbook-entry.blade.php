@props(['entry', 'event'])

<article class="rounded-sm border border-line bg-surface-300 p-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <p class="text-caption text-ink-muted">
            {{ $entry->author_name }}{{ $entry->author_name !== $entry->guest->name ? ' · foyer '.$entry->guest->name : '' }}
        </p>
        <span class="status-pill status-pill-{{ $entry->status->tone() }}">{{ $entry->status->label() }}</span>
    </div>

    <p class="mt-3 whitespace-pre-line text-body text-ink">{{ $entry->message }}</p>

    <p class="mt-3 text-caption text-ink-muted">
        Déposé le {{ $entry->created_at->translatedFormat('j F Y \à H\hi') }}
        @if ($entry->moderated_at)
            · {{ $entry->status->label() }} par {{ $entry->moderator->name ?? 'un organisateur' }} le {{ $entry->moderated_at->translatedFormat('j F Y \à H\hi') }}
        @endif
    </p>

    <div class="mt-3 flex flex-wrap gap-2">
        @if ($entry->status !== \App\Enums\GuestbookStatus::Approved)
            <form method="POST" action="{{ route('guestbook.approve', [$event, $entry]) }}">
                @csrf
                <button type="submit" class="btn btn-primary">Publier</button>
            </form>
        @endif

        @if ($entry->status === \App\Enums\GuestbookStatus::Approved)
            <form method="POST" action="{{ route('guestbook.hide', [$event, $entry]) }}">
                @csrf
                <button type="submit" class="btn btn-secondary">Masquer</button>
            </form>
        @endif

        @if ($entry->status !== \App\Enums\GuestbookStatus::Rejected)
            <form method="POST" action="{{ route('guestbook.reject', [$event, $entry]) }}">
                @csrf
                <button type="submit" class="btn btn-secondary">Rejeter</button>
            </form>
        @endif
    </div>
</article>
