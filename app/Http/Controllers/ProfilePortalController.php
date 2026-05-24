<?php

namespace App\Http\Controllers;

use App\Services\CoreProfilePortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfilePortalController extends Controller
{
    public function __construct(
        private readonly CoreProfilePortalService $profiles,
    ) {
    }

    public function show(Request $request): View
    {
        $profile = $this->profiles->summaryFor($request->user());

        return view('profile.show', [
            'profile' => $profile,
            'user' => $request->user(),
        ]);
    }

    public function edit(Request $request): View
    {
        $profile = $this->profiles->summaryFor($request->user());

        return view('profile.edit', [
            'profile' => $profile,
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'alternate_email' => ['nullable', 'email', 'max:255'],
        ]);

        $result = $this->profiles->updateSafeContactFields($request->user(), $validated, $request);

        if ($result['updated'] === []) {
            return redirect()
                ->route('profile.edit')
                ->with('status', 'Belum ada field kontak yang dapat diperbarui untuk profil ini.');
        }

        return redirect()
            ->route('profile.show')
            ->with('status', 'Profil kontak berhasil diperbarui.');
    }
}
