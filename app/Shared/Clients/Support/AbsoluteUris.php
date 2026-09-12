<?php
declare(strict_types=1);

namespace App\Shared\Clients\Support;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Every entry of a URI list is absolute. A redirect URI is compared against
 * what a relying party sends back, so it has to carry a scheme and a host, and
 * a fragment never survives the round trip.
 *
 * The API submits the list as an array and the console's textarea as one URI
 * per line, so the rule reads both spellings of a list.
 */
final class AbsoluteUris implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        foreach (self::list($value) as $uri) {
            if (! self::absolute($uri)) {
                $fail(__('admin.clients.validation.invalid-uri', ['uri' => $uri]));
            }
        }
    }

    /**
     * The entries of a list however its transport spells one.
     *
     * @return list<string>
     */
    public static function list(mixed $value): array
    {
        $entries = is_array($value) ? $value : (preg_split('/\R/u', (string) $value) ?: []);

        return array_values(array_filter(
            array_map(fn (mixed $entry): string => is_string($entry) ? trim($entry) : '', $entries),
            fn (string $entry): bool => $entry !== '',
        ));
    }

    private static function absolute(string $uri): bool
    {
        $parts = parse_url($uri);

        return is_array($parts)
            && (string) ($parts['scheme'] ?? '') !== ''
            && (string) ($parts['host'] ?? '') !== ''
            && ! array_key_exists('fragment', $parts);
    }
}
