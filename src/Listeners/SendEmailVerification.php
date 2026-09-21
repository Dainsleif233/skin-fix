<?php

namespace SysHub\BSFix\Listeners;

use App\Mail\EmailVerification;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use SysHub\BSFix\Support\VerificationLink;

/**
 * Replacement for App\Listeners\SendEmailVerification (#676).
 *
 * The only difference is the link which is put into the mail: it is bound to
 * the address it is sent to and it expires.
 */
class SendEmailVerification
{
    public function handle(User $user)
    {
        if (!option('require_verification')) {
            return;
        }

        try {
            Mail::to($user->email)->send(
                new EmailVerification(url(VerificationLink::for($user)))
            );
        } catch (\Exception $e) {
            report($e);
        }
    }
}
