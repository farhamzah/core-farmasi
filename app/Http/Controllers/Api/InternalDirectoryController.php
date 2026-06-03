<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Lecturer;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use App\Services\CoreApiResponseSanitizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class InternalDirectoryController extends Controller
{
    public function __construct(protected CoreApiResponseSanitizer $sanitizer)
    {
    }

    public function users(Request $request)
    {
        [$limit, $page] = $this->pagination($request);
        $filters = $this->validateFilters($request, [
            'q' => ['nullable', 'string', 'max:100'],
            'username' => ['nullable', 'string', 'max:100'],
            'identity_number' => ['nullable', 'string', 'max:100'],
            'active' => ['nullable', 'boolean'],
            'limit' => ['nullable', 'integer', 'min:1'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = User::query()
            ->with(['roles', 'appAccesses'])
            ->when($filters['q'] ?? null, fn (Builder $query, string $q) => $query->where(function (Builder $query) use ($q): void {
                $like = $this->like($q);
                $query->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('username', 'like', $like)
                    ->orWhere('identity_number', 'like', $like);
            }))
            ->when($filters['username'] ?? null, fn (Builder $query, string $username) => $query->where('username', $username))
            ->when($filters['identity_number'] ?? null, fn (Builder $query, string $identityNumber) => $query->where('identity_number', $identityNumber))
            ->when(array_key_exists('active', $filters), fn (Builder $query) => $query->where('active', $this->booleanFilter($filters['active'])))
            ->orderBy('name');

        return $this->directoryResponse($query, $limit, $page, fn (User $user): array => $this->sanitizer->user($user, includeAppAccesses: true));
    }

    public function user(int $id)
    {
        $user = User::query()->with(['roles', 'appAccesses'])->findOrFail($id);

        return response()->json(['data' => $this->sanitizer->user($user, includeAppAccesses: true)]);
    }

    public function students(Request $request)
    {
        [$limit, $page] = $this->pagination($request);
        $filters = $this->validateFilters($request, [
            'q' => ['nullable', 'string', 'max:100'],
            'nim' => ['nullable', 'string', 'max:100'],
            'student_number' => ['nullable', 'string', 'max:100'],
            'study_program_id' => ['nullable', 'integer', 'min:1'],
            'active' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string', 'max:50'],
            'limit' => ['nullable', 'integer', 'min:1'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = Student::query()
            ->with(['user', 'studyProgram.department', 'studyProgram.faculty'])
            ->when($filters['q'] ?? null, fn (Builder $query, string $q) => $query->where(function (Builder $query) use ($q): void {
                $like = $this->like($q);
                $query->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('student_number', 'like', $like);
            }))
            ->when($filters['nim'] ?? $filters['student_number'] ?? null, fn (Builder $query, string $number) => $query->where('student_number', $number))
            ->when($filters['study_program_id'] ?? null, fn (Builder $query, int $id) => $query->where('study_program_id', $id))
            ->when(array_key_exists('active', $filters), fn (Builder $query) => $query->where('active', $this->booleanFilter($filters['active'])))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->orderBy('student_number');

        return $this->directoryResponse($query, $limit, $page, fn (Student $student): array => $this->sanitizer->student($student));
    }

    public function student(int $id)
    {
        $student = Student::query()->with(['user', 'studyProgram.department', 'studyProgram.faculty'])->findOrFail($id);

        return response()->json(['data' => $this->sanitizer->student($student)]);
    }

    public function lecturers(Request $request)
    {
        [$limit, $page] = $this->pagination($request);
        $filters = $this->validateFilters($request, [
            'q' => ['nullable', 'string', 'max:100'],
            'nidn' => ['nullable', 'string', 'max:100'],
            'nidk' => ['nullable', 'string', 'max:100'],
            'nip' => ['nullable', 'string', 'max:100'],
            'nuptk' => ['nullable', 'string', 'max:100'],
            'national_id_number' => ['nullable', 'string', 'max:100'],
            'lecturer_number' => ['nullable', 'string', 'max:100'],
            'department_id' => ['nullable', 'integer', 'min:1'],
            'study_program_id' => ['nullable', 'integer', 'min:1'],
            'active' => ['nullable', 'boolean'],
            'limit' => ['nullable', 'integer', 'min:1'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = Lecturer::query()
            ->with(['user', 'department', 'studyProgram'])
            ->when($filters['q'] ?? null, fn (Builder $query, string $q) => $query->where(function (Builder $query) use ($q): void {
                $like = $this->like($q);
                $query->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('lecturer_number', 'like', $like)
                    ->orWhere('nidn', 'like', $like)
                    ->orWhere('nidk', 'like', $like)
                    ->orWhere('nip', 'like', $like)
                    ->orWhere('nuptk', 'like', $like)
                    ->orWhere('national_id_number', 'like', $like);
            }))
            ->when($filters['nidn'] ?? null, fn (Builder $query, string $number) => $query->where(fn (Builder $query) => $query->where('nidn', $number)->orWhere('lecturer_number', $number)))
            ->when($filters['nidk'] ?? null, fn (Builder $query, string $number) => $query->where('nidk', $number))
            ->when($filters['nip'] ?? null, fn (Builder $query, string $number) => $query->where(fn (Builder $query) => $query->where('nip', $number)->orWhere('lecturer_number', $number)))
            ->when($filters['nuptk'] ?? null, fn (Builder $query, string $number) => $query->where('nuptk', $number))
            ->when($filters['national_id_number'] ?? null, fn (Builder $query, string $number) => $query->where('national_id_number', $number))
            ->when($filters['lecturer_number'] ?? null, fn (Builder $query, string $number) => $query->where('lecturer_number', $number))
            ->when($filters['department_id'] ?? null, fn (Builder $query, int $id) => $query->where('department_id', $id))
            ->when($filters['study_program_id'] ?? null, fn (Builder $query, int $id) => $query->where('study_program_id', $id))
            ->when(array_key_exists('active', $filters), fn (Builder $query) => $query->where('active', $this->booleanFilter($filters['active'])))
            ->orderBy('name');

        return $this->directoryResponse($query, $limit, $page, fn (Lecturer $lecturer): array => $this->sanitizer->lecturer($lecturer));
    }

    public function lecturer(int $id)
    {
        $lecturer = Lecturer::query()->with(['user', 'department', 'studyProgram'])->findOrFail($id);

        return response()->json(['data' => $this->sanitizer->lecturer($lecturer)]);
    }

    public function employees(Request $request)
    {
        [$limit, $page] = $this->pagination($request);
        $filters = $this->validateFilters($request, [
            'q' => ['nullable', 'string', 'max:100'],
            'employee_number' => ['nullable', 'string', 'max:100'],
            'national_id_number' => ['nullable', 'string', 'max:100'],
            'department_id' => ['nullable', 'integer', 'min:1'],
            'study_program_id' => ['nullable', 'integer', 'min:1'],
            'staff_type' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'max:50'],
            'limit' => ['nullable', 'integer', 'min:1'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = Employee::query()
            ->with(['user', 'department', 'studyProgram'])
            ->when($filters['q'] ?? null, fn (Builder $query, string $q) => $query->where(function (Builder $query) use ($q): void {
                $like = $this->like($q);
                $query->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('employee_number', 'like', $like)
                    ->orWhere('national_id_number', 'like', $like);
            }))
            ->when($filters['employee_number'] ?? null, fn (Builder $query, string $number) => $query->where('employee_number', $number))
            ->when($filters['national_id_number'] ?? null, fn (Builder $query, string $number) => $query->where('national_id_number', $number))
            ->when($filters['department_id'] ?? null, fn (Builder $query, int $id) => $query->where('department_id', $id))
            ->when($filters['study_program_id'] ?? null, fn (Builder $query, int $id) => $query->where('study_program_id', $id))
            ->when($filters['staff_type'] ?? null, fn (Builder $query, string $type) => $query->where('staff_type', $type))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->orderBy('name');

        return $this->directoryResponse($query, $limit, $page, fn (Employee $employee): array => $this->sanitizer->employee($employee));
    }

    public function employee(int $id)
    {
        $employee = Employee::query()->with(['user', 'department', 'studyProgram'])->findOrFail($id);

        return response()->json(['data' => $this->sanitizer->employee($employee)]);
    }

    public function studyPrograms(Request $request)
    {
        [$limit, $page] = $this->pagination($request);
        $filters = $this->validateFilters($request, [
            'q' => ['nullable', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:100'],
            'department_id' => ['nullable', 'integer', 'min:1'],
            'active' => ['nullable', 'boolean'],
            'limit' => ['nullable', 'integer', 'min:1'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = StudyProgram::query()
            ->with(['faculty', 'department', 'headLecturer'])
            ->when($filters['q'] ?? null, fn (Builder $query, string $q) => $query->where(function (Builder $query) use ($q): void {
                $like = $this->like($q);
                $query->where('name', 'like', $like)->orWhere('code', 'like', $like);
            }))
            ->when($filters['code'] ?? null, fn (Builder $query, string $code) => $query->where('code', $code))
            ->when($filters['department_id'] ?? null, fn (Builder $query, int $id) => $query->where('department_id', $id))
            ->when(array_key_exists('active', $filters), fn (Builder $query) => $query->where('active', $this->booleanFilter($filters['active'])))
            ->orderBy('name');

        return $this->directoryResponse($query, $limit, $page, fn (StudyProgram $studyProgram): array => $this->sanitizer->studyProgram($studyProgram));
    }

    public function studyProgram(int $id)
    {
        $studyProgram = StudyProgram::query()->with(['faculty', 'department', 'headLecturer'])->findOrFail($id);

        return response()->json(['data' => $this->sanitizer->studyProgram($studyProgram)]);
    }

    public function departments(Request $request)
    {
        [$limit, $page] = $this->pagination($request);
        $filters = $this->validateFilters($request, [
            'q' => ['nullable', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:100'],
            'active' => ['nullable', 'boolean'],
            'limit' => ['nullable', 'integer', 'min:1'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = Department::query()
            ->when($filters['q'] ?? null, fn (Builder $query, string $q) => $query->where(function (Builder $query) use ($q): void {
                $like = $this->like($q);
                $query->where('name', 'like', $like)->orWhere('code', 'like', $like);
            }))
            ->when($filters['code'] ?? null, fn (Builder $query, string $code) => $query->where('code', $code))
            ->when(array_key_exists('active', $filters), fn (Builder $query) => $query->where('active', $this->booleanFilter($filters['active'])))
            ->orderBy('name');

        return $this->directoryResponse($query, $limit, $page, fn (Department $department): array => $this->sanitizer->department($department));
    }

    public function department(int $id)
    {
        $department = Department::query()->with('faculty')->findOrFail($id);

        return response()->json(['data' => $this->sanitizer->department($department)]);
    }

    protected function directoryResponse(Builder $query, int $limit, int $page, callable $map)
    {
        $total = (clone $query)->count();
        $items = $query
            ->forPage($page, $limit)
            ->get()
            ->map($map)
            ->values();

        return response()->json([
            'data' => $items,
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'has_more' => ($page * $limit) < $total,
            ],
        ]);
    }

    protected function pagination(Request $request): array
    {
        $default = max(1, (int) config('core_api.directory_default_limit', 25));
        $max = max($default, (int) config('core_api.directory_max_limit', 100));
        $limit = min(max(1, (int) $request->integer('limit', $default)), $max);
        $page = max(1, (int) $request->integer('page', 1));

        return [$limit, $page];
    }

    protected function validateFilters(Request $request, array $rules): array
    {
        return Validator::make($request->query(), [
            ...$rules,
            'include' => ['nullable', 'string', Rule::in(['user'])],
        ])->validate();
    }

    protected function like(string $value): string
    {
        return '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], trim($value)) . '%';
    }

    protected function booleanFilter(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
