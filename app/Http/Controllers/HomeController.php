<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $demoToken = config('hub.demo_invitation_token');

        return view('home', [
            'demoInvitationUrl' => is_string($demoToken) && $demoToken !== ''
                ? route('demo.invitation')
                : null,
        ]);
    }
}
