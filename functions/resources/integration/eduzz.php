<?php

add_action('rest_api_init', function () {
    register_rest_route(PXA_DOMAIN . '/v1', '/eduzz', [
        'methods'             => 'POST',
        'callback'            => 'PXA_integration_eduzz',
        'permission_callback' => '__return_true',
    ]);
});

function PXA_integration_eduzz($request, $integration = null)
{
    // $data = $request->get_params();
    $data  = $request->get_json_params();
    $token = data_get($integration, PXA_key('token'), PXA_get_setting('eduzz_token'));

    if ( ! PXA_license_status() || data_get($data, 'origin') != $token) {
        return new WP_REST_Response([
            'status'   => 401,
            'response' => __('Requisição Não Autorizada', 'pixel-x-app'),
        ]);
    }

    $events = PXA_get_setting('eduzz_status', []);
    $status = data_get($data, 'type') == 'abandonment' ? 'cart_abandonment' : data_get($data, 'event_name');

    if (in_array($status, $events)) {
        return new WP_REST_Response([
            'status'   => 401,
            'response' => __('Evento Desativado para Integração', 'pixel-x-app'),
        ]);
    }

    $date  = data_get($data, 'trans_paiddate', data_get($data, 'trans_createdate')) . ' ' . data_get($data, 'trans_paidtime', data_get($data, 'trans_createtime'));
    $value = (data_get($data, 'trans_paid') > 0) ? data_get($data, 'trans_paid') : data_get($data, 'trans_value');

    $process = PXA_webhook_process([
        'event' => [
            'name'         => EventEnum::eduzz($status),
            'date'         => $date,
            'source'       => __('Webhook - Eduzz', 'pixel-x-app'),
            'url'          => data_get($data, 'page_checkout_url'),
            'product_id'   => data_get($data, 'product_cod'),
            'product_name' => data_get($data, 'product_name'),
            'offer_code'   => data_get($data, 'product_parent_cod'),
            // 'offer_name' => data_get($data, 'item.offer_name'),
            'value'   => number_format($value, 2, '.', ''),
            'curreny' => data_get($data, 'trans_currency'),
        ],
        'lead' => [
            // 'id'      => data_get($data, 'query_params.external_id'),
            'name'    => data_get($data, 'cus_name'),
            'email'   => data_get($data, 'cus_email'),
            'phone'   => data_get($data, 'cus_cel') ?? data_get($data, 'cus_tel'),
            'doc'     => data_get($data, 'cus_taxnumber'),
            'address' => [
                'street'        => data_get($data, 'cus_address'),
                'street_number' => data_get($data, 'cus_address_number'),
                'complement'    => data_get($data, 'cus_address_comp'),
                'city'          => data_get($data, 'cus_address_city'),
                'state'         => data_get($data, 'cus_address_state'),
                'country'       => data_get($data, 'cus_address_country'),
                'zip_code'      => data_get($data, 'cus_address_zip_code'),
                'neighborhood'  => data_get($data, 'cus_address_district'),
            ]
        ],
        'tracking' => [
            'utm_source'   => data_get($data, 'tracker_utm_source'),
            'utm_medium'   => data_get($data, 'tracker_utm_medium'),
            'utm_campaign' => data_get($data, 'tracker_utm_campaign'),
            // 'utm_id'       => $utm_id,
            'utm_content' => data_get($data, 'tracker_utm_content'),
            // 'utm_term'     => $utm_term,
            // 'src'          => $src,
            // 'sck'          => $sck,
            // 'fbc'          => $fbc,
            // 'fbp'          => $fbp,
        ],
    ]);

    return new WP_REST_Response([
        'status'   => 200,
        'response' => __('Requisição Processada', 'pixel-x-app'),
        'data'     => $process,
    ]);
}
