<?php

namespace App\Enums;

enum StatusEnums
{
case DISABLED;
case DELETED;
case ACTIVE;

    public function value(): ?string
    {
        return match($this) {
            self::DISABLED => 'disabled',
            self::DELETED => 'deleted',
            self::ACTIVE => null,
        };
    }

    public static function fromValue(?string $value): ?self
    {
        return match($value) {
            'disabled' => self::DISABLED,
            'deleted' => self::DELETED,
            null => self::ACTIVE,
            default => null,
        };
    }
}
