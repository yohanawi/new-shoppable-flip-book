<?php

namespace App\Http\Controllers\Notifications;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\AdminCustomNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class AdminNotificationController extends Controller
{
    public function index(): View
    {
        return view('pages.apps.notifications.admin', [
            'notifications' => DatabaseNotification::query()->latest()->paginate(30),
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'email', 'role']),
        ]);
    }

    public function send(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            'action_url' => ['nullable', 'url', 'max:2048'],
            'action_text' => ['nullable', 'string', 'max:255'],
        ]);

        $users = User::query()->whereIn('id', $validated['user_ids'])->get();

        Notification::send($users, new AdminCustomNotification(
            $validated['title'],
            $validated['message'],
            $validated['action_url'] ?? null,
            $validated['action_text'] ?? null,
        ));

        return back()->with('success', 'Custom notification sent successfully.');
    }
}
