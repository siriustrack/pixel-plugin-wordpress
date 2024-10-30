<?php

use Carbon_Fields\Container;
use Carbon_Fields\Field;

add_action('carbon_fields_register_fields', function () {
    return;
    if ( ! PXA_license_status()) {
        return;
    }

    Container::make('theme_options', __('Integrações (Legado)', 'pixel-x-app'))
        ->set_page_parent(PXA_PREFIX . 'dashboard')
        ->set_page_file(PXA_PREFIX . 'integrations')
        ->set_page_menu_position(6)
        ->add_tab(__('Ticto', 'pixel-x-app'), [
            Field::make('text', PXA_PREFIX . 'ticto_url', __('URL de Webhook', 'pixel-x-app'))
                ->set_help_text(__('Adicione essa URL na sua conta Ticto para cada produto que deseja rastrear as vendas.', 'pixel-x-app'))
                ->set_default_value(get_rest_url(null, PXA_DOMAIN . '/v1/ticto'))
                ->set_attribute('data-clipboard', get_rest_url(null, PXA_DOMAIN . '/v1/ticto'))
                ->set_attribute('readOnly', 'true')
                ->set_width(50),

            Field::make('text', PXA_PREFIX . 'ticto_token', __('Token Webhook', 'pixel-x-app'))
                ->set_help_text(sprintf(
                    __('Informe o Token do Webhook gerado pela Ticto <a href="%s" target="_blank">aqui</a>.', 'pixel-x-app'),
                    'https://dash.ticto.com.br/tools/webhook/create'
                ))
                ->set_attribute('type', 'password')
                ->set_width(50),

            Field::make('set', PXA_PREFIX . 'ticto_status', __('Desativar Integração para os Status', 'pixel-x-app'))
                ->set_options([
                    'abandoned_cart'    => __('Abandono de Carrinho', 'pixel-x-app') . ' = ' . EventEnum::INITIATE_CHECKOUT()->label(),
                    'bank_slip_created' => __('Boleto Gerado', 'pixel-x-app') . ' = ' . EventEnum::ADD_PAYMENT_INFO()->label(),
                    'pix_created'       => __('Pix Gerado', 'pixel-x-app') . ' = ' . EventEnum::ADD_PAYMENT_INFO()->label(),
                    'waiting_payment'   => __('Aguardando Pagamento', 'pixel-x-app') . ' = ' . EventEnum::ADD_PAYMENT_INFO()->label(),
                    'trial'             => __('Período de Testes', 'pixel-x-app') . ' = ' . EventEnum::START_TRIAL()->label(),
                    'authorized'        => __('Compra Aprovada', 'pixel-x-app') . ' = ' . EventEnum::PURCHASE()->label(),
                ])
        ])
        ->add_tab(__('Hotmart', 'pixel-x-app'), [
            Field::make('text', PXA_PREFIX . 'hotmart_url', __('URL de Webhook', 'pixel-x-app'))
                ->set_help_text(__('Adicione essa URL na sua conta Hotmart para todos produtos produtos que deseja rastrear as vendas.', 'pixel-x-app'))
                ->set_default_value(get_rest_url(null, PXA_DOMAIN . '/v1/hotmart'))
                ->set_attribute('data-clipboard', get_rest_url(null, PXA_DOMAIN . '/v1/hotmart'))
                ->set_attribute('readOnly', 'true')
                ->set_width(50),

            Field::make('text', PXA_PREFIX . 'hotmart_token', __('Token Webhook', 'pixel-x-app'))
                ->set_help_text(sprintf(
                    __('Informe o Token do Webhook gerado pela Hotmart <a href="%s" target="_blank">aqui</a>.', 'pixel-x-app'),
                    'https://app.hotmart.com/tools/webhook'
                ))
                ->set_attribute('type', 'password')
                ->set_width(50),

            Field::make('set', PXA_PREFIX . 'hotmart_status', __('Desativar Integração para os Status', 'pixel-x-app'))
                ->set_options([
                    'PURCHASE_OUT_OF_SHOPPING_CART' => __('Abandono de Carrinho', 'pixel-x-app') . ' = ' . EventEnum::INITIATE_CHECKOUT()->label(),
                    'PURCHASE_BILLET_PRINTED'       => __('Boleto / Pix Gerado', 'pixel-x-app') . ' = ' . EventEnum::ADD_PAYMENT_INFO()->label(),
                    'PURCHASE_APPROVED'             => __('Compra Aprovada', 'pixel-x-app') . ' = ' . EventEnum::PURCHASE()->label(),
                    // 'WAITING_PAYMENT'               => __('Aguardando Pagamento', 'pixel-x-app') . ' = ' . EventEnum::ADD_PAYMENT_INFO()->label(),
                    // 'STARTED'                       => __('Compra Iniciada', 'pixel-x-app') . ' = ' . EventEnum::ADD_PAYMENT_INFO()->label(),
                    // 'PRE_ORDER'                     => __('Período de Testes', 'pixel-x-app') . ' = ' . EventEnum::START_TRIAL()->label(),
                ])
        ])
        ->add_tab(__('Eduzz', 'pixel-x-app'), [
            Field::make('text', PXA_PREFIX . 'eduzz_url', __('URL de Webhook', 'pixel-x-app'))
                ->set_help_text(__('Adicione essa URL na sua conta Eduzz para todos produtos produtos que deseja rastrear as vendas.', 'pixel-x-app'))
                ->set_default_value(get_rest_url(null, PXA_DOMAIN . '/v1/eduzz'))
                ->set_attribute('data-clipboard', get_rest_url(null, PXA_DOMAIN . '/v1/eduzz'))
                ->set_attribute('readOnly', 'true')
                ->set_width(50),

            Field::make('text', PXA_PREFIX . 'eduzz_token', __('Origin Key', 'pixel-x-app'))
                ->set_help_text(sprintf(
                    __('Informe o Origin Key do Webhook gerado pela Eduzz <a href="%s" target="_blank">aqui</a>.', 'pixel-x-app'),
                    'https://orbita.eduzz.com/producer/config-api'
                ))
                ->set_attribute('type', 'password')
                ->set_width(50),

            Field::make('set', PXA_PREFIX . 'eduzz_status', __('Desativar Integração para os Status', 'pixel-x-app'))
                ->set_options([
                    'cart_abandonment'        => __('Abandono de Carrinho', 'pixel-x-app') . ' = ' . EventEnum::INITIATE_CHECKOUT()->label(),
                    'invoice_open'            => __('Boleto / Pix Gerado', 'pixel-x-app') . ' = ' . EventEnum::ADD_PAYMENT_INFO()->label(),
                    'invoice_waiting_payment' => __('Aguardando Pagamento', 'pixel-x-app') . ' = ' . EventEnum::ADD_PAYMENT_INFO()->label(),
                    'invoice_trial'           => __('Período de Testes', 'pixel-x-app') . ' = ' . EventEnum::START_TRIAL()->label(),
                    'invoice_paid'            => __('Compra Aprovada', 'pixel-x-app') . ' = ' . EventEnum::PURCHASE()->label(),
                ])
        ])
        ->add_tab(__('Kiwify', 'pixel-x-app'), [
            Field::make('text', PXA_PREFIX . 'kiwify_url', __('URL de Webhook', 'pixel-x-app'))
                ->set_help_text(__('Adicione essa URL na sua conta Kiwify para todos os produtos que deseja rastrear as vendas.', 'pixel-x-app'))
                ->set_default_value(get_rest_url(null, PXA_DOMAIN . '/v1/kiwify'))
                ->set_attribute('data-clipboard', get_rest_url(null, PXA_DOMAIN . '/v1/kiwify'))
                ->set_attribute('readOnly', 'true')
                ->set_width(50),

            Field::make('text', PXA_PREFIX . 'kiwify_token', __('Token Webhook', 'pixel-x-app'))
                ->set_help_text(sprintf(
                    __('Informe o Token do Webhook gerado pela Kiwify <a href="%s" target="_blank">aqui</a>.', 'pixel-x-app'),
                    'https://dashboard.kiwify.com.br/apps/webhooks/integrations'
                ))
                ->set_attribute('type', 'password')
                ->set_width(50),

            Field::make('set', PXA_PREFIX . 'kiwify_status', __('Desativar Integração para os Status', 'pixel-x-app'))
                ->set_options([
                    'abandoned_cart'  => __('Abandono de Carrinho', 'pixel-x-app') . ' = ' . EventEnum::INITIATE_CHECKOUT()->label(),
                    'waiting_payment' => __('Aguardando Pagamento', 'pixel-x-app') . ' = ' . EventEnum::ADD_PAYMENT_INFO()->label(),
                    'paid'            => __('Compra Aprovada', 'pixel-x-app') . ' = ' . EventEnum::PURCHASE()->label(),
                ])
        ])
//         ->add_tab(__('BlitzPay', 'pixel-x-app'), [
//             Field::make('text', PXA_PREFIX . 'blitzpay_url', __('URL de Webhook', 'pixel-x-app'))
//                 ->set_help_text(__('Adicione essa URL na sua conta BlitzPay para todos produtos produtos que deseja rastrear as vendas.', 'pixel-x-app'))
//                 ->set_default_value(get_rest_url(null, PXA_DOMAIN . '/v1/blitzpay'))
//                 ->set_attribute('readOnly', 'true')
//                 ->set_width(50),
//
//             Field::make('text', PXA_PREFIX . 'blitzpay_token', __('Token Webhook', 'pixel-x-app'))
//                 ->set_help_text(sprintf(
//                     __('Deixe em branco ou defina um Token para ser enviado pela BlitzPay <a href="%s" target="_blank">aqui</a>.', 'pixel-x-app'),
//                     'https://app.blitzpay.com.br/ferramentas/webhook'
//                 ))
//                 ->set_width(50),
//
//             Field::make('set', PXA_PREFIX . 'blitzpay_status', __('Desativar Integração para os Status', 'pixel-x-app'))
//                 ->set_options([
//                     'abandoned_cart'    => __('Abandono de Carrinho', 'pixel-x-app') . ' = ' . EventEnum::INITIATE_CHECKOUT()->label(),
//                     'bank_slip_created' => __('Boleto Gerado', 'pixel-x-app') . ' = ' . EventEnum::ADD_PAYMENT_INFO()->label(),
//                     'pix_created'       => __('Pix Gerado', 'pixel-x-app') . ' = ' . EventEnum::ADD_PAYMENT_INFO()->label(),
//                     'waiting_payment'   => __('Aguardando Pagamento', 'pixel-x-app') . ' = ' . EventEnum::ADD_PAYMENT_INFO()->label(),
//                     'trial'             => __('Período de Testes', 'pixel-x-app') . ' = ' . EventEnum::START_TRIAL()->label(),
//                     'authorized'        => __('Compra Aprovada', 'pixel-x-app') . ' = ' . EventEnum::PURCHASE()->label(),
//                 ])
//         ])
        ->add_tab(__('Digital Manager Guru', 'pixel-x-app'), [
            Field::make('text', PXA_PREFIX . 'dmg_url', __('URL de Webhook', 'pixel-x-app'))
                ->set_help_text(__('Adicione essa URL na sua conta Digital Manager Guru para todos produtos produtos que deseja rastrear as vendas.', 'pixel-x-app'))
                ->set_default_value(get_rest_url(null, PXA_DOMAIN . '/v1/dmg'))
                ->set_attribute('data-clipboard', get_rest_url(null, PXA_DOMAIN . '/v1/dmg'))
                ->set_attribute('readOnly', 'true')
                ->set_width(50),

            Field::make('text', PXA_PREFIX . 'dmg_token', __('Token Webhook', 'pixel-x-app'))
                ->set_help_text(sprintf(
                    __('Informe o Token API gerado pela Digital Manager Guru <a href="%s" target="_blank">aqui</a>.', 'pixel-x-app'),
                    'https://digitalmanager.guru/admin/myaccount'
                ))
                ->set_attribute('type', 'password')
                ->set_width(50),

            Field::make('set', PXA_PREFIX . 'dmg_status', __('Desativar Integração para os Status', 'pixel-x-app'))
                ->set_options([
                    'abandoned'       => __('Abandono de Carrinho', 'pixel-x-app') . ' = ' . EventEnum::INITIATE_CHECKOUT()->label(),
                    'billet_printed'  => __('Boleto Gerado', 'pixel-x-app') . ' = ' . EventEnum::ADD_PAYMENT_INFO()->label(),
                    'waiting_payment' => __('Aguardando Pagamento', 'pixel-x-app') . ' = ' . EventEnum::ADD_PAYMENT_INFO()->label(),
                    'trial'           => __('Período de Testes', 'pixel-x-app') . ' = ' . EventEnum::START_TRIAL()->label(),
                    'approved'        => __('Compra Aprovada', 'pixel-x-app') . ' = ' . EventEnum::PURCHASE()->label(),
                ])
        ])
        ->add_tab(__('Celetus', 'pixel-x-app'), [
            Field::make('text', PXA_PREFIX . 'celetus_url', __('URL de Webhook', 'pixel-x-app'))
                ->set_help_text(__('Adicione essa URL na sua conta Celetus para todos produtos produtos que deseja rastrear as vendas.', 'pixel-x-app'))
                ->set_default_value(get_rest_url(null, PXA_DOMAIN . '/v1/celetus'))
                ->set_attribute('data-clipboard', get_rest_url(null, PXA_DOMAIN . '/v1/celetus'))
                ->set_attribute('readOnly', 'true')
                ->set_width(50),

            Field::make('text', PXA_PREFIX . 'celetus_token', __('Token Webhook', 'pixel-x-app'))
                ->set_help_text(sprintf(
                    __('Informe o Token do Webhook gerado pela Celetus <a href="%s" target="_blank">aqui</a>.', 'pixel-x-app'),
                    'https://dash.celetus.com/apps/webhooks'
                ))
                ->set_attribute('type', 'password')
                ->set_width(50),

            Field::make('set', PXA_PREFIX . 'celetus_status', __('Desativar Integração para os Status', 'pixel-x-app'))
                ->set_options([
                    'AbandonedCheckout'        => __('Abandono de Carrinho', 'pixel-x-app') . ' = ' . EventEnum::INITIATE_CHECKOUT()->label(),
                    'BoletoGenerated'          => __('Boleto Gerado', 'pixel-x-app') . ' = ' . EventEnum::ADD_PAYMENT_INFO()->label(),
                    'PixGenerated'             => __('Pix Gerado', 'pixel-x-app') . ' = ' . EventEnum::ADD_PAYMENT_INFO()->label(),
                    'WaitingPayment'           => __('Aguardando Pagamento', 'pixel-x-app') . ' = ' . EventEnum::ADD_PAYMENT_INFO()->label(),
                    'ApprovedPurchase'         => __('Compra Aprovada', 'pixel-x-app') . ' = ' . EventEnum::PURCHASE()->label(),
                    'ApprovedPurchaseComplete' => __('Compra Completa', 'pixel-x-app') . ' = ' . EventEnum::PURCHASE()->label(),
                ])
        ])
        ->add_tab(__('Personalizado', 'pixel-x-app'), [
            Field::make('separator', PXA_PREFIX . 'separator_custom', __('Como Utilizar a Integração Personalizada?', 'pixel-x-app'))
                ->set_help_text(sprintf(
                    __('A integração personalizada, não é uma integração universal, ou seja, você precisa customizar como o webhook vai ser enviado para nosso sistema. <br>Siga a nossa documentação para realizar a integração, caso contrário será ignorado qualquer conteúdo enviado para a URL. <br><a href="%s" target="_blank">Documentação Integração Personalizada</a>', 'pixel-x-app'),
                    'https://pixelx.app/doc-integracao-personalizada'
                )),

            Field::make('text', PXA_PREFIX . 'custom_url', __('URL de Webhook', 'pixel-x-app'))
                ->set_help_text(__('Adicione essa URL para integrações personalizadas com plataformas que não temos integração nativa.', 'pixel-x-app'))
                ->set_default_value(get_rest_url(null, PXA_DOMAIN . '/v1/custom'))
                ->set_attribute('data-clipboard', get_rest_url(null, PXA_DOMAIN . '/v1/custom'))
                ->set_attribute('readOnly', 'true')
                ->set_width(50),

            Field::make('text', PXA_PREFIX . 'custom_token', __('Token Webhook', 'pixel-x-app'))
                ->set_help_text(__('Informe o Token no corpo da requisição.', 'pixel-x-app'))
                ->set_attribute('data-clipboard', PXA_get_custom_token())
                ->set_width(50)
                ->set_attribute('readOnly', 'true'),

            Field::make('set', PXA_PREFIX . 'custom_status', __('Desativar Integração para os Status', 'pixel-x-app'))
                ->set_options([
                    'abandoned_cart'      => __('Abandono de Carrinho', 'pixel-x-app') . ' = ' . EventEnum::INITIATE_CHECKOUT()->label(),
                    'billet_pix_generate' => __('Boleto / Pix Gerado', 'pixel-x-app') . ' = ' . EventEnum::ADD_PAYMENT_INFO()->label(),
                    'waiting_payment'     => __('Aguardando Pagamento', 'pixel-x-app') . ' = ' . EventEnum::ADD_PAYMENT_INFO()->label(),
                    'trial'               => __('Compra Aprovada', 'pixel-x-app') . ' = ' . EventEnum::PURCHASE()->label(),
                    'approved'            => __('Compra Completa', 'pixel-x-app') . ' = ' . EventEnum::PURCHASE()->label(),
                ])
        ]);
});
