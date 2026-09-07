<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Deterministic colour maths shared by the theme compiler.
 *
 * Every function here is mirrored byte-for-byte by the TypeScript compiler in
 * the frontend, and the golden-file render tests compare the two outputs, so
 * any change to the rounding here must be made on both sides.
 */
final class Color
{
    /** @return array{0:int,1:int,2:int} */
    public static function toRgb(string $hex): array
    {
        $hex = ltrim(trim($hex), '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (! preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            return [0, 0, 0];
        }

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }

    public static function toHex(int $r, int $g, int $b): string
    {
        return sprintf('#%02x%02x%02x', self::clamp($r), self::clamp($g), self::clamp($b));
    }

    public static function rgba(string $hex, float $alpha): string
    {
        [$r, $g, $b] = self::toRgb($hex);

        return sprintf('rgba(%d, %d, %d, %s)', $r, $g, $b, rtrim(rtrim(number_format($alpha, 2, '.', ''), '0'), '.'));
    }

    /** Multiply every channel, e.g. 0.88 for a subtle "pressed" shade. */
    public static function scale(string $hex, float $factor): string
    {
        [$r, $g, $b] = self::toRgb($hex);

        return self::toHex(
            (int) round($r * $factor),
            (int) round($g * $factor),
            (int) round($b * $factor),
        );
    }

    /** Mix towards white; amount 0..1. */
    public static function lighten(string $hex, float $amount): string
    {
        [$r, $g, $b] = self::toRgb($hex);

        return self::toHex(
            (int) round($r + (255 - $r) * $amount),
            (int) round($g + (255 - $g) * $amount),
            (int) round($b + (255 - $b) * $amount),
        );
    }

    /** WCAG relative luminance. */
    public static function luminance(string $hex): float
    {
        [$r, $g, $b] = self::toRgb($hex);

        $channel = static function (int $value): float {
            $v = $value / 255;

            return $v <= 0.04045 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
        };

        return 0.2126 * $channel($r) + 0.7152 * $channel($g) + 0.0722 * $channel($b);
    }

    /** Black or white text, whichever reads better on the given background. */
    public static function readableOn(string $hex): string
    {
        return self::luminance($hex) > 0.45 ? '#101014' : '#ffffff';
    }

    private static function clamp(int $value): int
    {
        return max(0, min(255, $value));
    }
}
