<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

class AuthController extends Controller
{
    public function showLogin(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        // Ambil email dari cookie jika ada
        $rememberedEmail = $request->cookie('remembered_email', '');

        return view('auth.login', compact('rememberedEmail'));
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            $user = Auth::user();

            $response = redirect()->intended(route('dashboard'))
                ->with('success', "Selamat datang kembali, {$user->name}!");

            // Atur cookie remembered_email
            if ($remember) {
                $response->cookie('remembered_email', $request->email, 60 * 24 * 30); // 30 hari
            } else {
                $response->cookie(Cookie::forget('remembered_email'));
            }

            return $response;
        }

        return back()->withErrors([
            'email' => 'Email atau password yang Anda masukkan salah.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('info', 'Anda telah berhasil logout dari sistem.');
    }
}
