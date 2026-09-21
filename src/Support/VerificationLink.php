<?php

namespace SysHub\BSFix\Support;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\URL;

/**
 * Builds the e-mail verification link.
 *
 * The stock link (\URL::signedRoute('auth.verify', ['user' => $uid])) signs the
 * user ID only and never expires, so a link that was issued before the account
 * was given another address keeps working afterwards — which allows binding an
 * arbitrary address (#676).  Signing the address as well is what binds the link
 * to the address it was sent to.
 */
class VerificationLink
{
    /**
     * How long a freshly issued link stays valid, in minutes.
     */
    public const LIFETIME_MINUTES = 60;

    /**
     * The value that binds a link to the address it was issued for.
     */
    public static function hash(User $user): string
    {
        return hash('sha256', (string) $user->email);
    }

    /**
     * Build a relative, temporary signed verification URL for the given user.
     */
    public static function for(User $user): string
    {
        return URL::temporarySignedRoute(
            'auth.verify',
            Carbon::now()->addMinutes(static::LIFETIME_MINUTES),
            ['user' => $user->uid, 'hash' => static::hash($user)],
            false
        );
    }
}
