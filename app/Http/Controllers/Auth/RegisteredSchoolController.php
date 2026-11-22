<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Domain\School\Actions\SetupSchoolAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Helpers\NormalizesInputHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisteredSchoolController extends Controller
{
    /**
     * A packaged single school install is set up with kcam:install, never from the web.
     */
    private function ensureOpen(): void
    {
        abort_if(config('kcam.mode') === 'single', 404);
    }

    private function clean(mixed $value): mixed
    {
        return is_string($value) ? NormalizesInputHelper::cleanText($value) : $value;
    }

    /**
     * Display the school registration view.
     */
    public function create(): View
    {
        $this->ensureOpen();

        return view('auth.register');
    }

    /**
     * Handle an incoming school registration request.
     */
    public function store(Request $request, SetupSchoolAction $setupAction): RedirectResponse
    {
        $this->ensureOpen();

        $request->merge([
            'name' => $this->clean($request->input('name')),
            'name_bn' => $this->clean($request->input('name_bn')),
            'address' => $this->clean($request->input('address')),
            'address_bn' => $this->clean($request->input('address_bn')),
            'owner_name' => $this->clean($request->input('owner_name')),
            'phone' => is_string($request->input('phone')) ? NormalizesInputHelper::canonicalMobile($request->input('phone')) : $request->input('phone'),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'name_bn' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^01[3-9]\d{8}$/', 'unique:users,mobile'],
            'address' => ['nullable', 'string', 'max:1000'],
            'address_bn' => ['nullable', 'string', 'max:1000'],
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class.',email'],
            'owner_password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $school = $setupAction->execute($validated);

        $owner = User::where('school_id', $school->id)
            ->where('email', $validated['owner_email'])
            ->firstOrFail();

        Auth::login($owner);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'বিদ্যালয় সফলভাবে নিবন্ধিত হয়েছে। KCAM এ স্বাগতম!');
    }
}
