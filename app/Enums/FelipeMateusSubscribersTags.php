<?php

namespace App\Enums;

enum FelipeMateusSubscribersTags
{
case PIXELFED;
case FELIPEMATEUS;

    public function value(): int
    {
        $env = env('APP_ENV', 'production');

        return match ($this) {
            self::PIXELFED => $env === 'production' ? 1 : 3,
            self::FELIPEMATEUS => $env === 'production' ? 2 : 4,
        };
    }
}
