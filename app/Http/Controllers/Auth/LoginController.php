<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\UserSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(protected UserSessionService $sessionService) {}

    public function showForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->intended('/');
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'code'     => 'required|string',
            'password' => 'required|string',
        ], [
            'code.required'     => 'Ingresa tu correo o clave de cliente.',
            'password.required' => 'Ingresa tu contraseña.',
        ]);

        $credentials = [
            'code'     => $request->input('code'),
            'password' => $request->input('password'),
        ];

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            $this->sessionService->initSession(Auth::user()->cliente_id);

            return redirect()->intended('/');
        }

        return back()
            ->withInput($request->only('code'))
            ->withErrors(['code' => 'Usuario o contraseña incorrectos.']);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
