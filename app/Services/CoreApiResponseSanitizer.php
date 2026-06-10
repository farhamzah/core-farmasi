<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Employee;
use App\Models\LeadershipAssignment;
use App\Models\Lecturer;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class CoreApiResponseSanitizer
{
    public function user(User $user, bool $includeAppAccesses = false): array
    {
        $payload = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'identity_type' => $user->identity_type,
            'identity_number' => $user->identity_number,
            'profile_photo_url' => $this->profilePhotoUrl($user),
            'active' => $user->active,
            'roles' => $user->roles->pluck('name')->values()->all(),
            'created_at' => $user->created_at?->toISOString(),
            'updated_at' => $user->updated_at?->toISOString(),
        ];

        if ($includeAppAccesses) {
            $payload['app_accesses'] = $user->appAccesses
                ->where('is_active', true)
                ->map(fn ($access): array => [
                    'app_code' => $access->app_code,
                    'role_slug' => $access->role_slug,
                ])
                ->values()
                ->all();
        }

        return $payload;
    }

    public function student(Student $student): array
    {
        return [
            'id' => $student->id,
            'user_id' => $student->user_id,
            'nim' => $student->student_number,
            'student_number' => $student->student_number,
            'name' => $student->name,
            'email' => $student->email,
            'status' => $student->status,
            'active' => $student->active,
            'birth_place' => $student->birth_place,
            'enrolled_at' => $student->enrolled_at?->toDateString(),
            'phone' => null,
            'study_program_id' => $student->study_program_id,
            'study_program' => $student->studyProgram ? [
                'id' => $student->studyProgram->id,
                'code' => $student->studyProgram->code,
                'name' => $student->studyProgram->name,
                'department' => $student->studyProgram->department?->name,
            ] : null,
            'user' => $student->relationLoaded('user') && $student->user
                ? $this->userSummary($student->user)
                : null,
        ];
    }

    public function lecturer(Lecturer $lecturer): array
    {
        return [
            'id' => $lecturer->id,
            'user_id' => $lecturer->user_id,
            'nidn' => $lecturer->nidn ?: $lecturer->lecturer_number,
            'nidk' => $lecturer->nidk,
            'nip' => $lecturer->nip,
            'nuptk' => $lecturer->nuptk,
            'national_id_number' => $lecturer->national_id_number,
            'lecturer_number' => $lecturer->lecturer_number,
            'name' => $lecturer->name,
            'email' => $lecturer->email,
            'phone' => $lecturer->phone,
            'birth_place' => $lecturer->birth_place,
            'active' => $lecturer->active,
            'department_id' => $lecturer->department_id,
            'study_program_id' => $lecturer->study_program_id,
            'department' => $lecturer->department ? [
                'id' => $lecturer->department->id,
                'code' => $lecturer->department->code,
                'name' => $lecturer->department->name,
            ] : null,
            'study_program' => $lecturer->studyProgram ? [
                'id' => $lecturer->studyProgram->id,
                'code' => $lecturer->studyProgram->code,
                'name' => $lecturer->studyProgram->name,
            ] : null,
            'user' => $lecturer->relationLoaded('user') && $lecturer->user
                ? $this->userSummary($lecturer->user)
                : null,
        ];
    }

    public function employee(Employee $employee): array
    {
        return [
            'id' => $employee->id,
            'user_id' => $employee->user_id,
            'employee_number' => $employee->employee_number,
            'national_id_number' => $employee->national_id_number,
            'name' => $employee->name,
            'staff_type' => $employee->staff_type,
            'position_title' => $employee->position_title,
            'email' => $employee->email,
            'phone' => $employee->phone,
            'birth_place' => $employee->birth_place,
            'status' => $employee->status,
            'department_id' => $employee->department_id,
            'study_program_id' => $employee->study_program_id,
            'department' => $employee->department ? [
                'id' => $employee->department->id,
                'code' => $employee->department->code,
                'name' => $employee->department->name,
            ] : null,
            'study_program' => $employee->studyProgram ? [
                'id' => $employee->studyProgram->id,
                'code' => $employee->studyProgram->code,
                'name' => $employee->studyProgram->name,
            ] : null,
            'user' => $employee->relationLoaded('user') && $employee->user
                ? $this->userSummary($employee->user)
                : null,
        ];
    }

    public function studyProgram(StudyProgram $studyProgram): array
    {
        return [
            'id' => $studyProgram->id,
            'code' => $studyProgram->code,
            'name' => $studyProgram->name,
            'description' => $studyProgram->description,
            'faculty_id' => $studyProgram->faculty_id,
            'faculty_name' => $studyProgram->faculty?->name,
            'department_id' => $studyProgram->department_id,
            'department_name' => $studyProgram->department?->name,
            'active' => $studyProgram->active,
            'head_lecturer' => $studyProgram->relationLoaded('headLecturer') && $studyProgram->headLecturer
                ? [
                    'id' => $studyProgram->headLecturer->id,
                    'lecturer_number' => $studyProgram->headLecturer->lecturer_number,
                    'name' => $studyProgram->headLecturer->name,
                    'email' => $studyProgram->headLecturer->email,
                ]
                : null,
        ];
    }

    public function department(Department $department): array
    {
        return [
            'id' => $department->id,
            'code' => $department->code,
            'name' => $department->name,
            'description' => $department->description,
            'faculty_id' => $department->faculty_id,
            'faculty_name' => $department->faculty?->name,
            'active' => $department->active,
        ];
    }

    public function leadership(?LeadershipAssignment $assignment): ?array
    {
        if (! $assignment) {
            return null;
        }

        $person = $assignment->person;

        return [
            'position_type' => $assignment->position_type,
            'position_title' => $assignment->position_title,
            'unit_type' => $assignment->unit_type,
            'unit_id' => $assignment->unit_id,
            'person_type' => $assignment->person_type,
            'person_id' => $assignment->person_id,
            'person_name' => $assignment->official_name_snapshot ?: $person?->name,
            'title_prefix' => $assignment->title_prefix,
            'title_suffix' => $assignment->title_suffix,
            'start_date' => $assignment->start_date?->toDateString(),
            'end_date' => $assignment->end_date?->toDateString(),
        ];
    }

    protected function userSummary(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'identity_type' => $user->identity_type,
            'identity_number' => $user->identity_number,
            'profile_photo_url' => $this->profilePhotoUrl($user),
            'active' => $user->active,
        ];
    }

    private function profilePhotoUrl(User $user): ?string
    {
        return $user->profile_photo_path
            ? Storage::disk('public')->url($user->profile_photo_path)
            : null;
    }
}
