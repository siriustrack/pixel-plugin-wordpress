<?php

add_action('rest_api_init', function () {
    register_rest_route(PXA_DOMAIN . '/v1', '/celetus', [
        'methods'             => 'POST',
        'callback'            => 'PXA_integration_celetus',
        'permission_callback' => '__return_true',
    ]);
});

function PXA_integration_celetus($request, $integration = null)
{
    // $data = $request->get_params();
    $data       = $request->get_json_params();
    $data_token = $request->get_header('api-token');
    $token      = data_get($integration, PXA_key('token'), PXA_get_setting('celetus_token'));

    if ( ! PXA_license_status() || $data_token != $token) {
        return new WP_REST_Response([
            'status'   => 401,
            'response' => __('Requisição Não Autorizada', 'pixel-x-app'),
        ]);
    }

    $events = PXA_get_setting('celetus_status', []);
    $status = data_get($data, 'order_status');

    if (in_array($status, $events)) {
        return new WP_REST_Response([
            'status'   => 401,
            'response' => __('Evento Desativado para Integração', 'pixel-x-app'),
        ]);
    }

    // $query_params = data_get($data, 'query_params');
    // $query_params = explode('&', $query_params);
    // foreach ($query_params as $key => $query_param) {
    //     $query_params[$key] = explode('=', $query_param);
    // }

    $process = PXA_webhook_process([
        'event' => [
            'name'   => EventEnum::celetus($status),
            'date'   => current_time('timestamp'),
            'source' => __('Webhook - Celetus', 'pixel-x-app'),
            // 'url'    => data_get($data, 'page_checkout_url'),
            'product_id'   => data_get($data, 'items.0.code'),
            'product_name' => data_get($data, 'items.0.name'),
            // 'offer_code' => data_get($data, 'offer_code'),
            // 'offer_name' => data_get($data, 'item.offer_name'),
            'value' => data_get($data, 'charge.amount'),
            // 'curreny'    => data_get($data, 'trans_currency'),
        ],
        'lead' => [
            'id'      => data_get($data, 'external_id'),
            'name'    => data_get($data, 'customer.name'),
            'email'   => data_get($data, 'customer.email'),
            'phone'   => data_get($data, 'customer.phone'),
            'doc'     => data_get($data, 'customer.document'),
            'address' => [
                'street'        => data_get($data, 'customer.address.street'),
                'street_number' => data_get($data, 'customer.address.number'),
                'complement'    => data_get($data, 'customer.address.complement'),
                'city'          => data_get($data, 'customer.address.city'),
                'state'         => data_get($data, 'customer.address.state'),
                'country'       => data_get($data, 'customer.address.country'),
                // 'country_code'  => data_get($data, 'country'),
                'zip_code'     => data_get($data, 'customer.address.zip_code'),
                'neighborhood' => data_get($data, 'customer.address.neighborhood'),
            ]
        ],
        // 'tracking' => [
        //     'utm_source'   => data_get($query_param, 'utm_source'),
        //     'utm_medium'   => data_get($query_param, 'utm_medium'),
        //     'utm_campaign' => data_get($query_param, 'utm_campaign'),
        //     'utm_id'       => data_get($query_param, 'utm_id'),
        //     'utm_content'  => data_get($query_param, 'utm_content'),
        //     'utm_term'     => data_get($query_param, 'utm_term'),
        //     'src'          => data_get($query_param, 'src'),
        //     'sck'          => data_get($data, 'sck'),
        //     'fbc'          => data_get($query_param, 'fbc'),
        //     'fbp'          => data_get($query_param, 'fbp'),
        // ],
    ]);

    return new WP_REST_Response([
        'status'   => 200,
        'response' => __('Requisição Processada', 'pixel-x-app'),
        'data'     => $process,
    ]);
}
