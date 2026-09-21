<?php

use App\Events\PluginWasEnabled;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return [
    PluginWasEnabled::class => function () {
        $allowed = array_keys(config('locales', []));

        $query = DB::table('users')
            ->whereNotNull('locale')
            ->where('locale', '!=', '');

        if (count($allowed) > 0) {
            $query->whereNotIn('locale', $allowed);
        }

        $affected = $query->update(['locale' => null]);

        if ($affected > 0) {
            Log::info("bs-fix: cleared {$affected} invalid users.locale value(s)");
        }
    },
];
