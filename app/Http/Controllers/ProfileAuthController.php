<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\CoreProfilePortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProfileAuthController extends Controller
{
    public function __construct(
        private readonly CoreProfilePortalService $profiles,
    ) {
    }

    public function showLoginForm(Request $request): View|RedirectResponse
    {
        if ($request->user()) {
            return redirect()->route('profile.show');
        }

        return view('profile.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $user = $this->findUserForLogin($validated['login']);

        if (! $user || ! $user->active || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'login' => 'Login gagal. Periksa username dan password.',
            ]);
        }

        Auth::login($user, (bool) $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended($this->postLoginPath($user));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('profile.login')
            ->with('status', 'Anda sudah keluar dari Profile Portal.');
    }

    private function findUserForLogin(string $login): ?User
    {
        $normalized = trim($login);

        return User::query()
            ->where('username', $normalized)
            ->orWhere('email', $normalized)
            ->orWhere('identity_number', $normalized)
            ->first();
    }

    private function postLoginPath(User $user): string
    {
        if ($user->must_change_password) {
            return route('profile.password.edit', absolute: false);
        }

        if (! $this->profiles->isComplete($user)) {
            return route('profile.edit', absolute: false);
        }

        return route('profile.show', absolute: false);
    }
}
