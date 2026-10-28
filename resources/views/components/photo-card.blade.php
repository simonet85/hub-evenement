@props(['photo', 'event'])

<article id="photo-{{ $photo->id }}" class="rounded-sm border border-line bg-surface-300 p-4">
    <div class="grid gap-4 sm:grid-cols-[180px_1fr]">
        <a
            href="{{ route('gallery.preview', [$event, $photo, 'web']) }}"
            target="_blank"
            rel="noopener"
            class="block self-start"
        >
            <img
                src="{{ route('gallery.preview', [$event, $photo]) }}"
                alt="{{ $photo->caption ?: $photo->original_name }}"
                loading="lazy"
                decoding="async"
                class="aspect-square w-full rounded-sm border border-line object-cover"
            >
            <span class="sr-only"> (nouvelle fenêtre)</span>
        </a>

        <div class="min-w-0">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <p class="truncate text-label text-ink">{{ $photo->original_name }}</p>
                <span class="status-pill status-pill-{{ $photo->status->tone() }}">{{ $photo->status->label() }}</span>
            </div>

            <p class="mt-1 text-caption text-ink-muted">
                Déposée le {{ $photo->created_at->translatedFormat('j F Y \à H\hi') }}
                @if ($photo->uploader)
                    · par {{ $photo->uploader->name }}
                @endif
                @if ($photo->width && $photo->height)
                    · {{ $photo->width }} × {{ $photo->height }} px
                @endif
            </p>

            <p class="text-caption text-ink-muted">
                {{ $photo->hasDerivatives() ? 'Vignette et version web prêtes.' : 'Traitement des dérivés en cours…' }}
            </p>

            @if ($photo->moderated_at)
                <p class="mt-1 text-caption text-ink-muted">
                    {{ $photo->status->label() }} par {{ $photo->moderator->name ?? 'un organisateur' }}
                    le {{ $photo->moderated_at->translatedFormat('j F Y \à H\hi') }}
                </p>
            @endif

            <form method="POST" action="{{ route('gallery.caption', [$event, $photo]) }}" class="mt-3 flex flex-wrap items-end gap-2">
                @csrf
                @method('PUT')

                <div class="min-w-0 flex-1">
                    <label for="caption_{{ $photo->id }}" class="label">
                        Légende <span class="font-normal text-ink-muted">(facultative)</span>
                    </label>
                    <input
                        id="caption_{{ $photo->id }}"
                        name="caption"
                        type="text"
                        maxlength="120"
                        value="{{ $photo->caption }}"
                        placeholder="Un mot sur cette photo…"
                        class="field mt-1"
                    >
                </div>

                <button type="submit" class="btn btn-secondary">Enregistrer</button>
            </form>

            <div class="mt-3 flex flex-wrap items-center gap-3">
                @if ($photo->status !== \App\Enums\PhotoStatus::Approved)
                    <form method="POST" action="{{ route('gallery.approve', [$event, $photo]) }}">
                        @csrf
                        <button type="submit" class="btn btn-primary">Publier</button>
                    </form>
                @endif

                @if ($photo->status === \App\Enums\PhotoStatus::Approved)
                    <form method="POST" action="{{ route('gallery.hide', [$event, $photo]) }}">
                        @csrf
                        <button type="submit" class="btn btn-secondary">Masquer</button>
                    </form>
                @endif

                <form
                    method="POST"
                    action="{{ route('gallery.reject', [$event, $photo]) }}"
                    onsubmit="return confirm('Supprimer définitivement cette photo et ses fichiers ?')"
                >
                    @csrf
                    <button type="submit" class="text-caption font-semibold text-danger hover:underline">
                        Supprimer
                    </button>
                </form>
            </div>
        </div>
    </div>
</article>
