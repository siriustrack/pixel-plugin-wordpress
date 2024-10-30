<?php

function PXA_integration_blitzpay($request, $integration = null)
{
    $data = $request->get_json_params() ?? $request->get_params();

    if ( ! PXA_license_status() || data_get($data, 'tid') != PXA_get_custom_token()) {
        return new WP_REST_Response([
            'status'   => 401,
            'response' => __('Requisição Não Autorizada', 'pixel-x-app'),
        ]);
    }

    $events = PXA_get_setting('blitzpay_status', []);
    $status = data_get($data, 'status');

    if (in_array($status, $events)) {
        return new WP_REST_Response([
            'status'   => 401,
            'response' => __('Evento Desativado para Integração', 'pixel-x-app'),
        ]);
    }

    $query_params = data_get($data, 'query_params');
    $query_params = explode('&', $query_params);
    foreach ($query_params as $key => $query_param) {
        $query_params[$key] = explode('=', $query_param);
    }

    $process = PXA_webhook_process([
        'event' => [
            'name'   => EventEnum::blitzpay($status),
            'date'   => data_get($data, 'timestamp'),
            'source' => __('Webhook - BlitzPay', 'pixel-x-app'),
            // 'url'    => data_get($data, 'page_checkout_url'),
            'product_id'   => data_get($data, 'product_id'),
            'product_name' => data_get($data, 'product_name'),
            'offer_code'   => data_get($data, 'offer_code'),
            'offer_name'   => data_get($data, 'item.offer_name'),
            'value'        => number_format(data_get($data, 'full_price'), 2, '.', ''),
            'curreny'      => data_get($data, 'trans_currency'),
        ],
        'lead' => [
            'id'      => data_get($data, 'external_id'),
            'name'    => data_get($data, 'name'),
            'email'   => data_get($data, 'email'),
            'phone'   => data_get($data, 'phone'),
            'doc'     => data_get($data, 'document'),
            'address' => [
                'street'        => data_get($data, 'cus_address'),
                'street_number' => data_get($data, 'cus_address_number'),
                'complement'    => data_get($data, 'cus_address_comp'),
                'city'          => data_get($data, 'cus_address_city'),
                'state'         => data_get($data, 'cus_address_state'),
                'country'       => data_get($data, 'cus_address_country'),
                'country_code'  => data_get($data, 'country'),
                'zip_code'      => data_get($data, 'cus_address_zip_code'),
                'neighborhood'  => data_get($data, 'cus_address_district'),
            ]
        ],
        'tracking' => [
            'utm_source'   => data_get($query_param, 'utm_source'),
            'utm_medium'   => data_get($query_param, 'utm_medium'),
            'utm_campaign' => data_get($query_param, 'utm_campaign'),
            'utm_id'       => data_get($query_param, 'utm_id'),
            'utm_content'  => data_get($query_param, 'utm_content'),
            'utm_term'     => data_get($query_param, 'utm_term'),
            'src'          => data_get($query_param, 'src'),
            'sck'          => data_get($data, 'sck'),
            'fbc'          => data_get($query_param, 'fbc'),
            'fbp'          => data_get($query_param, 'fbp'),
        ],
    ]);

    return new WP_REST_Response([
        'status'   => 200,
        'response' => __('Requisição Processada', 'pixel-x-app'),
        'data'     => $process,
    ]);
}
