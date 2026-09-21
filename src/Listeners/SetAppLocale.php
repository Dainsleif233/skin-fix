<?php

namespace SysHub\BSFix\Listeners;

use App\Models\User;
use Illuminate\Http\Request;
use SysHub\BSFix\Support\LocaleDirectory;

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

        $locales = config('locales', []);

        // array_key_exists() rather than Arr::has(): Arr::has() resolves the
        // given key as a dot path, so "zh_CN.name" would pass the gate and be
        // persisted into users.locale.
        if (!array_key_exists($locale, $locales)) {
            return null;
        }

        $info = $locales[$locale];
        $alias = is_array($info) ? ($info['alias'] ?? null) : null;

        if (is_string($alias) && array_key_exists($alias, $locales)) {
            $locale = $alias;
        }

        // Nothing outside of the locale directories may be stored, otherwise
        // the value breaks every later page render.
        return LocaleDirectory::has($locale) ? $locale : null;
    }
}
