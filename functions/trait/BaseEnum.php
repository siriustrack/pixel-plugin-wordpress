<?php

use MyCLabs\Enum\Enum;

class BaseEnum extends Enum
{
    public static function toSelect(): array
    {
        foreach (self::values() as $item) {
            $arrays[$item->value] = $item->label();
        }

        return $arrays;
    }

    protected static function labels(): array
    {
        return [];
    }

    public function label(): string
    {
        return data_get(static::labels(), $this->value, ucfirst($this->value));
    }
}
