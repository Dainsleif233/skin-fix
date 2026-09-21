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
        // array_key_exists(), not Arr::has(). Arr::has() treats its argument as a
        // dot path, so "zh_CN.name" resolves to config('locales')['zh_CN']['name']
        // and is accepted as a valid locale.
        return is_string($locale) && array_key_exists($locale, config('locales', []));
    }
}
