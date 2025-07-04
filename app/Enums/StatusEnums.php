<?php

namespace App\Enums;

enum StatusEnums
{
case DISABLED;
case DELETED;
case ACTIVE;
case DELETE_QUEUE;
case SUSPENDED;

    public function value(): ?string
    {
        return match($this) {
            self::DISABLED => 'disabled',
            self::DELETED => 'deleted',
            self::DELETE_QUEUE => 'delete',
            self::SUSPENDED => 'suspended',
            self::ACTIVE => null,
        };
    }

    public static function fromValue(?string $value): ?self
    {
        return match($value) {
            'disabled' => self::DISABLED,
            'deleted' => self::DELETED,
            'delete' => self::DELETE_QUEUE,
            'suspended' => self::SUSPENDED,
            null => self::ACTIVE,
            default => null,
        };
    }
}
