<?php

class IntegrationTypeEnum extends BaseEnum
{
    public const TICTO      = 'ticto';
    public const HOTMART    = 'hotmart';
    public const EDUZZ      = 'eduzz';
    public const EDUZZ_V2   = 'eduzz_v2';
    public const KIWIFY     = 'kiwify';
    public const BLITZPAY   = 'blitzpay';
    public const DMG        = 'dmg';
    public const CELETUS    = 'celetus';
    public const BRAIP      = 'braip';
    public const MONETIZZE  = 'monetizze';
    public const DOPPUS     = 'doppus';
    public const ZIPPIFY    = 'zippify';
    public const HUBLA      = 'hubla';
    public const CARTPANDA  = 'cartpanda';
    public const PAYT       = 'payt';
    public const GREEN      = 'green';
    public const PERFECTPAY = 'perfectpay';
    public const LASTLINK   = 'lastlink';
    public const KIRVANO    = 'kirvano';
    public const YAMPI      = 'yampi';
    public const CAKTO      = 'cakto';
    public const VOOMP      = 'voomp';
    public const CUSTOM     = 'custom';

    protected static function labels(): array
    {
        return [
            self::TICTO      => __('Ticto', 'pixel-x-app'),
            self::HOTMART    => __('Hotmart', 'pixel-x-app'),
            self::EDUZZ      => __('Eduzz', 'pixel-x-app'),
            self::EDUZZ_V2   => __('Eduzz - v2', 'pixel-x-app'),
            self::KIWIFY     => __('Kiwify', 'pixel-x-app'),
            self::BLITZPAY   => __('Blitzpay', 'pixel-x-app'),
            self::DMG        => __('Digital Manager Guru', 'pixel-x-app'),
            self::CELETUS    => __('Celetus', 'pixel-x-app'),
            self::BRAIP      => __('Braip', 'pixel-x-app'),
            self::MONETIZZE  => __('Monetizze', 'pixel-x-app'),
            self::DOPPUS     => __('Doppus', 'pixel-x-app'),
            self::ZIPPIFY    => __('Zippify', 'pixel-x-app'),
            self::HUBLA      => __('Hubla', 'pixel-x-app'),
            self::CARTPANDA  => __('CartPanda', 'pixel-x-app'),
            self::PAYT       => __('PayT', 'pixel-x-app'),
            self::GREEN      => __('Greenn', 'pixel-x-app'),
            self::PERFECTPAY => __('PerfectPay', 'pixel-x-app'),
            self::LASTLINK   => __('LastLink', 'pixel-x-app'),
            self::KIRVANO    => __('Kirvano', 'pixel-x-app'),
            self::YAMPI      => __('Yampi', 'pixel-x-app'),
            self::CAKTO      => __('Cakto', 'pixel-x-app'),
            self::VOOMP      => __('Voomp', 'pixel-x-app'),
            self::CUSTOM     => __('Personalizado', 'pixel-x-app'),
        ];
    }
}
