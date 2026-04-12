<?php

namespace App\Support;

use Throwable;

final class ResendInstallChecker
{
    /**
     * True when Laravel's Resend mailer fails because resend/resend-php is not installed (composer install not run on server).
     */
    public static function isMissingSdkError(Throwable $e): bool
    {
        $m = $e->getMessage();

        return str_contains($m, 'Resend')
            && (str_contains($m, 'not found') || str_contains($m, 'not exist'));
    }
}
