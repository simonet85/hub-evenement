<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, Event $event): bool
    {
        return $event->hasMember($user);
    }

    public function update(User $user, Event $event): bool
    {
        return $event->user_id === $user->id;
    }

    public function manageGuests(User $user, Event $event): bool
    {
        return $event->hasMember($user);
    }

    public function manageProgram(User $user, Event $event): bool
    {
        return $event->hasMember($user);
    }

    public function moderateGuestbook(User $user, Event $event): bool
    {
        return $event->hasMember($user);
    }

    public function manageGallery(User $user, Event $event): bool
    {
        return $event->hasMember($user);
    }

    public function manageRegistry(User $user, Event $event): bool
    {
        return $event->hasMember($user);
    }

    public function manageCoOrganizers(User $user, Event $event): bool
    {
        return $event->user_id === $user->id;
    }

    public function delete(User $user, Event $event): bool
    {
        return $event->user_id === $user->id;
    }
}
