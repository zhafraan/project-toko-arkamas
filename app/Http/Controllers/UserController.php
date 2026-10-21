<?php

namespace App\Http\Controllers;

use App\Models\DeletionLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('name')->get();
        return view('users.index', compact('users'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed|regex:/^(?=.*[A-Za-z])(?=.*\d)/',
            'role'     => 'required|in:owner,kasir',
        ], [
            'password.min'  => 'Password minimal 8 karakter.',
            'password.regex' => 'Password harus mengandung minimal satu huruf dan satu angka.',
        ]);
        $data['password'] = Hash::make($data['password']);
        User::create($data);
        return back()->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email,' . $user->id,
            'role'     => 'required|in:owner,kasir',
            'password' => 'nullable|string|min:8|confirmed|regex:/^(?=.*[A-Za-z])(?=.*\d)/',
        ], [
            'password.min'  => 'Password minimal 8 karakter.',
            'password.regex' => 'Password harus mengandung minimal satu huruf dan satu angka.',
        ]);

        // Cegah pemilik menurunkan perannya sendiri (akan terkunci dari menu pemilik)
        if ($user->id === auth()->id() && $data['role'] !== 'owner') {
            return back()->with('error', 'Anda tidak dapat mengubah peran akun Anda sendiri.');
        }

        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);
        return back()->with('success', 'Data pengguna berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Akun yang sedang digunakan tidak dapat dihapus.');
        }

        if ($user->transactions()->exists() || $user->agentDeliveries()->exists() || $user->debtPayments()->exists()) {
            return back()->with('error', 'Akun "' . $user->name . '" sudah tercatat di riwayat transaksi sehingga tidak bisa dihapus.');
        }

        if ($user->deletionLogs()->exists()) {
            return back()->with('error', 'Akun "' . $user->name . '" memiliki catatan di Riwayat Penghapusan sehingga tidak bisa dihapus (agar jejak audit tetap utuh).');
        }

        if ($user->stockAdjustments()->exists()) {
            return back()->with('error', 'Akun "' . $user->name . '" memiliki catatan di Riwayat Penyesuaian Stok sehingga tidak bisa dihapus (agar jejak audit tetap utuh).');
        }

        DB::transaction(function () use ($user) {
            DeletionLog::record(
                $user,
                "Pengguna {$user->name} ({$user->role})",
                [
                    'name'  => $user->name,
                    'email' => $user->email,
                    'role'  => $user->role,
                ]
            );

            $user->delete();
        });

        return back()->with('success', 'Pengguna berhasil dihapus. Penghapusan tercatat di riwayat penghapusan.');
    }
}
