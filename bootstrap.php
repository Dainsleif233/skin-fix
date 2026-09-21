<?php

use App\Events\ConfigureRoutes;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Http\Kernel;
use SysHub\BSFix\Controllers\EmailVerificationController;
use SysHub\BSFix\Listeners\SendEmailVerification;
use SysHub\BSFix\Listeners\SetAppLocale;
use SysHub\BSFix\Middleware\SanitizeLang;

return function (Dispatcher $events, Kernel $kernel) {
    /**
     * Drop a single class-based listener from an event, keeping every other
     * listener (including other plugins') registered as-is.
     */
    $dropClassListener = function (string $event, string $class) use ($events) {
        $reflection = new ReflectionObject($events);
        $property = $reflection->getProperty('listeners');
        $property->setAccessible(true);

        /** @var array<string, array> $listeners */
        $listeners = $property->getValue($events);

        $listeners[$event] = array_values(array_filter(
            $listeners[$event] ?? [],
            function ($listener) use ($class) {
                if ($listener === $class) {
                    return false;
                }

                if ($listener instanceof Closure) {
                    $name = (new ReflectionFunction($listener))->getStaticVariables()['listener'] ?? null;

                    return !(is_string($name)
                        && ($name === $class || str_starts_with($name, $class.'@')));
                }

                return true;
            }
        ));

        $property->setValue($events, $listeners);
    };

    // ------------------------------------------------------------------ #684 --
    // The core listener reads ?lang= from the request without validating it,
    // so the plugin replaces it with one that ignores unknown locales.
    $dropClassListener(Authenticated::class, \App\Listeners\SetAppLocale::class);
    $events->listen(Authenticated::class, SetAppLocale::class);

    // Before DetectLanguagePrefer so a bad ?lang= never reaches core paths.
    $kernel->prependMiddleware(SanitizeLang::class);

    // ------------------------------------------------------------------ #676 --
    // The core listener mails a link that is signed for the user ID only and
    // never expires, so it stays usable after the address has been changed.
    // It is replaced by one that signs the address as well.
    $dropClassListener(
        'auth.registration.completed',
        \App\Listeners\SendEmailVerification::class
    );
    $events->listen('auth.registration.completed', SendEmailVerification::class);

    // Registered before RouteServiceProvider boots, and fired once the core
    // routes (routes/web.php) exist.  Routes with an identical URI and method
    // replace the core ones, which is how the fixed endpoints take over.
    $events->listen(ConfigureRoutes::class, function (ConfigureRoutes $configure) {
        $router = $configure->router;

        $router->middleware(['web'])->prefix('auth')->name('auth.')->group(function () use ($router) {
            // The legacy endpoints signed the user ID only.  Anything that
            // still arrives without the e-mail hash is refused, so links that
            // were handed out before this plugin was installed lose their
            // value once the account's address changes.
            $router->get('verify/{user}', function () {
                abort(403, trans('auth.verify.invalid'));
            });
            $router->post('verify/{user}', function () {
                abort(403, trans('auth.verify.invalid'));
            });

            $router->get('verify/{user}/{hash}', [EmailVerificationController::class, 'verify'])
                ->name('verify');
            $router->post('verify/{user}/{hash}', [EmailVerificationController::class, 'handleVerify'])
                ->name('handle.verify');
        });

        // "Resend verification mail" (POST /user/email-verification).  The route
        // name is kept so that route('user.email-verification') keeps working.
        $router->middleware(['web', 'authorize'])
            ->prefix('user')
            ->name('user.')
            ->group(function () use ($router) {
                $router->post('email-verification', [EmailVerificationController::class, 'send'])
                    ->name('email-verification');
            });
    });
};
