<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Mail\AdminKontakMasukMail;
use App\Models\User;
use App\Notifications\AdminKontakMasuk;
use Illuminate\Http\Request;
use App\Models\Kontak;
use Illuminate\Support\Facades\Mail;

class KontakController extends Controller
{
    public function index()
    {
        return view('web.Kontak.kontak');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama' => 'required|string|max:100',
            'email' => 'required|email|max:150',
            'subjek' => 'required|string|max:150',
            'pesan' => 'required|string',
        ], [
            'required' => 'Kolom :attribute wajib diisi.',
            'string' => 'Kolom :attribute harus berupa teks.',
            'email' => 'Format :attribute tidak valid.',
            'max' => 'Kolom :attribute maksimal :max karakter.',
        ], [
            'nama' => 'nama',
            'email' => 'email',
            'subjek' => 'subjek',
            'pesan' => 'pesan',
        ]);

        $kontak = Kontak::create($data);

        $admins = User::where('user_role', 'admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new AdminKontakMasuk([
                ...$data,
                'kontak_id' => $kontak->id,
            ]));

            if (!empty($admin->email)) {
                Mail::to($admin->email)->send(new AdminKontakMasukMail($kontak));
            }
        }

        return back()->with('flash', [
            'type' => 'success',
            'action' => 'create',
            'entity' => 'Pesan',
            'title' => 'Pesan Terkirim',
            'message' => 'Pesan kamu berhasil dikirim. Kami akan segera membalas.',
        ]);
    }
}
