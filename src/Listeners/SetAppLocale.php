<?php

namespace SysHub\BSFix\Listeners;

use App\Models\User;
use Illuminate\Http\Request;

class SetAppLocale
{
    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function handle($event)
    {
        /** @var User */
        $user = $event->user;

        if ($this->request->has('lang')) {
            $lang = $this->normalize($this->request->input('lang'));
            if ($lang !== null) {
                $user->locale = $lang;
                $user->save();
            }

            return;
        }

        $locale = $this->normalize($user->locale);
        if ($locale !== null) {
            app()->setLocale($locale);
        }
    }

    protected function normalize($locale): ?string
    {
        if (!is_string($locale) || $locale === '') {
            return null;
        }

        $available = config('locales', []);

        // Exact key match only. Arr::has() and Arr::get() treat "." as a path
        // separator, so "zh_CN.name" resolves to config('locales')['zh_CN']['name']
        // and passes the check; the value is then persisted and every page render
        // for that account fails in Yaml::parse().
        if (!array_key_exists($locale, $available)) {
            return null;
        }

        $info = $available[$locale];
        $alias = is_array($info) ? ($info['alias'] ?? null) : null;

        return is_string($alias) && array_key_exists($alias, $available) ? $alias : $locale;
    }
}
