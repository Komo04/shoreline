<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;


class AdminNotificationController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $notifications = $user->notifications()->latest()->paginate(20);

        return view('Admin.Notifikasi.notif', compact('notifications'));
    }

    public function markAsRead(string $id)
    {
        $n = auth()->user()->notifications()->where('id', $id)->firstOrFail();
        $n->markAsRead();

        return back();
    }

    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();
        return back();
    }
}
