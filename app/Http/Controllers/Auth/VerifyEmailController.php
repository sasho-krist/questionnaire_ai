<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\ResendInstallChecker;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\Mailer\Exception\TransportException;

class VerifyEmailController extends Controller
{
    public function notice(Request $request): View|RedirectResponse
    {
        return $request->user()->hasVerifiedEmail()
            ? redirect()->route('questionnaires.index')
            : view('auth.verify-email');
    }

    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        $request->fulfill();

        return redirect()->route('questionnaires.index')->with('status', 'Имейлът е потвърден.');
    }

    public function send(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('questionnaires.index');
        }

        try {
            $request->user()->sendEmailVerificationNotification();
        } catch (TransportException $e) {
            Log::error('Email verification mail transport failed', [
                'exception' => $e,
                'mail_default' => config('mail.default'),
            ]);

            $email = match (config('mail.default')) {
                'resend' => 'Изпращането през Resend не успя. Проверете RESEND_API_KEY и задължително задайте MAIL_FROM_ADDRESS (верифициран домейн в Resend или onboarding@resend.dev за тест).',
                default => 'Това е грешка от SMTP транспорт. Ако в .env вече имате MAIL_MAILER=resend, Laravel вероятно ползва стар кеш — изпълнете: php artisan config:clear (и изтрийте bootstrap/cache/config.php ако съществува). След това задайте RESEND_API_KEY и MAIL_FROM_ADDRESS. За истински SMTP ползвайте хост/порт, които хостингът позволява.',
            };

            return back()->withErrors(['email' => $email]);
        } catch (\Throwable $e) {
            if (ResendInstallChecker::isMissingSdkError($e)) {
                Log::error('Resend SDK missing on server', ['exception' => $e]);

                return back()->withErrors([
                    'email' => 'Липсва PHP пакетът resend/resend-php (клас Resend). На сървъра в папката на проекта изпълнете: composer install --no-dev -o, после php artisan config:clear.',
                ]);
            }

            throw $e;
        }

        return back()->with('status', 'Изпратихме нов линк за потвърждение.');
    }
}
