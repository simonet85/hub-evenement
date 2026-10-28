<?php

namespace App\Jobs;

use App\Models\Photo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use RuntimeException;

class GeneratePhotoDerivatives implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public Photo $photo) {}

    public function handle(): void
    {
        $disk = Storage::disk('local');

        if (! $disk->exists($this->photo->path)) {
            throw new RuntimeException('Photo introuvable : '.$this->photo->path);
        }

        $manager = new ImageManager(new Driver);
        $web = $manager->read($disk->path($this->photo->path))
            ->orient()
            ->scaleDown(width: 1600, height: 1600);
        $thumb = (clone $web)->scaleDown(width: 480, height: 480);

        $png = $this->photo->mime === 'image/png';
        $base = preg_replace('/\.[^.]+$/', '', $this->photo->path);
        $extension = $png ? 'png' : 'jpg';
        $webPath = $base.'-web.'.$extension;
        $thumbPath = $base.'-thumb.'.$extension;

        $disk->put($webPath, ($png ? $web->toPng() : $web->toJpeg(82))->toString());
        $disk->put($thumbPath, ($png ? $thumb->toPng() : $thumb->toJpeg(82))->toString());

        $this->photo->forceFill([
            'web_path' => $webPath,
            'thumb_path' => $thumbPath,
            'width' => $web->width(),
            'height' => $web->height(),
        ])->save();
    }
}
