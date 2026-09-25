<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Media library collections registered on the Person model.
 */
enum PersonMediaCollection: string
{
    case Photos = 'photos';
    case Files  = 'files';

    /**
     * The filesystem disk the collection is stored on.
     */
    public function disk(): string
    {
        return match ($this) {
            self::Photos => 'photos',
            self::Files  => 'files',
        };
    }
}
