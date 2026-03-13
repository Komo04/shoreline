<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $notifications = $user->notifications()->latest()->paginate(15);

        // IMPORTANT: view kamu pakai yang mana? (lihat bagian #3)
        return view('web.Notifikasi.notifikasi', compact('notifications'));
    }

    public function markAsRead($id)
    {
        $notif = Auth::user()->notifications()->where('id', $id)->firstOrFail();
        $notif->markAsRead();
        return back();
    }

    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
        return back();
    }
}
