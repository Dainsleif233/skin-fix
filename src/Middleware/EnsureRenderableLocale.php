<?php

namespace SysHub\BSFix\Middleware;

use Closure;
use Illuminate\Http\Request;
use SysHub\BSFix\Support\LocaleDirectory;

/**
 * Last line of defence for #684.
 *
 * It is pushed onto the global middleware stack, i.e. it runs right after
 * DetectLanguagePrefer, no matter where the application locale came from —
 * the query string, the cookie, Accept-Language or a value that was already
 * stored in the database.  A locale the front-end translations cannot be
 * built for is replaced before anything renders.
 */
class EnsureRenderableLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locale = app()->getLocale();

        if (!is_string($locale) || !LocaleDirectory::has($locale)) {
            app()->setLocale(config('app.fallback_locale'));
        }

        return $next($request);
    }
}
