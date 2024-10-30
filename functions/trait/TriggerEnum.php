<?php

class TriggerEnum extends BaseEnum
{
    public const PAGE_LOAD    = 'page_load';
    public const PAGE_TIME    = 'page_time';
    public const VIDEO_TIME   = 'video_time';
    public const FORM_SUBMIT  = 'form_submit';
    public const CLICK        = 'click';
    public const VIEW_ELEMENT = 'view_element';
    public const MOUSE_OVER   = 'mouse_over';
    public const SCROLL       = 'scroll';

    protected static function labels(): array
    {
        return [
            self::PAGE_LOAD    => __('Visualização de Página', 'pixel-x-app'),
            self::PAGE_TIME    => __('Tempo de Visualização de Página', 'pixel-x-app'),
            self::VIDEO_TIME   => __('Tempo de Visualização de Video (Em Breve)', 'pixel-x-app'),
            self::FORM_SUBMIT  => __('Formulário Enviado / Submetido', 'pixel-x-app'),
            self::CLICK        => __('Clique no Botão / Elemento', 'pixel-x-app'),
            self::VIEW_ELEMENT => __('Visualização de Elemento / Sessão', 'pixel-x-app'),
            self::MOUSE_OVER   => __('Passagem de Mouse em Elemento', 'pixel-x-app'),
            self::SCROLL       => __('Percentual de Rolagem de Página', 'pixel-x-app'),
        ];
    }
}
