<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    private const TEMPORARY_EMPLOYEE_PASSWORD = 'TempEmployee123!@#';

    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();
        $this->flagTemporaryEmployeePassword($request);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function flagTemporaryEmployeePassword(Request $request): void
    {
        $user = $request->user();

        if (
            !$user
            || $user->canonicalRole() !== 'employee'
            || $user->password_must_change
            || !Hash::check(self::TEMPORARY_EMPLOYEE_PASSWORD, (string) $user->password)
        ) {
            return;
        }

        $user->forceFill(['password_must_change' => true])->save();
    }
}
