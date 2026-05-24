<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;

class CoreInitialPasswordService
{
    public function resolveBirthDateForUser(User $user): DateTimeInterface|string|null
    {
        $user->loadMissing(['student', 'lecturer', 'employee']);

        return $user->student?->getAttribute('birth_date')
            ?? $user->lecturer?->getAttribute('birth_date')
            ?? $user->employee?->getAttribute('birth_date');
    }

    public function generateFromBirthDate(DateTimeInterface|string|null $birthDate): string
    {
        if (blank($birthDate)) {
            throw new InvalidArgumentException('Birth date is required to generate an initial password.');
        }

        $date = $birthDate instanceof DateTimeInterface
            ? Carbon::instance($birthDate)
            : $this->parseBirthDate($birthDate);

        return $date->format(config('core_identity.initial_password_format', 'd/m/Y'));
    }

    public function hashFromBirthDate(DateTimeInterface|string|null $birthDate): string
    {
        return Hash::make($this->generateFromBirthDate($birthDate));
    }

    public function setInitialPassword(User $user, DateTimeInterface|string|null $birthDate, ?User $resetBy = null): void
    {
        $user->forceFill([
            'password' => $this->hashFromBirthDate($birthDate),
            'must_change_password' => true,
            'password_changed_at' => null,
            'last_password_reset_at' => now(),
            'password_reset_by' => $resetBy?->id,
        ])->save();
    }

    public function setInitialPasswordFromUserBirthDate(User $user, ?User $resetBy = null): void
    {
        $this->setInitialPassword($user, $this->resolveBirthDateForUser($user), $resetBy);
    }

    protected function parseBirthDate(string $birthDate): Carbon
    {
        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $birthDate);
            } catch (\Throwable) {
                continue;
            }

            if ($date && $date->format($format) === $birthDate) {
                return $date;
            }
        }

        return Carbon::parse($birthDate);
    }
}
