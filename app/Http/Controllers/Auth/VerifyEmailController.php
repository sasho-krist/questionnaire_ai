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
            Log::error('Email verification SMTP failed', ['exception' => $e]);

            return back()->withErrors([
                'email' => 'Неуспешна връзка с пощенския сървър (SMTP). Често хостингът блокира портове 587/465. Решение: ползвайте изпращане по HTTPS — в .env задайте MAIL_MAILER=resend, RESEND_API_KEY от resend.com и верифициран MAIL_FROM_ADDRESS (вижте .env.example).',
            ]);
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
