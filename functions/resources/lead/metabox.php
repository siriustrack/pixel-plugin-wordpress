<?php

use Carbon_Fields\Container;
use Carbon_Fields\Field;

add_action('carbon_fields_register_fields', function () {
    //     global $pagenow;
    //
    //     $post_type = data_get($_GET, 'post_type', data_get($_POST, 'post_type', get_post_type(data_get($_GET, 'post'))));
    //     if ( ! in_array($pagenow, ['post.php', 'post-new.php']) || $post_type != PXA_key('lead')) {
    //         return;
    //     }

    Container::make('post_meta', __('Informações do Lead', 'pixel-x-app'))
        ->where('post_type', '=', PXA_key('lead'))
        ->set_priority('high')
        ->add_fields([
            Field::make('text', PXA_key('id'), __('ID', 'pixel-x-app'))
                ->set_attribute('readOnly', 'true')
                // ->set_default_value(wp_generate_uuid4())
                ->set_help_text(__('O ID é gerado automaticamente, para identificação única.', 'pixel-x-app')),

            Field::make('text', PXA_key('name'), __('Nome', 'pixel-x-app'))
                ->set_width(50),

            Field::make('text', PXA_key('email'), __('Email', 'pixel-x-app'))
                ->set_attribute('type', 'email')
                ->set_width(50),

            Field::make('text', PXA_key('phone'), __('Telefone', 'pixel-x-app'))
                ->set_width(50),

            Field::make('text', PXA_key('document'), __('Documento', 'pixel-x-app'))
                ->set_width(50),

            /**
             * Location
             */
            Field::make('separator', PXA_key('separator_adress'), __('Endereço', 'pixel-x-app'))
                ->set_help_text(__('As informações de endereço são salvas e atualizadas sempre a partir da última interação.', 'pixel-x-app'))
                ->set_width(100),

            Field::make('text', PXA_key('ip'), __('IP', 'pixel-x-app'))
                ->set_width(50)
                ->set_attribute('readOnly', 'true'),

            Field::make('text', PXA_key('device'), __('Dispositivo', 'pixel-x-app'))
                ->set_width(50)
                ->set_attribute('readOnly', 'true'),

            Field::make('text', PXA_key('adress_street'), __('Endereço', 'pixel-x-app'))
                ->set_width(50)
                ->set_attribute('readOnly', 'true'),

            Field::make('text', PXA_key('adress_street_number'), __('Número', 'pixel-x-app'))
                ->set_width(20)
                ->set_attribute('readOnly', 'true'),

            Field::make('text', PXA_key('adress_complement'), __('Complemento', 'pixel-x-app'))
                ->set_width(30)
                ->set_attribute('readOnly', 'true'),

            Field::make('text', PXA_key('adress_city'), __('Cidade', 'pixel-x-app'))
                ->set_width(33)
                ->set_attribute('readOnly', 'true'),

            Field::make('text', PXA_key('adress_state'), __('Estado', 'pixel-x-app'))
                ->set_width(33)
                ->set_attribute('readOnly', 'true'),

            Field::make('text', PXA_key('adress_zipcode'), __('Código Postal', 'pixel-x-app'))
                ->set_width(33)
                ->set_attribute('readOnly', 'true'),

            Field::make('text', PXA_key('adress_country_name'), __('País', 'pixel-x-app'))
                ->set_width(25)
                ->set_attribute('readOnly', 'true'),

            Field::make('text', PXA_key('adress_country'), __('Código do País', 'pixel-x-app'))
                ->set_width(25)
                ->set_attribute('readOnly', 'true'),
        ]);

    Container::make('post_meta', __('Parâmetros', 'pixel-x-app'))
        ->where('post_type', '=', PXA_key('lead'))
        ->set_priority('high')
        ->add_fields([
            Field::make('separator', PXA_key('separator_first'), __('Primeiros Parâmetros', 'pixel-x-app'))
                ->set_help_text(__('Parâmetros capturados na primeira interação do usuário.', 'pixel-x-app'))
                ->set_width(100),

            Field::make('text', PXA_key('first_fbc'), __('FBC', 'pixel-x-app'))
                ->set_width(50)
                ->set_attribute('readOnly', 'true'),

            Field::make('text', PXA_key('fbp'), __('FBP', 'pixel-x-app'))
                ->set_width(50)
                ->set_attribute('readOnly', 'true'),

            Field::make('text', PXA_key('first_utm_source'), __('UTM Source', 'pixel-x-app'))
                ->set_width(30)
                ->set_attribute('readOnly', 'true'),

            Field::make('text', PXA_key('first_utm_medium'), __('UTM Medium', 'pixel-x-app'))
                ->set_width(30)
                ->set_attribute('readOnly', 'true'),

            Field::make('text', PXA_key('first_utm_campaign'), __('UTM Campaign', 'pixel-x-app'))
                ->set_width(30)
                ->set_attribute('readOnly', 'true'),

            Field::make('text', PXA_key('first_utm_id'), __('UTM Id', 'pixel-x-app'))
                ->set_width(30)
                ->set_attribute('readOnly', 'true'),

            Field::make('text', PXA_key('first_utm_content'), __('UTM Content', 'pixel-x-app'))
                ->set_width(30)
                ->set_attribute('readOnly', 'true'),

            Field::make('text', PXA_key('first_utm_term'), __('UTM Term', 'pixel-x-app'))
                ->set_width(30)
                ->set_attribute('readOnly', 'true'),

            Field::make('text', PXA_key('first_src'), __('SRC', 'pixel-x-app'))
                ->set_width(30)
                ->set_attribute('readOnly', 'true'),

            Field::make('text', PXA_key('first_sck'), __('SCK', 'pixel-x-app'))
                ->set_width(30)
                ->set_attribute('readOnly', 'true'),

            /**
             * Facebook
             */

            Field::make('separator', PXA_key('separator_last'), __('Últimos Parâmetros', 'pixel-x-app'))
                ->set_help_text(__('Parâmetros capturados na última interação do usuário.', 'pixel-x-app'))
                ->set_width(100),

            Field::make('text', PXA_key('fbc'), __('FBC', 'pixel-x-app'))
                ->set_width(30)
                ->set_attribute('readOnly', 'true'),

            Field::make('text', PXA_key('src'), __('SRC', 'pixel-x-app'))
                ->set_width(30)
                ->set_attribute('readOnly', 'true'),

            Field::make('text', PXA_key('sck'), __('SCK', 'pixel-x-app'))
                ->set_width(30)
                ->set_attribute('readOnly', 'true'),

            Field::make('text', PXA_key('utm_source'), __('UTM Source', 'pixel-x-app'))
                ->set_width(30)
                ->set_attribute('readOnly', 'true'),

            Field::make('text', PXA_key('utm_medium'), __('UTM Medium', 'pixel-x-app'))
                ->set_width(30)
                ->set_attribute('readOnly', 'true'),

            Field::make('text', PXA_key('utm_campaign'), __('UTM Campaign', 'pixel-x-app'))
                ->set_width(30)
                ->set_attribute('readOnly', 'true'),

            Field::make('text', PXA_key('utm_id'), __('UTM Id', 'pixel-x-app'))
                ->set_width(30)
                ->set_attribute('readOnly', 'true'),

            Field::make('text', PXA_key('utm_content'), __('UTM Content', 'pixel-x-app'))
                ->set_width(30)
                ->set_attribute('readOnly', 'true'),

            Field::make('text', PXA_key('utm_term'), __('UTM Term', 'pixel-x-app'))
                ->set_width(30)
                ->set_attribute('readOnly', 'true'),
        ]);
});
