<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    public function showLogin()
    {
        return view('web.auth.login');
    }

    public function showRegister()
    {
        return view('web.auth.register');
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();
        
        if (Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']]) ||
            Auth::attempt(['username' => $credentials['email'], 'password' => $credentials['password']])) {
            $request->session()->regenerate();
            return redirect()->intended('/home');
        }

        return back()->withErrors([
            'email' => 'Email/Username atau password salah.',
        ])->onlyInput('email');
    }

    public function register(RegisterRequest $request)
    {
        $user = $this->authService->register($request->validated());
        Auth::login($user);
        
        return redirect('/home');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
