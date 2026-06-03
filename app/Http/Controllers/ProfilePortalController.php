<?php

namespace App\Http\Controllers;

use App\Services\CoreProfilePortalService;
use App\Models\UserActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProfilePortalController extends Controller
{
    public function __construct(
        private readonly CoreProfilePortalService $profiles,
    ) {
    }

    public function show(Request $request): View|RedirectResponse
    {
        if ($request->user()->must_change_password) {
            return redirect()->route('profile.password.edit');
        }

        $profile = $this->profiles->summaryFor($request->user());

        return view('profile.show', [
            'profile' => $profile,
            'user' => $request->user(),
        ]);
    }

    public function edit(Request $request): View|RedirectResponse
    {
        if ($request->user()->must_change_password) {
            return redirect()->route('profile.password.edit');
        }

        $profile = $this->profiles->summaryFor($request->user());

        return view('profile.edit', [
            'profile' => $profile,
            'user' => $request->user(),
        ]);
    }

    public function changePassword(Request $request): View
    {
        return view('profile.change-password', [
            'user' => $request->user(),
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', Password::default(), 'min:8'],
        ]);

        $user = $request->user();

        if (! Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Password saat ini tidak sesuai.',
            ]);
        }

        if (Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'Password baru tidak boleh sama dengan password saat ini.',
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
            'password_changed_at' => now(),
        ])->save();

        if ($request->hasSession()) {
            $request->session()->regenerate();
            $request->session()->put([
                'password_hash_web' => $user->password,
            ]);
        }

        UserActivityLog::create([
            'user_id' => $user->id,
            'action' => 'profile.password_changed',
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'meta' => [
                'target_user_id' => $user->id,
                'changed_by' => $user->id,
                'source' => 'profile_portal',
            ],
        ]);

        $redirectRoute = $this->profiles->isComplete($user) ? 'profile.show' : 'profile.edit';

        return redirect()
            ->route($redirectRoute)
            ->with('status', 'Password berhasil diganti.');
    }

    public function update(Request $request): RedirectResponse
    {
        if ($request->user()->must_change_password) {
            return redirect()->route('profile.password.edit');
        }

        $validated = $request->validate([
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'alternate_email' => ['nullable', 'email', 'max:255'],
        ]);

        $result = $this->profiles->updateSafeContactFields($request->user(), $validated, $request);

        if ($result['updated'] === []) {
            return redirect()
                ->route('profile.show')
                ->with('status', 'Tidak ada perubahan kontak yang perlu disimpan.');
        }

        return redirect()
            ->route('profile.show')
            ->with('status', 'Profil kontak berhasil diperbarui.');
    }
}
