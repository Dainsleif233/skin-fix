<?php

namespace SysHub\BSFix\Support;

/**
 * Whether a locale can actually be rendered.
 *
 * The application locale is used as a directory name when the front-end
 * translations are built:
 *
 *     JavaScript.php:36   resource_path("lang/$locale/front-end.yml")
 *     JavaScript.php:37   ->lastModified($path)   // filemtime() on a missing file
 *
 * so any value without that file turns every page render into a 500 (#684).
 */
class LocaleDirectory
{
    public static function has(string $locale): bool
    {
        if ($locale === '' || str_contains($locale, '..')) {
            return false;
        }

        // Keeps path separators and anything else exotic out of the path.
        if (!preg_match('/^[A-Za-z0-9_.-]+$/', $locale)) {
            return false;
        }

        return is_file(resource_path("lang/$locale/front-end.yml"));
    }
}
