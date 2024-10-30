<?php

add_action('rest_api_init', function () {
    register_rest_route(PXA_DOMAIN . '/v1', '/kiwify', [
        'methods'             => 'POST',
        'callback'            => 'PXA_integration_kiwify',
        'permission_callback' => '__return_true',
    ]);
});

function PXA_integration_kiwify($request, $integration = null)
{
    $data = $request->get_json_params() ?? $request->get_params();
    // $data_token = data_get($data, 'order.cart_token');
    // $data_token = $request->get_header('signature');

    // $int_token       = data_get($integration, PXA_key('token'),PXA_get_setting('kiwify_token'));
    $disabled_events = data_get($integration, PXA_key('disabled_events'), []);
    $undefined       = data_get($integration, PXA_key('undefined'), false);
    $type            = IntegrationTypeEnum::from(data_get($integration, PXA_key('type')))->label();

    if (
        ! PXA_license_status()
        // || $data_token != $int_token
    ) {
        return new WP_REST_Response([
            'status'   => 401,
            'response' => __('Requisição Não Autorizada', 'pixel-x-app'),
        ]);
    }

    $data   = data_get($data, 'order', data_get($data, 'cart', $data));
    $status = data_get($data, 'order_status', data_get($data, 'status'));
    $status = EventEnum::kiwify($status);

    if (in_array($status, $disabled_events)) {
        return new WP_REST_Response([
            'status'   => 401,
            'response' => __('Evento Desativado para Integração', 'pixel-x-app'),
        ]);
    }

    $utmParams = [
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_id',
        'utm_content',
        'utm_term',
        'src',
        'sck',
        'fbc',
        'fbp',
    ];

    foreach ($utmParams as $param) {
        $$param = data_get($data, "TrackingParameters.$param", data_get($data, "query_params.$param"));
        if ($$param == 'Não Informado') {
            unset($$param);
        }

        if (is_array($$param)) {
            $$param = $$param[0];
        }
    }

    $process = PXA_webhook_process([
        'event' => [
            'name'         => $status,
            'source'       => 'Webhook - ' . $type,
            'date'         => data_get($data, 'created_at'),
            'product_id'   => data_get($data, 'Product.product_id', data_get($data, 'product_id')),
            'product_name' => data_get($data, 'Product.product_name', data_get($data, 'product_name')),
            'offer_code'   => data_get($data, 'order_ref'),
            'value'        => money_format(data_get($data, 'Commissions.charge_amount') / 100),
            // 'offer_name'   => data_get($data, 'offer.title'),
            // 'currency' => data_get($data, 'currency'),
            // 'url'      => data_get($data, 'thank_you_page'),
        ],
        'lead' => [
            'id'      => data_get($data, 'TrackingParameters.external_id'),
            'name'    => data_get($data, 'Customer.full_name', data_get($data, 'name')),
            'email'   => data_get($data, 'Customer.email', data_get($data, 'email')),
            'phone'   => data_get($data, 'Customer.mobile', data_get($data, 'phone')),
            'doc'     => data_get($data, 'Customer.CPF', data_get($data, 'Customer.CNPJ', data_get($data, 'cpf'))),
            'ip'      => data_get($data, 'Customer.ip'),
            'address' => [
                // 'street'        => data_get($data, 'address.address'),
                // 'street_number' => data_get($data, 'address.house_no'),
                // 'complement'    => data_get($data, 'address.address2'),
                // 'neighborhood'  => data_get($data, 'address.neighborhood'),
                // 'city'          => data_get($data, 'address.city'),
                // 'state'         => data_get($data, 'address.province'),
                // 'country'       => data_get($data, 'customer.country'),
                // 'country_code'  => data_get($data, 'customer.country_code'),
                // 'zip_code'      => data_get($data, 'address.zip'),
            ]
        ],
        'tracking' => [
            'utm_source'   => $utm_source,
            'utm_medium'   => $utm_medium,
            'utm_campaign' => $utm_campaign,
            'utm_id'       => $utm_id,
            'utm_content'  => $utm_content,
            'utm_term'     => $utm_term,
            'src'          => $src,
            'sck'          => $sck,
            'fbc'          => $fbc,
            'fbp'          => $fbp,
        ],
    ]);

    return new WP_REST_Response([
        'status'   => 200,
        'response' => __('Requisição Processada', 'pixel-x-app'),
        'data'     => $process,
    ]);
}
