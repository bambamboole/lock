<?php
declare(strict_types=1);

namespace App\Shared\Ui\Components;

use Lattice\Core\Support\Affix;
use Lattice\Ui\Components\Link;

/**
 * The application's mark, linking home. Both layouts carry two: a wordmark at
 * the top of the sidebar, and a bare glyph in the topbar for the widths where
 * the sidebar is off screen — hence the key argument.
 */
final class BrandMark
{
    public static function make(string $key): Link
    {
        return self::link($key)->icon('logo');
    }

    public static function wordmark(string $key): Link
    {
        return self::link($key)->prefix(Affix::icon('logo'));
    }

    private static function link(string $key): Link
    {
        return Link::make((string) config('app.name'), $key)
            ->href(route('dashboard', absolute: false));
    }
}
