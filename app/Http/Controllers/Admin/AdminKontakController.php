<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\KontakReplyMail;
use App\Models\Kontak;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class AdminKontakController extends Controller
{
    public function index()
    {
        $kontaks = Kontak::latest()->paginate(10);
        return view('Admin.Kontak.kontak', compact('kontaks'));
    }

    public function show(Kontak $kontak)
    {
        if (!$kontak->dibaca_pada) {
            $kontak->update(['dibaca_pada' => now()]);
        }

        return view('Admin.Kontak.show', compact('kontak'));
    }

    public function replyForm(Kontak $kontak)
    {
        // biar status dibaca juga
        if (!$kontak->dibaca_pada) {
            $kontak->update(['dibaca_pada' => now()]);
        }

        return view('Admin.Kontak.reply', compact('kontak'));
    }

    public function replySend(Request $request, Kontak $kontak)
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
        ]);

        Mail::to($kontak->email)->send(
            new KontakReplyMail($kontak, $data['subject'], $data['message'])
        );

        // opsional: simpan status dibalas
        $kontak->update([
            'dibalas_pada' => now(),
            'balasan_subjek' => $data['subject'],
        ]);

        return redirect()
            ->route('admin.kontak.show', $kontak->id)
            ->with('flash', [
                'type' => 'success',
                'action' => 'reply',
                'entity' => 'Balasan',
            ]);
    }
}
