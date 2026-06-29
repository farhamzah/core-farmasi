<?php

namespace App\Services;

use App\Mail\ProfilePasswordResetLinkMail;
use App\Models\User;
use App\Models\UserActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password as PasswordBroker;

class CoreProfilePasswordResetService
{
    public function sendResetLink(string $login, Request $request): void
    {
        $user = $this->findUserForPasswordReset($login);

        if (! $user || ! $user->active || blank($user->email)) {
            return;
        }

        $token = PasswordBroker::broker()->createToken($user);
        $resetUrl = route('profile.password.reset.edit', [
            'token' => $token,
            'email' => $user->email,
        ]);

        Mail::to($user->email)->send(new ProfilePasswordResetLinkMail(
            $user,
            $resetUrl,
            (int) config('auth.passwords.users.expire', 60),
        ));

        UserActivityLog::create([
            'user_id' => $user->id,
            'action' => 'profile.password_reset_requested',
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'meta' => [
                'source' => 'profile_portal',
                'delivery' => 'email',
            ],
        ]);
    }

    public function findUserForPasswordReset(string $login): ?User
    {
        $normalized = trim($login);
        $email = strtolower($normalized);

        return User::query()
            ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
            ->orWhere('username', $normalized)
            ->orWhere('identity_number', $normalized)
            ->orWhereHas('student', fn ($query) => $query->where('student_number', $normalized))
            ->orWhereHas('lecturer', function ($query) use ($normalized): void {
                $query->where('lecturer_number', $normalized)
                    ->orWhere('nidn', $normalized)
                    ->orWhere('nidk', $normalized)
                    ->orWhere('nip', $normalized)
                    ->orWhere('nuptk', $normalized);
            })
            ->orWhereHas('employee', fn ($query) => $query->where('employee_number', $normalized))
            ->first();
    }
}
