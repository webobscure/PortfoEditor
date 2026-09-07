<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Escaping and markup helpers used by every template renderer.
 *
 * User content is never trusted: it is escaped here, at the moment it becomes
 * markup, on both the preview path and the export path. Templates have no way
 * to emit a raw user string — they only ever call these helpers.
 *
 * Mirrored by frontend/src/templates/shared/html.ts.
 */
final class Html
{
    /** Schemes that may appear in an href. Everything else is dropped. */
    private const SAFE_SCHEMES = ['http', 'https', 'mailto', 'tel'];

    public static function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Normalise a user-supplied URL, returning '' when it is unusable.
     *
     * This is the second line of defence: the validator already rejects
     * anything that is not http/https, but a renderer must not depend on that
     * because seeds, imports and future features write to the same columns.
     */
    public static function url(?string $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        // Strip control characters that could be used to smuggle "javascript:".
        $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value) ?? '';

        if (str_starts_with($value, '//')) {
            return '';
        }

        $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));

        if ($scheme === '') {
            // Bare domains are treated as https rather than as a relative path.
            return 'https://'.ltrim($value, '/');
        }

        return in_array($scheme, self::SAFE_SCHEMES, true) ? $value : '';
    }

    public static function mailto(?string $email): string
    {
        $email = trim((string) $email);

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? 'mailto:'.$email : '';
    }

    public static function tel(?string $phone): string
    {
        $digits = preg_replace('/[^0-9+]/', '', (string) $phone) ?? '';

        return $digits === '' ? '' : 'tel:'.$digits;
    }

    /**
     * Attribute string in insertion order. Null and false drop the attribute,
     * true renders it bare.
     *
     * @param  array<string, string|bool|null>  $attributes
     */
    public static function attrs(array $attributes): string
    {
        $parts = [];

        foreach ($attributes as $name => $value) {
            if ($value === null || $value === false || $value === '') {
                continue;
            }

            if ($value === true) {
                $parts[] = $name;

                continue;
            }

            $parts[] = $name.'="'.self::e((string) $value).'"';
        }

        return $parts === [] ? '' : ' '.implode(' ', $parts);
    }

    /**
     * @param  array<string, string|bool|null>  $attributes
     */
    public static function tag(string $name, array $attributes = [], string $children = ''): string
    {
        return "<{$name}".self::attrs($attributes).">{$children}</{$name}>";
    }

    /**
     * @param  array<string, string|bool|null>  $attributes
     */
    public static function void(string $name, array $attributes = []): string
    {
        return "<{$name}".self::attrs($attributes).'>';
    }

    /**
     * Render a plain-text block as paragraphs. Blank lines separate paragraphs,
     * single newlines become <br>. No user markup survives this.
     */
    public static function paragraphs(?string $text, string $class = ''): string
    {
        $text = trim((string) $text);

        if ($text === '') {
            return '';
        }

        $normalised = str_replace(["\r\n", "\r"], "\n", $text);
        $blocks = preg_split('/\n{2,}/', $normalised) ?: [];
        $attrs = $class !== '' ? ' class="'.self::e($class).'"' : '';
        $out = [];

        foreach ($blocks as $block) {
            $block = trim($block);

            if ($block === '') {
                continue;
            }

            $out[] = '<p'.$attrs.'>'.implode('<br>', array_map(
                static fn (string $line) => self::e(trim($line)),
                explode("\n", $block)
            )).'</p>';
        }

        return implode('', $out);
    }

    /** Collapse a string to a single line for use in meta tags. */
    public static function summarise(?string $text, int $length = 160): string
    {
        $clean = trim(preg_replace('/\s+/u', ' ', (string) $text) ?? '');

        if ($clean === '' || mb_strlen($clean) <= $length) {
            return $clean;
        }

        return rtrim(mb_substr($clean, 0, $length - 1)).'…';
    }
}
