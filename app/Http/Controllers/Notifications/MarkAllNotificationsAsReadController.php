<?php

declare(strict_types=1);

namespace App\Http\Controllers\Notifications;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class MarkAllNotificationsAsReadController
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $user->unreadNotifications->markAsRead();

        return back();
    }
}
