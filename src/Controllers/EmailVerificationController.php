<?php

namespace SysHub\BSFix\Controllers;

use App\Exceptions\PrettyPageException;
use App\Mail\EmailVerification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use SysHub\BSFix\Support\VerificationLink;

/**
 * Takes over the two endpoints which hand out and consume verification links,
 * so that a link can only ever be used for the address it was issued for.
 *
 * @see https://github.com/bs-community/blessing-skin-server/pull/676
 */
class EmailVerificationController
{
    public function verify(Request $request, User $user)
    {
        if (!option('require_verification')) {
            throw new PrettyPageException(trans('user.verification.disabled'), 1);
        }

        $this->assertLinkIsValid($request, $user);

        return view('auth.verify');
    }

    public function handleVerify(Request $request, User $user)
    {
        $this->assertLinkIsValid($request, $user);

        ['email' => $email] = $request->validate(['email' => 'required|email']);

        if ($user->email !== $email) {
            return back()->with('errorMessage', trans('auth.verify.not-matched'));
        }

        $user->verified = true;
        $user->save();

        return redirect()->route('user.home');
    }

    /**
     * Re-issue the verification mail for the current user.
     *
     * Same behaviour as UserController::sendVerificationEmail(), except for the
     * link that is generated.
     */
    public function send()
    {
        if (!option('require_verification')) {
            return json(trans('user.verification.disabled'), 1);
        }

        // Rate limit of 60s
        $remain = 60 + session('last_mail_time', 0) - time();

        if ($remain > 0) {
            return json(trans('user.verification.frequent-mail'), 1);
        }

        $user = Auth::user();

        if ($user->verified) {
            return json(trans('user.verification.verified'), 1);
        }

        try {
            Mail::to($user->email)->send(
                new EmailVerification(url(VerificationLink::for($user)))
            );
        } catch (\Exception $e) {
            report($e);

            return json(trans('user.verification.failed', ['msg' => $e->getMessage()]), 2);
        }

        Session::put('last_mail_time', time());

        return json(trans('user.verification.success'), 0);
    }

    /**
     * A link is only accepted when its signature is intact *and* the signed
     * e-mail hash still matches the address of the account.
     */
    protected function assertLinkIsValid(Request $request, User $user): void
    {
        $hash = $request->route('hash');

        abort_unless(
            is_string($hash)
                && $request->hasValidSignature(false)
                && hash_equals(VerificationLink::hash($user), $hash),
            403,
            trans('auth.verify.invalid')
        );
    }
}
