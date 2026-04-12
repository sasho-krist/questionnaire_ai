<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\ResendInstallChecker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;
use Symfony\Component\Mailer\Exception\TransportException;

class ForgotPasswordController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']], [], ['email' => 'имейл']);

        try {
            $status = Password::sendResetLink($request->only('email'));
        } catch (TransportException $e) {
            Log::error('Password reset SMTP failed', ['exception' => $e]);

            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'Неуспешна връзка с пощенския сървър (SMTP). Ако портовете са блокирани, превключете на Resend: MAIL_MAILER=resend и RESEND_API_KEY в .env.',
            ]);
        } catch (\Throwable $e) {
            if (ResendInstallChecker::isMissingSdkError($e)) {
                Log::error('Resend SDK missing on server', ['exception' => $e]);

                return back()->withInput($request->only('email'))->withErrors([
                    'email' => 'Липсва resend/resend-php. На сървъра: composer install --no-dev -o и php artisan config:clear.',
                ]);
            }

            throw $e;
        }

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', 'Изпратихме връзка за нова парола на имейла ви.');
        }

        return back()->withInput($request->only('email'))->withErrors([
            'email' => match ($status) {
                Password::INVALID_USER => 'Няма регистрация с този имейл.',
                default => 'Неуспешно изпращане. Опитайте по-късно.',
            },
        ]);
    }
}
