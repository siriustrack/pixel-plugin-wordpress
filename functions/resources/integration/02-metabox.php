<?php

use Carbon_Fields\Container;
use Carbon_Fields\Field;

add_action('carbon_fields_register_fields', function () {
    $id        = PXA_get_post_meta(data_get($_GET, 'post'), 'id') ?? wp_generate_uuid4();
    $url       = get_rest_url(null, PXA_DOMAIN . '/v1/integration') . '?pid=' . $id;
    $url_field = pxa_input_html($url, true);

    Container::make('post_meta', __('Configurações', 'pixel-x-app'))
        ->where('post_type', '=', PXA_key('integration'))
        ->set_priority('high')
        ->add_fields([
            Field::make('select', PXA_key('type'), __('Tipo', 'pixel-x-app'))
                ->set_help_text(__('Selecione qual o tipo de integração deseja realizar.'))
                ->add_options(IntegrationTypeEnum::toSelect())
                ->set_required()
                ->set_classes('tom-select')
                ->set_width(50),

            Field::make('text', PXA_key('token'), __('Token API', 'pixel-x-app'))
                ->set_help_text(__('Informe o Token do Webhook gerado pela plataforma a ser integrada.'))
                ->set_attribute('type', 'password')
                ->set_required()
                ->set_width(50)
                ->set_conditional_logic([[
                    'field'   => PXA_key('type'),
                    'value'   => IntegrationTypeEnum::CUSTOM,
                    'compare' => '!=',
                ], [
                    'field'   => PXA_key('type'),
                    'value'   => IntegrationTypeEnum::BLITZPAY,
                    'compare' => '!=',
                ], [
                    'field'   => PXA_key('type'),
                    'value'   => IntegrationTypeEnum::ZIPPIFY,
                    'compare' => '!=',
                ], [
                    'field'   => PXA_key('type'),
                    'value'   => IntegrationTypeEnum::CARTPANDA,
                    'compare' => '!=',
                ], [
                    'field'   => PXA_key('type'),
                    'value'   => IntegrationTypeEnum::KIRVANO,
                    'compare' => '!=',
                ], [
                    'field'   => PXA_key('type'),
                    'value'   => IntegrationTypeEnum::CAKTO,
                    'compare' => '!=',
                ], [
                    'field'   => PXA_key('type'),
                    'value'   => IntegrationTypeEnum::VOOMP,
                    'compare' => '!=',
                ]]),

            Field::make('html', PXA_key('custom_token'), __('Token Personalizado', 'pixel-x-app'))
                ->set_html(pxa_input_html(PXA_get_custom_token(), true))
                ->set_help_text(__('Inclua o Token acima no corpo da requisição personalizada.', 'pixel-x-app'))
                ->set_classes('show-label')
                ->set_width(50)
                ->set_conditional_logic([
                    'relation' => 'OR',
                    [
                        'field' => PXA_key('type'),
                        'value' => IntegrationTypeEnum::CUSTOM,
                    ], [
                        'field' => PXA_key('type'),
                        'value' => IntegrationTypeEnum::BLITZPAY,
                    ], [
                        'field' => PXA_key('type'),
                        'value' => IntegrationTypeEnum::CAKTO,
                    ]
                ]),

            Field::make('separator', PXA_key('documentation'), __('Documentação', 'pixel-x-app'))
                ->set_help_text(
                    __('Confira como realizar cada integração em nossos tutoriais para a plataforma que deseja integrar.', 'pixel-x-app')
                    . '<br><br>'
                    . PXA_component_button([
                        'title'  => __('Tutoriais de Integrações', 'pixel-x-app'),
                        'link'   => PXA_PAGE_DOCUMENTATION,
                        'icon'   => 'format-video',
                        'class'  => 'pxa-btn-primary pxa-btn-big',
                        'target' => '_blank',
                    ])
                ),

            Field::make('text', PXA_key('id'), __('ID da Integração', 'pixel-x-app'))
                ->set_help_text(__('O ID é gerado automaticamente, para identificação única internamente.', 'pixel-x-app'))
                ->set_attribute('readOnly', 'true')
                ->set_default_value($id)
                ->set_width(50),

            Field::make('html', PXA_key('url'), __('URL de Integração', 'pixel-x-app'))
                ->set_html($url_field)
                ->set_help_text(__('Insira essa URL no plataforma a ser integrada. Basta apertar no campo para copiar.', 'pixel-x-app'))
                ->set_classes('show-label')
                ->set_width(50),

            Field::make('set', PXA_key('disabled_events'), __('Desativar os Eventos', 'pixel-x-app'))
                ->set_help_text(__('Selecione quais eventos não quer que sejam rastreados, ou deixe em branco, para rastrear a todos os eventos.'))
                ->set_options([
                    EventEnum::INITIATE_CHECKOUT => __('Abandono de Carrinho', 'pixel-x-app') ,
                    EventEnum::ADD_PAYMENT_INFO  => __('Aguardando Pagamento, Boleto ou Pix Gerado', 'pixel-x-app') ,
                    EventEnum::START_TRIAL       => __('Trial - Período de Avaliação', 'pixel-x-app') ,
                    EventEnum::PURCHASE          => __('Compra Aprovada', 'pixel-x-app') ,
                    EventEnum::SUBSCRIBE         => __('Assinatura ou Parcelamento Inteligente', 'pixel-x-app') ,
                ])
                ->set_width(50),

            Field::make('checkbox', PXA_key('undefined'), __('Ignorar Eventos Indefinidos?', 'pixel-x-app'))
                ->set_help_text(__('Se ativado, os eventos que não forem identificados serão ignorar, caso contrário, será tratado como Lead.'))
                ->set_option_value('true')
                ->set_width(50),
        ]);
});
