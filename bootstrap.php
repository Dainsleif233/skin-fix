<?php

use Illuminate\Auth\Events\Authenticated;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Http\Kernel;
use SysHub\BSFix\Listeners\SetAppLocale;
use SysHub\BSFix\Middleware\SanitizeLang;

return function (Dispatcher $events, Kernel $kernel) {
    $event = Authenticated::class;
    $core = \App\Listeners\SetAppLocale::class;

    $reflection = new ReflectionObject($events);
    $property = $reflection->getProperty('listeners');
    $property->setAccessible(true);

    /** @var array<string, array> $listeners */
    $listeners = $property->getValue($events);

    // Drop only the unvalidated core listener (#684); keep every other
    // Authenticated listener (including other plugins') registered as-is.
    $listeners[$event] = array_values(array_filter(
        $listeners[$event] ?? [],
        function ($listener) use ($core) {
            if ($listener === $core) {
                return false;
            }

            if ($listener instanceof Closure) {
                $name = (new ReflectionFunction($listener))->getStaticVariables()['listener'] ?? null;

                return !(is_string($name)
                    && ($name === $core || str_starts_with($name, $core.'@')));
            }

            return true;
        }
    ));

    $property->setValue($events, $listeners);

    $events->listen($event, SetAppLocale::class);

    // Before DetectLanguagePrefer so a bad ?lang= never reaches core paths.
    $kernel->prependMiddleware(SanitizeLang::class);
};
