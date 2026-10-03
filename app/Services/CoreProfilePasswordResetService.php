<?php

namespace App\Services;

use App\Mail\ProfilePasswordResetLinkMail;
use App\Models\User;
use App\Models\UserActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Throwable;

class CoreProfilePasswordResetService
{
    public function sendResetLink(string $login, Request $request): void
    {
        $user = $this->findUserForPasswordReset($login);

        if (! $user || ! $user->active || blank($user->email)) {
            return;
        }

        $token = PasswordBroker::broker()->createToken($user);
        $resetPath = route('profile.password.reset.edit', [
            'token' => $token,
            'email' => $user->email,
        ], false);
        $resetUrl = rtrim((string) config('app.url'), '/').$resetPath;

        try {
            Mail::to($user->email)->send(new ProfilePasswordResetLinkMail(
                $user,
                $resetUrl,
                (int) config('auth.passwords.users.expire', 60),
            ));
        } catch (Throwable $exception) {
            PasswordBroker::broker()->deleteToken($user);

            $this->logDelivery($user, $request, 'profile.password_reset_email_failed', 'failed', [
                'exception' => $exception::class,
            ]);

            throw $exception;
        }

        $this->logDelivery($user, $request, 'profile.password_reset_requested', 'sent');
    }

    private function logDelivery(User $user, Request $request, string $action, string $status, array $meta = []): void
    {
        UserActivityLog::create([
            'user_id' => $user->id,
            'action' => $action,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'meta' => array_merge([
                'source' => 'profile_portal',
                'delivery' => 'email',
                'delivery_status' => $status,
                'mailer' => (string) config('mail.default'),
            ], $meta),
        ]);
    }

    public function findUserForPasswordReset(string $login): ?User
    {
        $normalized = trim($login);
        $email = strtolower($normalized);

        $matches = User::query()
            ->where(function ($query) use ($normalized, $email): void {
                $query->whereRaw('LOWER(TRIM(email)) = ?', [$email])
                    ->orWhereRaw('LOWER(TRIM(alternate_email)) = ?', [$email])
                    ->orWhere('username', $normalized)
                    ->orWhere('identity_number', $normalized)
                    ->orWhere('phone', $normalized)
                    ->orWhereHas('student', fn ($student) => $student->where(fn ($profile) => $profile
                        ->where('student_number', $normalized)
                        ->orWhere('phone', $normalized)
                        ->orWhereRaw('LOWER(TRIM(email)) = ?', [$email])))
                    ->orWhereHas('lecturer', function ($lecturer) use ($normalized, $email): void {
                        $lecturer->where(fn ($profile) => $profile
                            ->where('lecturer_number', $normalized)
                            ->orWhere('nidn', $normalized)
                            ->orWhere('nidk', $normalized)
                            ->orWhere('nip', $normalized)
                            ->orWhere('nuptk', $normalized)
                            ->orWhere('phone', $normalized)
                            ->orWhereRaw('LOWER(TRIM(email)) = ?', [$email]));
                    })
                    ->orWhereHas('employee', fn ($employee) => $employee->where(fn ($profile) => $profile
                        ->where('employee_number', $normalized)
                        ->orWhere('phone', $normalized)
                        ->orWhereRaw('LOWER(TRIM(email)) = ?', [$email])))
                    ->orWhereHas('externalPerson', fn ($external) => $external->where(fn ($profile) => $profile
                        ->where('phone', $normalized)
                        ->orWhereRaw('LOWER(TRIM(email)) = ?', [$email])));
            })
            ->limit(2)
            ->get();

        return $matches->count() === 1 ? $matches->first() : null;
    }
}
