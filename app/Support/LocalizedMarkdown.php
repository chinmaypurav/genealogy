<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Finds a markdown page in resources/markdown, preferring the current locale's translation.
 *
 * For "terms.md" it tries "terms.{locale}.md" first, then "terms.md". Shared by all static pages
 * so they resolve translations the same way.
 */
class LocalizedMarkdown
{
    public static function path(string $name): ?string
    {
        $localizedName = preg_replace('#(\.md)$#i', '.' . app()->getLocale() . '$1', $name);

        return collect([resource_path('markdown/' . $localizedName), resource_path('markdown/' . $name)])
            ->first(fn (string $path): bool => file_exists($path));
    }
}
