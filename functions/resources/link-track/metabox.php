<?php

use Carbon_Fields\Container;
use Carbon_Fields\Field;

add_action('carbon_fields_register_fields', function () {
    Container::make('post_meta', __('Configurações', 'pixel-x-app'))
        ->where('post_type', '=', PXA_key('link_tracked'))
        ->set_priority('high')
        ->add_tab(__('Configurações', 'pixel-x-app'), [
            Field::make('text', PXA_key('target'), __('Link de Destino', 'pixel-x-app'))
                ->set_help_text(__('Insira a URL completa de destino do redirecionamento.', 'pixel-x-app'))
                ->set_required()
                ->set_width(80),

            /*
             * Event
             */
            Field::make('separator', PXA_key('separator_event'), __('Evento de Rastreamento', 'pixel-x-app'))
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
             * Parameters Passing
             */
            Field::make('separator', PXA_key('separator_parameter_passing'), __('Passagem de Parâmetros da URL', 'pixel-x-app'))
                ->set_help_text(__('Por padrão, todos os parâmetros que estiverem presentes na URL, serão repassados para o link de destino.
                <br>Caso tenha sido capturado em acessos anteriores UTMs, FBC, FBP, SRC e SCK, também serão incluidos nos links, mesmo se não presentes na URL.', 'pixel-x-app')),

            Field::make('separator', PXA_key('separator_parameter_passing_lead'), __('Passagem de Dados do Lead e Inteligência de Checkout', 'pixel-x-app'))
                ->set_help_text(__('Se ativo, será incluido no Link de Destino, os dados do lead já capturados, como nome, email, telefone e documento.
                <br>Com o sistema de inteligência de checkout, ao ser identificado que seu link de destino é um checkout conhecido por nós, é feito a adaptação dos parâmetros com os dados do lead para o padrão do checkout de destino, fazendo o checkout ser pré populado, para que o lead não precise preencher novamente os seus dados.', 'pixel-x-app')),

            Field::make('checkbox', PXA_key('parameter_passing_lead'), __('Ativar Passagem de Dados do Lead e Inteligência de Checkout', 'pixel-x-app'))
                ->set_option_value('true')
                ->set_default_value(PXA_get_setting('parameter_passing_lead')),

            Field::make('checkbox', PXA_key('parameter_passing_first_access'), __('Passar Parâmetros de Campanha de Origem (Primeiro Acesso)?', 'pixel-x-app'))
                ->set_help_text(__('Se ativo, será passado os parâmetros capturado no lead em seu primeiro acesso, possibilitando marcar conversão na campanha de origem, invés de marcar na campanha que gerou a conversão.
                <br>Exemplo: Marcar a Conversão na Campanha de Descoberta, invés de marcar na Campanha de Remarketing.', 'pixel-x-app'))
                ->set_option_value('true')
                ->set_conditional_logic([[
                    'field' => PXA_key('parameter_passing_lead'),
                    'value' => true,
                ]]),

            Field::make('checkbox', PXA_key('target_is_wordpress'), __('Link de Destino é um site WordPress?', 'pixel-x-app'))
                ->set_help_text(__('Ative esse recurso para corrigir possíveis conflitos de parâmetros a serem passados, que possam não ser identificados pelo WordPress.', 'pixel-x-app'))
                ->set_option_value('true')
                ->set_conditional_logic([[
                    'field' => PXA_key('parameter_passing_lead'),
                    'value' => true,
                ]]),
        ])
        /*
         * Product
         */
        ->add_tab(__('Produto', 'pixel-x-app'), [
            Field::make('separator', PXA_key('separator_product'), __('Produto', 'pixel-x-app'))
                ->set_help_text(__('Defina valores dados do produto que deseja que seja marcado no evento e no lead ao ser redirecionado pelo Link Rastreado.', 'pixel-x-app')),

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
        ])
        /*
         * Parameters
         */
        ->add_tab(__('Parâmetros Padrões', 'pixel-x-app'), [
            Field::make('separator', PXA_key('parameter_default'), __('Parâmetros Padrões', 'pixel-x-app'))
                ->set_help_text(__('Defina valores padrões que deseja ser repassado para o Link de Destino, caso não tenham nenhum valor rastreado no lead ou pela URL.', 'pixel-x-app')),

            Field::make('text', PXA_key('utm_source'), __('UTM Source', 'pixel-x-app'))
                ->set_width(30),

            Field::make('text', PXA_key('utm_medium'), __('UTM Medium', 'pixel-x-app'))
                ->set_width(30),

            Field::make('text', PXA_key('utm_campaign'), __('UTM Campaign', 'pixel-x-app'))
                ->set_width(30),

            Field::make('text', PXA_key('utm_id'), __('UTM Id', 'pixel-x-app'))
                ->set_width(30),

            Field::make('text', PXA_key('utm_content'), __('UTM Content', 'pixel-x-app'))
                ->set_width(30),

            Field::make('text', PXA_key('utm_term'), __('UTM Term', 'pixel-x-app'))
                ->set_width(30),

            Field::make('text', PXA_key('src'), __('SRC', 'pixel-x-app'))
                ->set_width(30),
        ]);

    Container::make('post_meta', __('Estatísticas', 'pixel-x-app'))
        ->where('post_type', '=', PXA_key('link_tracked'))
        ->set_priority('default')
        ->add_fields([
            Field::make('text', PXA_key('stats_access'), __('Total de Acessos', 'pixel-x-app'))
                ->set_width(30)
                ->set_default_value(0)
                ->set_attribute('readOnly', 'true'),

            Field::make('text', PXA_key('last_lead'), __('Último Lead', 'pixel-x-app'))
                ->set_width(30)
                ->set_attribute('readOnly', 'true'),

            Field::make('text', PXA_key('last_access'), __('Último Acesso', 'pixel-x-app'))
                ->set_width(30)
                ->set_attribute('readOnly', 'true'),
        ]);
});
