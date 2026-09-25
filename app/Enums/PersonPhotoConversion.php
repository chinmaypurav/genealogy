<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * WebP conversions generated for every person photo.
 *
 * - Small  : avatars and family tree nodes (w-24 / size-10, 2x for retina displays)
 * - Medium : profile card, gallery, photo management and datasheet (max-w-sm)
 * - Large  : full screen view when a photo is opened from the gallery
 */
enum PersonPhotoConversion: string
{
    case Small  = 'small';
    case Medium = 'medium';
    case Large  = 'large';

    /**
     * Maximum width in pixels.
     */
    public function width(): int
    {
        return match ($this) {
            self::Small  => 192,
            self::Medium => 384,
            self::Large  => 1920,
        };
    }

    /**
     * Maximum height in pixels, null keeps the aspect ratio based on the width only.
     */
    public function height(): ?int
    {
        return match ($this) {
            self::Small, self::Medium => null,
            self::Large               => 1080,
        };
    }

    /**
     * WebP encoding quality.
     */
    public function quality(): int
    {
        return match ($this) {
            self::Small  => 80,
            self::Medium => 85,
            self::Large  => 90,
        };
    }
}
