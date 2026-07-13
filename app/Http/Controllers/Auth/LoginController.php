<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Utilisateur;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): RedirectResponse|View
    {
        if (Auth::check()) {
            return redirect('/');
        }

        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $login = trim($credentials['login']);

        $utilisateur = Utilisateur::query()
            ->where('login', $login)
            ->first();

        if (
            $utilisateur
            && $utilisateur->isActive()
            && $utilisateur->checkPassword($credentials['password'])
        ) {
            Auth::login($utilisateur);
            $request->session()->regenerate();

            $homeRoute = $utilisateur->homeRouteName();

            return $homeRoute
                ? redirect()->route($homeRoute)
                : redirect('/');
        }

        return back()->withErrors([
            'login' => 'Identifiant ou mot de passe incorrect.',
        ])->onlyInput('login');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
