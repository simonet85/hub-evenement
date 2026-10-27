<?php

namespace App\Jobs;

use App\Mail\ReminderEmail;
use App\Models\Reminder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendReminderEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public Reminder $reminder) {}

    public function handle(): void
    {
        $guest = $this->reminder->guest;
        $guest->loadMissing('event');

        if (! $guest->canReceiveReminder($this->reminder->id)) {
            return;
        }

        Mail::to($guest->email)->send(new ReminderEmail($guest));

        $this->reminder->forceFill(['sent_at' => now()])->save();
    }
}
