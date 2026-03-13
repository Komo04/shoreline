<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('users')->where('user_role', 'customer');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('no_telp', 'like', "%{$s}%");
            });
        }

        $customers = $query->latest()->paginate(10)->withQueryString();

        return view('Admin.Customer.customer', compact('customers'));
    }

    public function toggleActive($id)
    {
        // ambil user
        $user = DB::table('users')
            ->where('id', $id)
            ->where('user_role', 'customer')
            ->first();

        if (! $user) {
            return back()->with('flash', [
  'type' => 'error',
  'action' => 'notfound',
  'entity' => 'User',
  'detail' => 'User tidak ditemukan.',
  'timer' => 3000,
]);
        }

        // proteksi: jangan nonaktifkan akun sendiri (admin yang sedang login)
        if ((int) Auth::id() === (int) $user->id) {
           return back()->with('flash', [
  'type' => 'warning',
  'action' => 'forbidden',
  'entity' => 'Customer',
  'detail' => 'Tidak bisa menonaktifkan akun sendiri.',
  'timer' => 3200,
]);
        }

        // toggle
        $newStatus = ! (bool) ($user->is_active ?? 1);

        DB::table('users')->where('id', $id)->update([
            'is_active' => $newStatus,
            'updated_at' => now(),
        ]);

       return back()->with('flash', [
  'type' => 'success',
  'action' => 'update',
  'entity' => 'Status Customer',
]);
    }

    public function updateRole(Request $request, $id)
    {
        $request->validate([
            'user_role' => ['required', Rule::in(['admin', 'customer'])],
        ]);

        $user = DB::table('users')->where('id', $id)->first();
        if (! $user) {
            return back()->with('flash', [
                'type' => 'error',
                'action' => 'notfound',
                'entity' => 'User',
                'detail' => 'User tidak ditemukan.',
                'timer' => 3000,
            ]);
        }

        if (($user->user_role ?? null) !== 'customer') {
            return back()->with('flash', [
                'type' => 'warning',
                'action' => 'forbidden',
                'entity' => 'Role',
                'detail' => 'Perubahan role dari menu ini hanya untuk akun customer.',
                'timer' => 3200,
            ]);
        }

        if ((int) Auth::id() === (int) $user->id && $request->user_role !== 'admin') {
            return back()->with('flash', [
                'type' => 'warning',
                'action' => 'forbidden',
                'entity' => 'Role',
                'detail' => 'Tidak bisa menurunkan role akun sendiri.',
                'timer' => 3200,
            ]);
        }

        DB::table('users')->where('id', $id)->update([
            'user_role' => $request->user_role,
            'updated_at' => now(),
        ]);

       return back()->with('flash', [
  'type' => 'success',
  'action' => 'update',
  'entity' => 'Role',
]);
    }
    public function edit($id)
    {
        $customer = DB::table('users')
            ->where('id', $id)
            ->where('user_role', 'customer')
            ->first();

        if (! $customer) {
            return back()->with('flash', [
                'type' => 'error',
                'action' => 'notfound',
                'entity' => 'Customer',
                'message' => 'Customer tidak ditemukan.',
            ]);
        }

        return view('Admin.Customer.edit', compact('customer'));
    }

    public function update(Request $request, $id)
    {
        if ($request->input('intent') === 'delete') {
            return $this->destroy($id);
        }

        $customer = DB::table('users')
            ->where('id', $id)
            ->where('user_role', 'customer')
            ->first();

        if (! $customer) {
            return back()->with('flash', [
                'type' => 'error',
                'action' => 'notfound',
                'entity' => 'Customer',
                'message' => 'Customer tidak ditemukan.',
            ]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $id],
            'no_telp' => ['required', 'regex:/^[0-9]+$/', 'digits_between:8,15'],
            'is_active' => ['nullable'],
        ], [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar.',
            'no_telp.required' => 'Nomor telepon wajib diisi.',
            'no_telp.regex' => 'Nomor telepon hanya boleh berisi angka.',
            'no_telp.digits_between' => 'Nomor telepon harus 8 sampai 15 digit.',
        ]);

        DB::table('users')->where('id', $id)->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'no_telp' => $data['no_telp'],
            'is_active' => $request->has('is_active') ? 1 : 0,
            'updated_at' => now(),
        ]);

        return redirect('/admin/customer')->with('flash', [
            'type' => 'success',
            'action' => 'update',
            'entity' => 'Customer',
        ]);
    }

    public function destroy($id)
    {
        $customer = DB::table('users')
            ->where('id', $id)
            ->where('user_role', 'customer')
            ->first();

        if (! $customer) {
            return back()->with('flash', [
                'type' => 'error',
                'action' => 'notfound',
                'entity' => 'Customer',
                'message' => 'Customer tidak ditemukan.',
            ]);
        }

        if ((int) Auth::id() === (int) $customer->id) {
            return back()->with('flash', [
                'type' => 'warning',
                'action' => 'forbidden',
                'entity' => 'Customer',
                'message' => 'Akun yang sedang digunakan tidak bisa dihapus.',
            ]);
        }

        $this->deleteCustomerData($customer);

        return redirect('/admin/customer')->with('flash', [
            'type' => 'success',
            'action' => 'delete',
            'entity' => 'Customer',
            'message' => 'Customer berhasil dihapus.',
        ]);
    }

    private function deleteCustomerData(object $customer): void
    {
        DB::transaction(function () use ($customer): void {
            DB::table('notifications')
                ->where('notifiable_type', 'App\\Models\\User')
                ->where('notifiable_id', $customer->id)
                ->delete();

            DB::table('sessions')
                ->where('user_id', $customer->id)
                ->delete();

            DB::table('password_reset_tokens')
                ->where('email', $customer->email)
                ->delete();

            DB::table('users')
                ->where('id', $customer->id)
                ->delete();
        });
    }
}
