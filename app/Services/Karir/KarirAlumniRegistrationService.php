<?php

namespace App\Services\Karir;

use App\Models\CareerAlumniRegistration;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class KarirAlumniRegistrationService
{
    public function register(array $data): CareerAlumniRegistration
    {
        return DB::transaction(function () use ($data): CareerAlumniRegistration {
            $nim = trim($data['student_number']);
            $email = strtolower(trim($data['personal_email']));

            $sameClaim = CareerAlumniRegistration::query()
                ->where('student_number', $nim)
                ->where('personal_email', $email)
                ->whereIn('status', [CareerAlumniRegistration::STATUS_PENDING, CareerAlumniRegistration::STATUS_MANUAL_REVIEW])
                ->lockForUpdate()
                ->first();

            if ($sameClaim) {
                return $sameClaim;
            }

            $nimConflict = CareerAlumniRegistration::query()->where('student_number', $nim)->exists();
            $emailConflict = CareerAlumniRegistration::query()->where('personal_email', $email)->exists();
            $matchingUsers = User::query()
                ->where('email', $email)
                ->orWhere('username', $nim)
                ->orWhere('identity_number', $nim)
                ->orWhereHas('student', fn ($query) => $query->where('student_number', $nim))
                ->get();

            $matchedUser = $matchingUsers->count() === 1 ? $matchingUsers->first() : null;
            $conflict = $nimConflict || $emailConflict || $matchingUsers->count() > 1;

            return CareerAlumniRegistration::create([
                'reference' => (string) Str::uuid(),
                'student_number' => $nim,
                'full_name' => trim($data['full_name']),
                'claimed_program' => trim($data['claimed_program']),
                'graduation_year' => $data['graduation_year'] ?? null,
                'personal_email' => $email,
                'whatsapp' => trim($data['whatsapp']),
                'password_hash' => Hash::make($data['password']),
                'status' => $conflict ? CareerAlumniRegistration::STATUS_MANUAL_REVIEW : CareerAlumniRegistration::STATUS_PENDING,
                'conflict_code' => $conflict ? 'duplicate_or_ambiguous_identity' : null,
                'matched_user_id' => $matchedUser?->id,
            ]);
        });
    }
}
