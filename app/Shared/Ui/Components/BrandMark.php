<?php
declare(strict_types=1);

namespace App\Shared\Ui\Components;

use Lattice\Core\Support\Affix;
use Lattice\Ui\Components\Link;

/**
 * The application's mark. The admin layout carries two: a wordmark at the top
 * of the sidebar, and a bare glyph in the topbar for the widths where the
 * sidebar is off screen — hence the key argument. It links home unless the
 * layout names a better landing, such as a realm user's account page.
 */
final class BrandMark
{
    public static function make(string $key, ?string $href = null): Link
    {
        return self::link($key, $href)->icon('logo');
    }

    public static function wordmark(string $key, ?string $href = null): Link
    {
        return self::link($key, $href)->prefix(Affix::icon('logo'));
    }

    private static function link(string $key, ?string $href): Link
    {
        return Link::make((string) config('app.name'), $key)
            ->href($href ?? route('home', absolute: false));
    }
}
