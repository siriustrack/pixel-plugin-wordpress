<?php

use Carbon_Fields\Container;
use Carbon_Fields\Field;

add_action('carbon_fields_register_fields', function () {
    //     global $pagenow;
    //
    //     $post_type = data_get($_GET, 'post_type', data_get($_POST, 'post_type', get_post_type(data_get($_GET, 'post'))));
    //     if ( ! in_array($pagenow, ['post.php', 'post-new.php']) || $post_type != PXA_key('conversion')) {
    //         return;
    //     }
    // $pixels = collect(PXA_get_setting('fb_pixels'))->pluck('pixel');
    // $pixels = $pixels->mapWithKeys(function ($item, $key) {
    //     return [$item => $item];
    // });

    Container::make('post_meta', __('Configurações', 'pixel-x-app'))
        ->where('post_type', '=', PXA_key('conversion'))
        ->set_priority('high')
        ->add_fields([
            /*
             * Page
             */
            Field::make('select', PXA_key('display_on'), __('Carregar em', 'pixel-x-app'))
                ->add_options(ConversionDisplayEnum::toSelect())
                ->set_required()
                ->set_classes('tom-select')
                ->set_width(50),

            Field::make('multiselect', PXA_key('pages'), __('Páginas', 'pixel-x-app'))
                ->add_options(PXA_get_model_list('any', 'any'))
                ->set_conditional_logic([[
                    'field' => PXA_key('display_on'),
                    'value' => ConversionDisplayEnum::SPECIFIC,
                ]])
                ->set_required()
                ->set_width(50),

            Field::make('text', PXA_key('path'), __('Caminho da Página (slug)', 'pixel-x-app'))
                ->set_help_text(__('Insira apenas o caminho da página, sem domínio e sem parâmetros, caso tenha sub-página, inserir o último caminho. Insirá "HOME", para conversões na página raiz do domínio. Pode ser inserido mais de um caminho de página, separado por ",".', 'pixel-x-app'))
                ->set_attribute('placeholder', __('/caminho-de-pagina', 'pixel-x-app'))
                ->set_conditional_logic([[
                    'field' => PXA_key('display_on'),
                    'value' => ConversionDisplayEnum::PATH,
                ]])
                ->set_required()
                ->set_width(50),

            Field::make('text', PXA_key('regex'), __('Expressão Regular', 'pixel-x-app'))
                ->set_help_text(sprintf(
                    '%s <a href="%s" target="_blank">%s</a>.',
                    __('As expressões regulares são uma forma avançada de filtrar pelo caminho da página (slug) as conversões que deseja executar.', 'pixel-x-app'),
                    'https://pixelx.app/tutorial-regex',
                    __('Saiba mais sobre como Usar Expressões Regulares', 'pixel-x-app'),
                ))
                ->set_conditional_logic([[
                    'field' => PXA_key('display_on'),
                    'value' => ConversionDisplayEnum::REGEX,
                ]])
                ->set_required()
                ->set_width(50),

            /*
             * Trigger
             */
            Field::make('separator', PXA_key('separator_trigger'), __('Gatilho', 'pixel-x-app'))
                ->set_width(100),

            Field::make('select', PXA_key('trigger'), __('Configurações do Gatilho', 'pixel-x-app'))
                ->add_options(TriggerEnum::toSelect())
                ->set_required()
                ->set_classes('tom-select')
                ->set_width(30),

            Field::make('text', PXA_key('time'), __('Tempo de Espera', 'pixel-x-app'))
                ->set_attribute('type', 'number')
                ->set_attribute('min', '0')
                ->set_attribute('step', '1')
                ->set_help_text(__('Defina o tempo em segundos.', 'pixel-x-app'))
                ->set_required()
                ->set_width(30)
                ->set_conditional_logic([
                    'relation' => 'OR',
                    [
                        'field' => PXA_key('trigger'),
                        'value' => TriggerEnum::PAGE_TIME,
                    ],  [
                        'field' => PXA_key('trigger'),
                        'value' => TriggerEnum::VIDEO_TIME,
                    ],
                ]),

            Field::make('text', PXA_key('class'), __('Classe CSS ou ID do Elemento', 'pixel-x-app'))
                ->set_help_text(__('Defina o nome da classe CSS a inserir nos elementos ou o ID único do elemento, com letras minúsculas, hífen e underline. Evite o uso de números.', 'pixel-x-app'))
                ->set_default_value(str_random(20))
                ->set_required()
                ->set_width(30)
                ->set_conditional_logic([
                    'relation' => 'OR',
                    [
                        'field' => PXA_key('trigger'),
                        'value' => TriggerEnum::FORM_SUBMIT,
                    ],  [
                        'field' => PXA_key('trigger'),
                        'value' => TriggerEnum::CLICK,
                    ],  [
                        'field' => PXA_key('trigger'),
                        'value' => TriggerEnum::MOUSE_OVER,
                    ],  [
                        'field' => PXA_key('trigger'),
                        'value' => TriggerEnum::VIEW_ELEMENT,
                    ],  [
                        'field' => PXA_key('trigger'),
                        'value' => TriggerEnum::VIDEO_TIME,
                    ],
                ]),

            Field::make('text', PXA_key('scroll'), __('Rolagem da Página', 'pixel-x-app'))
                ->set_attribute('type', 'number')
                ->set_attribute('min', '0')
                ->set_attribute('max', '100')
                ->set_attribute('step', '5')
                ->set_help_text(__('Defina o percentual da rolagem da página.', 'pixel-x-app'))
                ->set_required()
                ->set_width(30)
                ->set_conditional_logic([[
                    'field' => PXA_key('trigger'),
                    'value' => TriggerEnum::SCROLL,
                ]]),

            /*
             * Event
             */
            Field::make('separator', PXA_key('separator_event'), __('Evento', 'pixel-x-app'))
                ->set_width(100),

            Field::make('select', PXA_key('event'), __('Evento', 'pixel-x-app'))
                ->add_options(EventEnum::toSelect())
                ->set_required()
                ->set_classes('tom-select')
                ->set_width(33),

            Field::make('text', PXA_key('event_custom'), __('Evento Personalizado', 'pixel-x-app'))
                ->set_help_text(__('Defina o nome do evento personalizado.', 'pixel-x-app'))
                ->set_required()
                ->set_width(33)
                ->set_conditional_logic([[
                    'field' => PXA_key('event'),
                    'value' => EventEnum::CUSTOM,
                ]]),

            Field::make('text', PXA_key('content_name'), __('Nome do Conteúdo', 'pixel-x-app'))
                ->set_help_text(__('Defina o nome do conteúdo personalizado, senão, será definido o nome do produto.', 'pixel-x-app'))
                ->set_width(33)
                ->set_conditional_logic([
                    'relation' => 'OR',
                    [
                        'field' => PXA_key('event'),
                        'value' => EventEnum::VIEW_CONTENT,
                    ], [
                        'field' => PXA_key('event'),
                        'value' => EventEnum::CONTENT,
                    ],  [
                        'field' => PXA_key('event'),
                        'value' => EventEnum::CUSTOM,
                    ],
                ]),

            Field::make('text', PXA_key('predicted_ltv'), __('LTV Previsto', 'pixel-x-app'))
                ->set_help_text(__('Informe o valor previsto do Lifetime Value.', 'pixel-x-app'))
                ->set_default_value(PXA_get_setting('predicted_ltv'))
                ->set_attribute('type', 'number')
                ->set_attribute('min', '0')
                ->set_attribute('step', '0.01')
                ->set_width(33)
                ->set_conditional_logic([
                    'relation' => 'OR',
                    [
                        'field' => PXA_key('event'),
                        'value' => EventEnum::START_TRIAL,
                    ],  [
                        'field' => PXA_key('event'),
                        'value' => EventEnum::SUBSCRIBE,
                    ],
                ]),

            /*
             * Product
             */
            Field::make('separator', PXA_key('separator_product'), __('Produto', 'pixel-x-app'))
                ->set_width(100),

            Field::make('text', PXA_key('product_name'), __('Nome do Produto', 'pixel-x-app'))
                ->set_help_text(__('Informe o nome do produto igual cadastrado na plataforma de pagamento.', 'pixel-x-app'))
                ->set_default_value(PXA_get_setting('product_name'))
                ->set_width(20),

            Field::make('text', PXA_key('product_id'), __('ID do Produto', 'pixel-x-app'))
                ->set_help_text(__('Informe o ID do produto igual cadastrado na plataforma de pagamento.', 'pixel-x-app'))
                ->set_default_value(PXA_get_setting('product_id'))
                ->set_width(20),

            Field::make('text', PXA_key('offer_ids'), __('IDs da Oferta', 'pixel-x-app'))
                ->set_help_text(__('Separe os IDs das ofertas por ",", caso tenha mais de uma oferta na página.', 'pixel-x-app'))
                ->set_default_value(PXA_get_setting('offer_ids'))
                ->set_width(20),

            Field::make('text', PXA_key('product_value'), __('Valor do Produto', 'pixel-x-app'))
                ->set_help_text(__('Informe o valor do produto ou oferta.', 'pixel-x-app'))
                ->set_attribute('type', 'number')
                ->set_attribute('min', '0')
                ->set_attribute('step', '0.01')
                ->set_default_value(PXA_get_setting('product_value'))
                ->set_width(20),

            Field::make('text', PXA_key('currency'), __('Moeda', 'pixel-x-app'))
                ->set_help_text(__('Defina o código da moeda do produto conforme ISO 4217.<br><a href="https://pt.wikipedia.org/wiki/ISO_4217" target="_blank">Listagem de Moeda</a>', 'pixel-x-app'))
                ->set_default_value(PXA_get_setting('currency'))
                ->set_width(20),

            /*
             * Outras Configurações
             */
            //             Field::make('separator', PXA_key('separator_product'), __('Outros', 'pixel-x-app'))
            //                 ->set_width(100),
            //
            //             Field::make('multiselect', PXA_key('fb_pixels'), __('Facebook - Pixel da Conversão', 'pixel-x-app'))
            //                 ->set_help_text(__('Se nenhum pixel for selecionado, será acionado em todos os pixels da página.', 'pixel-x-app'))
            //                 ->set_options($pixels->all()),

            // Field::make('text', PXA_key('gg_ads_label_convertion'), __('GAds - Rótulo de Conversão', 'pixel-x-app'))
            //     ->set_help_text(__('Defina o rótulo (label) de conversão gerado pelo Google Ads.', 'pixel-x-app'))
            //     ->set_width(50),
        ]);
});
