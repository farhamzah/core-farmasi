<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserActivityLog;
use App\Services\CoreProfilePasswordResetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProfilePasswordResetController extends Controller
{
    public function requestForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('profile.show');
        }

        return view('profile.forgot-password');
    }

    public function sendLink(Request $request, CoreProfilePasswordResetService $passwordResetService): RedirectResponse
    {
        $validated = $request->validate([
            'login' => ['required', 'string', 'max:255'],
        ]);

        try {
            $passwordResetService->sendResetLink($validated['login'], $request);
        } catch (\Throwable $exception) {
            report($exception);
        }

        return back()->with('status', 'Permintaan reset password sudah diproses. Jika data cocok dengan akun aktif, link reset sudah dikirim ke email terdaftar. Silakan cek Inbox, Spam, atau Promotions.');
    }

    public function resetForm(Request $request, string $token): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('profile.show');
        }

        return view('profile.reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = PasswordBroker::broker()->reset(
            $validated,
            function (User $user, string $password) use ($request): void {
                if (! $user->active) {
                    throw ValidationException::withMessages([
                        'email' => 'Akun belum aktif. Hubungi Admin Core.',
                    ]);
                }

                $user->forceFill([
                    'password' => Hash::make($password),
                    'must_change_password' => false,
                    'password_changed_at' => now(),
                    'remember_token' => Str::random(60),
                ])->save();

                UserActivityLog::create([
                    'user_id' => $user->id,
                    'action' => 'profile.password_reset_completed',
                    'ip_address' => $request->ip(),
                    'user_agent' => substr((string) $request->userAgent(), 0, 255),
                    'meta' => [
                        'source' => 'profile_portal_email_reset',
                    ],
                ]);

                Auth::login($user);
                $request->session()->regenerate();
            }
        );

        if ($status !== PasswordBroker::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => 'Link reset password tidak valid atau sudah kedaluwarsa.',
            ]);
        }

        return redirect()->route('profile.edit')->with('status', 'Password berhasil direset. Silakan lengkapi profil jika masih ada data yang kurang.');
    }
}
