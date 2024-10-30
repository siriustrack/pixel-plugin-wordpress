<?php

add_action('rest_api_init', function () {
    register_rest_route(PXA_DOMAIN . '/v1', '/custom', [
        'methods'             => 'POST',
        'callback'            => 'PXA_integration_custom',
        'permission_callback' => '__return_true',
    ]);
});

function PXA_integration_custom($request)
{
    $data = $request->get_json_params();

    if (blank($data)) {
        $data = $request->get_params();
    }

    if ( ! PXA_license_status() || data_get($data, 'token') != PXA_get_custom_token()) {
        return new WP_REST_Response([
            'status'   => 401,
            'response' => __('Requisição Não Autorizada', 'pixel-x-app'),
        ], 401);
    }

    $events = PXA_get_setting('custom_status', []);
    $status = data_get($data, 'event.status') ?? data_get($data, 'event_status');

    if (in_array($status, $events)) {
        return new WP_REST_Response([
            'status'   => 401,
            'response' => __('Evento Desativado para Integração', 'pixel-x-app'),
        ], 401);
    }

    $query_params = [
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

    foreach ($query_params as $param) {
        $$param = data_get($data, "tracking.$param") ?? data_get($data, "tracking_$param");
    }

    $value = data_get($data, 'event.value') ?? data_get($data, 'event_value');

    $process = PXA_webhook_process([
        'event' => [
            'name'         => data_get($data, 'event.name') ?? data_get($data, 'event_name') ?? EventEnum::custom($status),
            'source'       => __('Webhook - Pixel X App', 'pixel-x-app'),
            'date'         => data_get($data, 'event.date')         ?? data_get($data, 'event_date'),
            'url'          => data_get($data, 'event.url')          ?? data_get($data, 'event_url'),
            'product_id'   => data_get($data, 'event.product_id')   ?? data_get($data, 'event_product_id'),
            'product_name' => data_get($data, 'event.product_name') ?? data_get($data, 'event_product_name'),
            'offer_code'   => data_get($data, 'event.offer_code')   ?? data_get($data, 'event_offer_code'),
            'offer_name'   => data_get($data, 'event.offer_name')   ?? data_get($data, 'event_offer_name'),
            'value'        => $value ? money_format($value) : null,
            'currency'     => data_get($data, 'event.currency') ?? data_get($data, 'event_currency'),
        ],
        'lead' => [
            'id'      => data_get($data, 'lead.id')         ?? data_get($data, 'lead_id'),
            'name'    => data_get($data, 'lead.name')       ?? data_get($data, 'lead_name'),
            'email'   => data_get($data, 'lead.email')      ?? data_get($data, 'lead_email'),
            'phone'   => data_get($data, 'lead.phone')      ?? data_get($data, 'lead_phone'),
            'doc'     => data_get($data, 'lead.doc')        ?? data_get($data, 'lead_doc'),
            'ip'      => data_get($data, 'lead.ip')         ?? data_get($data, 'lead_ip'),
            'device'  => data_get($data, 'lead.user_agent') ?? data_get($data, 'lead_user_agent'),
            'address' => [
                'street'        => data_get($data, 'lead.address.street')        ?? data_get($data, 'lead_address_street'),
                'street_number' => data_get($data, 'lead.address.street_number') ?? data_get($data, 'lead_address_street_number'),
                'complement'    => data_get($data, 'lead.address.complement')    ?? data_get($data, 'lead_address_complement'),
                'neighborhood'  => data_get($data, 'lead.address.neighborhood')  ?? data_get($data, 'lead_address_neighborhood'),
                'city'          => data_get($data, 'lead.address.city')          ?? data_get($data, 'lead_address_city'),
                'state'         => data_get($data, 'lead.address.state')         ?? data_get($data, 'lead_address_state'),
                'country'       => data_get($data, 'lead.address.country')       ?? data_get($data, 'lead_address_country'),
                'country_code'  => data_get($data, 'lead.address.country_code')  ?? data_get($data, 'lead_address_country_code'),
                'zip_code'      => data_get($data, 'lead.address.zip_code')      ?? data_get($data, 'lead_address_zip_code'),
            ]
        ],
        'tracking' => [
            'utm_source'   => $utm_source   ?? null,
            'utm_medium'   => $utm_medium   ?? null,
            'utm_campaign' => $utm_campaign ?? null,
            'utm_id'       => $utm_id       ?? null,
            'utm_content'  => $utm_content  ?? null,
            'utm_term'     => $utm_term     ?? null,
            'src'          => $src          ?? null,
            'sck'          => $sck          ?? null,
            'fbc'          => $fbc          ?? null,
            'fbp'          => $fbp          ?? null,
        ],
    ]);

    return new WP_REST_Response([
        'status'   => 200,
        'response' => __('Requisição Processada', 'pixel-x-app'),
        'data'     => $process,
    ]);
}
