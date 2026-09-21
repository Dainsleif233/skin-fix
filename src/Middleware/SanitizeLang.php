<?php

namespace SysHub\BSFix\Middleware;

use Closure;
use Illuminate\Http\Request;

class SanitizeLang
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->has('lang') && !$this->isValid($request->input('lang'))) {
            $request->query->remove('lang');
            $request->request->remove('lang');
        }

        return $next($request);
    }

    protected function isValid($locale): bool
    {
        // array_key_exists() rather than Arr::has(): Arr::has() resolves the
        // given key as a dot path, so "zh_CN.name" would be accepted and then
        // persisted into users.locale, which is the very thing #684 is about.
        return is_string($locale) && array_key_exists($locale, config('locales', []));
    }
}
