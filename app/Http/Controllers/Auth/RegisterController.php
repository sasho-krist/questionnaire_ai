<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Symfony\Component\Mailer\Exception\TransportException;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [], [
            'name' => 'име',
            'email' => 'имейл',
            'password' => 'парола',
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        Auth::login($user);

        try {
            event(new Registered($user));
        } catch (TransportException $e) {
            Log::error('Registration verification email SMTP failed', ['exception' => $e]);

            return redirect()->route('verification.notice')->withErrors([
                'email' => 'Акаунтът е създаден, но не изпратихме имейл за потвърждение поради проблем с SMTP (често блокиран порт от хостинга). Използвайте „Изпрати отново“ по-долу след като оправите MAIL_* или се свържете с хостинга.',
            ]);
        }

        return redirect()->route('verification.notice');
    }
}
