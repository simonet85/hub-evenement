<?php

namespace App\Http\Controllers;

use App\Enums\PhotoStatus;
use App\Jobs\GeneratePhotoDerivatives;
use App\Models\Event;
use App\Models\Guest;
use App\Models\Photo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class GalleryController extends Controller
{
    public function guest(string $token): View
    {
        $guest = Guest::query()
            ->with('event')
            ->where('invitation_token', $token)
            ->firstOrFail();

        $photos = $guest->event->photos()
            ->approved()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(12, ['*'], 'galerie_page');

        return view('gallery.show', [
            'guest' => $guest,
            'event' => $guest->event,
            'photos' => $photos,
        ]);
    }

    public function serve(Photo $photo, string $variant): Response
    {
        abort_unless(in_array($variant, ['thumb', 'web'], true), 404);
        abort_unless($photo->isApproved(), 404);

        $path = $photo->derivativePath($variant);
        abort_if($path === null || ! Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'private, max-age=1800',
        ]);
    }

    public function index(Event $event): View
    {
        Gate::authorize('manageGallery', $event);

        $photos = $event->photos()
            ->with(['uploader:id,name', 'moderator:id,name'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        return view('gallery.index', [
            'event' => $event,
            'pending' => $photos->where('status', PhotoStatus::Pending)->values(),
            'approved' => $photos->where('status', PhotoStatus::Approved)->values(),
            'hidden' => $photos->where('status', PhotoStatus::Hidden)->values(),
        ]);
    }

    public function store(Request $request, Event $event): RedirectResponse
    {
        Gate::authorize('manageGallery', $event);

        $validator = Validator::make($request->all(), [
            'photos' => ['required', 'array', 'min:1', 'max:10'],
            'photos.*' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
        ], [
            'photos.required' => 'Sélectionnez au moins une photo.',
            'photos.min' => 'Sélectionnez au moins une photo.',
            'photos.max' => 'Envoyez au plus 10 photos à la fois.',
            'photos.*.required' => 'Chaque fichier doit être une image.',
            'photos.*.file' => 'Chaque fichier doit être une image.',
            'photos.*.image' => 'Chaque fichier doit être une image lisible.',
            'photos.*.mimes' => 'Formats acceptés : JPEG ou PNG.',
            'photos.*.max' => 'Chaque photo doit faire 10 Mo maximum.',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('gallery.index', $event)
                ->withErrors($validator)
                ->withInput($request->input());
        }

        $count = 0;

        foreach ($request->file('photos') as $file) {
            $mime = $file->getMimeType();
            $png = $mime === 'image/png';
            $path = $file->storeAs(
                'events/'.$event->id.'/photos',
                Str::uuid().'.'.($png ? 'png' : 'jpg'),
                'local',
            );

            [$width, $height] = getimagesize($file->getRealPath());

            $photo = $event->photos()->create([
                'uploaded_by' => $request->user()->id,
                'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
                'path' => $path,
                'mime' => $png ? 'image/png' : 'image/jpeg',
                'size' => $file->getSize(),
                'width' => $width,
                'height' => $height,
                'status' => PhotoStatus::Pending,
            ]);

            GeneratePhotoDerivatives::dispatch($photo);
            $count++;
        }

        $status = $count === 1
            ? '1 photo ajoutée. Elle sera visible après validation.'
            : $count.' photos ajoutées. Elles seront visibles après validation.';

        return redirect()
            ->route('gallery.index', $event)
            ->with('status', $status);
    }

    public function preview(Event $event, Photo $photo, string $variant = 'thumb'): Response
    {
        Gate::authorize('manageGallery', $event);
        $this->ensurePhotoBelongsTo($event, $photo);

        abort_unless(in_array($variant, ['thumb', 'web', 'original'], true), 404);

        $candidates = $variant === 'original'
            ? ['original']
            : [$variant, 'web', 'thumb', 'original'];

        $path = collect($candidates)
            ->map(fn (string $candidate) => $photo->derivativePath($candidate))
            ->filter(fn (?string $candidate) => $candidate !== null && Storage::disk('local')->exists($candidate))
            ->first();

        abort_if($path === null, 404);

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'no-store, private',
        ]);
    }

    public function caption(Request $request, Event $event, Photo $photo): RedirectResponse
    {
        Gate::authorize('manageGallery', $event);
        $this->ensurePhotoBelongsTo($event, $photo);

        $validator = Validator::make($request->all(), [
            'caption' => ['nullable', 'string', 'max:120'],
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('gallery.index', $event)
                ->withFragment('photo-'.$photo->id)
                ->withErrors($validator)
                ->withInput();
        }

        $caption = trim((string) ($request->input('caption') ?? ''));

        $photo->forceFill(['caption' => $caption === '' ? null : $caption])->save();

        return redirect()
            ->route('gallery.index', $event)
            ->withFragment('photo-'.$photo->id)
            ->with('status', 'Légende enregistrée.');
    }

    public function approve(Request $request, Event $event, Photo $photo): RedirectResponse
    {
        return $this->moderate($request, $event, $photo, PhotoStatus::Approved, 'Photo publiée.');
    }

    public function hide(Request $request, Event $event, Photo $photo): RedirectResponse
    {
        return $this->moderate($request, $event, $photo, PhotoStatus::Hidden, 'Photo masquée.');
    }

    public function reject(Request $request, Event $event, Photo $photo): RedirectResponse
    {
        Gate::authorize('manageGallery', $event);
        $this->ensurePhotoBelongsTo($event, $photo);

        $disk = Storage::disk('local');

        foreach (array_filter([$photo->path, $photo->thumb_path, $photo->web_path]) as $path) {
            $disk->delete($path);
        }

        $photo->delete();

        return redirect()
            ->route('gallery.index', $event)
            ->with('status', 'Photo supprimée.');
    }

    private function moderate(Request $request, Event $event, Photo $photo, PhotoStatus $status, string $message): RedirectResponse
    {
        Gate::authorize('manageGallery', $event);
        $this->ensurePhotoBelongsTo($event, $photo);

        $photo->forceFill([
            'status' => $status,
            'moderated_by' => $request->user()->id,
            'moderated_at' => now(),
        ])->save();

        return redirect()
            ->route('gallery.index', $event)
            ->with('status', $message);
    }

    private function ensurePhotoBelongsTo(Event $event, Photo $photo): void
    {
        abort_unless($photo->event_id === $event->id, 404);
    }
}
