<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class AdminNotificationController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $notifications = $user->notifications()->latest()->paginate(20);

        return view('Admin.Notifikasi.notif', compact('notifications'));
    }

    public function markAsRead(string $id)
    {
        $n = Auth::user()->notifications()->where('id', $id)->firstOrFail();
        $n->markAsRead();

        return back();
    }

    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
        return back();
    }
}
