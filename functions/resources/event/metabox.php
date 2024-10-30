<?php

use Carbon_Fields\Container;
use Carbon_Fields\Field;

add_action('carbon_fields_register_fields', function () {
    $lead_id   = '';
    $lead_uuid = '';

    if (data_get($_GET, 'post')) {
        $lead_id   = PXA_get_post_meta(data_get($_GET, 'post'), 'lead_id');
        $lead      = PXA_get_model($lead_id, 'lead');
        $lead_uuid = data_get($lead, PXA_key('id'));
    }

    Container::make('post_meta', __('Informações', 'pixel-x-app'))
        ->where('post_type', '=', PXA_key('event'))
        ->set_priority('high')
        ->add_tab(__('Lead', 'pixel-x-app'), [
            Field::make('text', PXA_key('id'), __('ID', 'pixel-x-app'))
                ->set_help_text(__('O ID é gerado automaticamente, para identificação única.', 'pixel-x-app'))
                ->set_attribute('readOnly', 'true')
                ->set_width(50),

            Field::make('text', PXA_key('lead_id'), __('ID do Lead Interno', 'pixel-x-app'))
                ->set_attribute('readOnly', 'true')
                ->set_help_text(sprintf(
                    __('ID Único Externo: %s <br><a href="%s" target="_blank">Ver Lead</a>', 'pixel-x-app'),
                    $lead_uuid,
                    admin_url('post.php?action=edit&post=' . $lead_id)
                ))
                ->set_width(50),
        ])
            ->add_tab(__('Localização', 'pixel-x-app'), [
                Field::make('text', PXA_key('geo_ip'), __('IP', 'pixel-x-app'))
                    ->set_width(50)
                    ->set_attribute('readOnly', 'true'),

                Field::make('text', PXA_key('geo_device'), __('Dispositivo', 'pixel-x-app'))
                    ->set_width(50)
                    ->set_attribute('readOnly', 'true'),

                Field::make('text', PXA_key('geo_city'), __('Cidade', 'pixel-x-app'))
                    ->set_width(20)
                    ->set_attribute('readOnly', 'true'),

                Field::make('text', PXA_key('geo_state'), __('Estado', 'pixel-x-app'))
                    ->set_width(20)
                    ->set_attribute('readOnly', 'true'),

                Field::make('text', PXA_key('geo_country'), __('Código do País', 'pixel-x-app'))
                    ->set_width(20)
                    ->set_attribute('readOnly', 'true'),

                Field::make('text', PXA_key('geo_country_name'), __('País', 'pixel-x-app'))
                    ->set_width(20)
                    ->set_attribute('readOnly', 'true'),

                Field::make('text', PXA_key('geo_zipcode'), __('Código Postal', 'pixel-x-app'))
                    ->set_width(20)
                    ->set_attribute('readOnly', 'true'),
            ])
            ->add_tab(__('Conteúdo', 'pixel-x-app'), [
                Field::make('text', PXA_key('product_name'), __('Nome do Produto', 'pixel-x-app'))
                    ->set_attribute('readOnly', 'true')
                    ->set_width(25),

                Field::make('text', PXA_key('product_id'), __('ID do Produto', 'pixel-x-app'))
                    ->set_attribute('readOnly', 'true')
                    ->set_width(25),

                Field::make('text', PXA_key('offer_ids'), __('IDs das Ofertas', 'pixel-x-app'))
                    ->set_attribute('readOnly', 'true')
                    ->set_width(25),

                Field::make('text', PXA_key('product_value'), __('Valor do Produto', 'pixel-x-app'))
                    ->set_attribute('readOnly', 'true')
                    ->set_width(25),

                Field::make('text', PXA_key('predicted_ltv'), __('LTV Previsto', 'pixel-x-app'))
                    ->set_attribute('readOnly', 'true')
                    ->set_width(25),

                Field::make('text', PXA_key('geo_currency'), __('Moeda', 'pixel-x-app'))
                    ->set_width(25)
                    ->set_attribute('readOnly', 'true'),

                Field::make('text', PXA_key('page_id'), __('ID da Página', 'pixel-x-app'))
                    ->set_attribute('readOnly', 'true')
                    ->set_width(33),

                Field::make('text', PXA_key('page_title'), __('Título da Página', 'pixel-x-app'))
                    ->set_attribute('readOnly', 'true')
                    ->set_width(33),

                Field::make('text', PXA_key('content_name'), __('Nome do Conteúdo', 'pixel-x-app'))
                    ->set_attribute('readOnly', 'true')
                    ->set_width(33),
            ])
            ->add_tab(__('Evento', 'pixel-x-app'), [
                Field::make('text', PXA_key('event_name'), __('Nome do Evento', 'pixel-x-app'))
                    ->set_width(50)
                    ->set_attribute('readOnly', 'true'),

                Field::make('text', PXA_key('event_day'), __('Dia da Semana', 'pixel-x-app'))
                    ->set_width(50)
                    ->set_attribute('readOnly', 'true'),

                Field::make('text', PXA_key('event_day_in_month'), __('Dia do Mês', 'pixel-x-app'))
                    ->set_width(50)
                    ->set_attribute('readOnly', 'true'),

                Field::make('text', PXA_key('event_month'), __('Mês', 'pixel-x-app'))
                    ->set_width(50)
                    ->set_attribute('readOnly', 'true'),

                Field::make('text', PXA_key('event_time_interval'), __('Intervalo de Hora', 'pixel-x-app'))
                    ->set_width(50)
                    ->set_attribute('readOnly', 'true'),

                Field::make('text', PXA_key('event_time'), __('Data e Hora', 'pixel-x-app'))
                    ->set_help_text(__('Formato Timestamp', 'pixel-x-app'))
                    ->set_width(50)
                    ->set_attribute('readOnly', 'true'),

                Field::make('text', PXA_key('event_url'), __('URL do Evento', 'pixel-x-app'))
                    ->set_width(50)
                    ->set_attribute('readOnly', 'true'),

                Field::make('text', PXA_key('traffic_source'), __('Fonte de Tráfego', 'pixel-x-app'))
                    ->set_width(50)
                    ->set_attribute('readOnly', 'true'),
            ])
            ->add_tab(__('Parâmetros de URL', 'pixel-x-app'), [
                Field::make('text', PXA_key('utm_source'), __('utm_source', 'pixel-x-app'))
                    ->set_width(50)
                    ->set_attribute('readOnly', 'true'),

                Field::make('text', PXA_key('utm_medium'), __('utm_medium', 'pixel-x-app'))
                    ->set_width(50)
                    ->set_attribute('readOnly', 'true'),

                Field::make('text', PXA_key('utm_campaign'), __('utm_campaign', 'pixel-x-app'))
                    ->set_width(50)
                    ->set_attribute('readOnly', 'true'),

                Field::make('text', PXA_key('utm_id'), __('utm_id', 'pixel-x-app'))
                    ->set_width(50)
                    ->set_attribute('readOnly', 'true'),

                Field::make('text', PXA_key('utm_term'), __('utm_term', 'pixel-x-app'))
                    ->set_width(50)
                    ->set_attribute('readOnly', 'true'),

                Field::make('text', PXA_key('utm_content'), __('utm_content', 'pixel-x-app'))
                    ->set_width(50)
                    ->set_attribute('readOnly', 'true'),

                Field::make('text', PXA_key('src'), __('src', 'pixel-x-app'))
                    ->set_width(50)
                    ->set_attribute('readOnly', 'true'),

                Field::make('text', PXA_key('sck'), __('sck', 'pixel-x-app'))
                    ->set_width(50)
                    ->set_attribute('readOnly', 'true'),
            ])
            ->add_tab(__('Facebook Cookies', 'pixel-x-app'), [
                Field::make('text', PXA_key('fbc'), __('FBC', 'pixel-x-app'))
                    ->set_width(50)
                    ->set_attribute('readOnly', 'true'),

                Field::make('text', PXA_key('fbp'), __('FBP', 'pixel-x-app'))
                    ->set_width(50)
                    ->set_attribute('readOnly', 'true'),
            ])
            ->add_tab(__('Facebook API', 'pixel-x-app'), [
                Field::make('textarea', PXA_key('fb_api_playload'), __('Corpo de Requisição', 'pixel-x-app'))
                    ->set_attribute('readOnly', 'true'),

                Field::make('textarea', PXA_key('fb_api_response'), __('Resposta da API de Conversão', 'pixel-x-app'))
                    ->set_attribute('readOnly', 'true')
                    ->set_help_text(sprintf(
                        '%s <a href="" target="_blank">%s</a>',
                        __('Em caso de erro, confira o código do Erro junto ao Facebook', 'pixel-x-app'),
                        'https://developers.facebook.com/docs/graph-api/guides/error-handling/'
                    ))
            ]);
});
