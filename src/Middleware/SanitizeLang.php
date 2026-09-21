<?php

namespace SysHub\BSFix\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

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
        return is_string($locale) && Arr::has(config('locales'), $locale);
    }
}
