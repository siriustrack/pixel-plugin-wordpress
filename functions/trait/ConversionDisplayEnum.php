<?php

class ConversionDisplayEnum extends BaseEnum
{
    public const SITE_WIDE = 'site_wide';
    public const SPECIFIC  = 'specific';
    public const PATH      = 'path';
    public const REGEX     = 'regex';

    protected static function labels(): array
    {
        return [
            self::SITE_WIDE => __('Todo o site', 'pixel-x-app'),
            self::SPECIFIC  => __('Páginas específicas', 'pixel-x-app'),
            self::PATH      => __('Caminho da página', 'pixel-x-app'),
            self::REGEX     => __('Expressão Regular (Regex)', 'pixel-x-app'),
        ];
    }
}
