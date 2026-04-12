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
            Log::error('Password reset mail transport failed', [
                'exception' => $e,
                'mail_default' => config('mail.default'),
            ]);

            $msg = match (config('mail.default')) {
                'resend' => 'Изпращането през Resend не успя. Проверете RESEND_API_KEY и MAIL_FROM_ADDRESS.',
                default => 'Грешка от SMTP. Ако ползвате Resend в .env, пуснете php artisan config:clear — иначе остава стар smtp. Нужни са MAIL_MAILER=resend, RESEND_API_KEY, MAIL_FROM_ADDRESS.',
            };

            return back()->withInput($request->only('email'))->withErrors(['email' => $msg]);
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
