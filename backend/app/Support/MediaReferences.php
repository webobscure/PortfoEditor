<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Finds media ids inside a section's JSON payload.
 *
 * The convention is that any key ending in `_media_id` holds a media id, at any
 * depth. Walking the structure by convention rather than by a hardcoded list of
 * paths means a new section type that stores an image needs no changes here,
 * and — more importantly — the export and the garbage collector can never
 * disagree about which files are still in use.
 */
final class MediaReferences
{
    /**
     * @return array<int, int>
     */
    public static function collect(mixed $payload): array
    {
        $ids = [];
        self::walk($payload, $ids);

        return array_values(array_unique($ids));
    }

    /**
     * @param  array<int, int>  $ids
     */
    private static function walk(mixed $node, array &$ids): void
    {
        if (! is_array($node)) {
            return;
        }

        foreach ($node as $key => $value) {
            if (is_string($key) && str_ends_with($key, '_media_id')) {
                if (is_int($value) || (is_string($value) && ctype_digit($value))) {
                    $ids[] = (int) $value;
                }

                continue;
            }

            self::walk($value, $ids);
        }
    }
}
