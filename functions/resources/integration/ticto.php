<?php

add_action('rest_api_init', function () {
    register_rest_route(PXA_DOMAIN . '/v1', '/ticto', [
        'methods'             => 'POST',
        'callback'            => 'PXA_integration_ticto',
        'permission_callback' => '__return_true',
    ]);
});

function PXA_integration_ticto($request, $integration = null)
{
    $data       = $request->get_json_params() ?? $request->get_params();
    $data_token = data_get($data, 'token');
    // $data_token = $request->get_header('x-hotmart-hottok') ?? $request->get_header('X-WEBHOOK-TOKEN');

    $int_token       = data_get($integration, PXA_key('token'), PXA_get_setting('ticto_token'));
    $disabled_events = PXA_get_post_meta($integration['ID'], 'disabled_events', []);
    $undefined       = boolval(data_get($integration, PXA_key('undefined'), false));
    $type            = IntegrationTypeEnum::from(data_get($integration, PXA_key('type')))->label();

    if (
        ! PXA_license_status()
        || $data_token != $int_token
    ) {
        return new WP_REST_Response([
            'status'   => 401,
            'response' => __('Requisição Não Autorizada', 'pixel-x-app'),
        ]);
    }

    // Status
    $status = EventEnum::ticto(data_get($data, 'status'));

    if (in_array($status, $disabled_events) || ($undefined && $status == EventEnum::LEAD)) {
        return new WP_REST_Response([
            'status'   => 401,
            'response' => __('Evento Desativado para Integração', 'pixel-x-app'),
        ]);
    }

    // Tracking
    $utmParams = [
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_id',
        'utm_content',
        'utm_term',
        'src',
        'sck',
    ];

    $queryParams = [
        'fbc',
        'fbp',
    ];

    foreach ($utmParams as $param) {
        $$param = data_get($data, "tracking.$param", data_get($data, "query_params.$param"));
        if ($$param == 'Não Informado') {
            unset($$param);
        }
    }

    foreach ($queryParams as $param) {
        $$param = data_get($data, "query_params.$param");
        if ($$param == 'Não Informado') {
            unset($$param);
        }
    }

    // Recorrência
    $recurrence_number = data_get($data, 'subscriptions.0.successful_charges');
    $predicted_ltv     = PXA_get_setting('predicted_ltv');
    if ($recurrence_number > 1) {
        $status           = EventEnum::SUBSCRIBE;
        $recurrence_value = data_get($data, 'item.0.amount', 0) * $recurrence_number;

        if ($recurrence_value > $predicted_ltv) {
            $predicted_ltv = $recurrence_value;
        }
    }

    $process = PXA_webhook_process([
        'event' => [
            'source'        => 'Webhook - ' . $type,
            'name'          => $status,
            'date'          => data_get($data, 'status_date'),
            'url'           => data_get($data, 'checkout_url'),
            'product_id'    => data_get($data, 'item.product_id'),
            'product_name'  => data_get($data, 'item.product_name'),
            'offer_code'    => data_get($data, 'item.offer_code'),
            'offer_name'    => data_get($data, 'item.offer_name'),
            'value'         => money_format(data_get($data, 'order.paid_amount') / 100),
            'predicted_ltv' => money_format($predicted_ltv),
        ],
        'lead' => [
            'id'      => data_get($data, 'query_params.external_id'),
            'name'    => data_get($data, 'customer.name'),
            'email'   => data_get($data, 'customer.email'),
            'phone'   => data_get($data, 'customer.phone.ddi') . data_get($data, 'customer.phone.ddd') . data_get($data, 'customer.phone.number'),
            'doc'     => data_get($data, 'customer.cpf') ?? data_get($data, 'customer.cnpj'),
            'address' => [
                'street'        => data_get($data, 'customer.address.street'),
                'street_number' => data_get($data, 'customer.address.street_number'),
                'complement'    => data_get($data, 'customer.address.complement'),
                'city'          => data_get($data, 'customer.address.city'),
                'state'         => data_get($data, 'customer.address.state'),
                'country'       => data_get($data, 'customer.address.country'),
                'zip_code'      => data_get($data, 'customer.address.zip_code'),
                'neighborhood'  => data_get($data, 'customer.address.neighborhood'),
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
