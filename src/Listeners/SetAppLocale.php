<?php

namespace SysHub\BSFix\Listeners;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

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

        $info = Arr::get(config('locales'), $locale);
        if (is_array($info) && ($alias = Arr::get($info, 'alias'))) {
            $locale = $alias;
        }

        return Arr::has(config('locales'), $locale) ? $locale : null;
    }
}
