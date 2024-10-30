<?php

add_action('rest_api_init', function () {
    register_rest_route(PXA_DOMAIN . '/v1', '/dmg', [
        'methods'             => 'POST',
        'callback'            => 'PXA_integration_dmg',
        'permission_callback' => '__return_true',
    ]);
});

function PXA_integration_dmg($request, $integration = null)
{
    // $data = $request->get_params();
    $data  = $request->get_json_params();
    $token = data_get($integration, PXA_key('token'), PXA_get_setting('dmg_token'));

    if ( ! PXA_license_status() || data_get($data, 'api_token') != $token) {
        return new WP_REST_Response([
            'status'   => 401,
            'response' => __('Requisição Não Autorizada', 'pixel-x-app'),
        ]);
    }

    $status = EventEnum::dmg(data_get($data, 'status'));
    $events = PXA_get_setting('dmg_status', []);

    if (in_array($status, $events)) {
        return new WP_REST_Response([
            'status'   => 401,
            'response' => __('Evento Desativado para Integração', 'pixel-x-app'),
        ]);
    }

    $value = data_get($data, 'payment.total'); // product.total_value || invoice.value

    // Recorrência
    $recurrence_status = data_get($data, 'subscription.last_status');
    $recurrence_number = data_get($data, 'subscription.charged_times');
    $predicted_ltv     = PXA_get_setting('predicted_ltv');
    if ($recurrence_number > 1 && $recurrence_status == 'active') {
        $status = EventEnum::SUBSCRIBE;

        if (($value * $recurrence_number) > $predicted_ltv) {
            $predicted_ltv = $value * $recurrence_number;
        }
    }

    $process = PXA_webhook_process([
        'event' => [
            'source'       => __('Webhook - Digital Manager Guru', 'pixel-x-app'),
            'name'         => $status,
            'date'         => data_get($data, 'dates.ordered_at'),
            'url'          => data_get($data, 'checkout_url'),
            'product_id'   => data_get($data, 'product.id'),
            'product_name' => data_get($data, 'product.name'),
            // 'offer_code'   => data_get($data, 'item.offer_code'),
            // 'offer_name'   => data_get($data, 'item.offer_name'),
            'value'         => money_format($value),
            'currency'      => data_get($data, 'payment.currency'),
            'predicted_ltv' => money_format($predicted_ltv),
        ],
        'lead' => [
            'id'      => data_get($data, 'contact.id'),
            'name'    => data_get($data, 'contact.name'),
            'email'   => data_get($data, 'contact.email'),
            'phone'   => data_get($data, 'contact.phone_local_code') . data_get($data, 'contact.phone_number'),
            'doc'     => data_get($data, 'contact.doc'),
            'address' => [
                'street'        => data_get($data, 'contact.address'),
                'street_number' => data_get($data, 'contact.address_number'),
                'complement'    => data_get($data, 'contact.address_comp'),
                'city'          => data_get($data, 'contact.address_city'),
                'state'         => data_get($data, 'contact.address_state'),
                'country'       => data_get($data, 'contact.address_country'),
                'zip_code'      => data_get($data, 'contact.address_zip_code'),
                'neighborhood'  => data_get($data, 'contact.address_district'),
            ]
        ],
        'tracking' => [
            'utm_source'   => data_get($data, 'source.utm_source'),
            'utm_medium'   => data_get($data, 'source.utm_medium'),
            'utm_campaign' => data_get($data, 'source.utm_campaign'),
            // 'utm_id'       => data_get($data, 'contact'),
            'utm_content' => data_get($data, 'source.utm_content'),
            'utm_term'    => data_get($data, 'source.utm_term'),
            'src'         => data_get($data, 'source.source', data_get($data, 'trackings.source')),
            'sck'         => data_get($data, 'trackings.checkout_source '),
            // 'fbc'         => data_get($data, 'contact'),
            // 'fbp'         => data_get($data, 'contact'),
        ],
    ]);

    return new WP_REST_Response([
        'status'   => 200,
        'response' => __('Requisição Processada', 'pixel-x-app'),
        'data'     => $process,
    ]);
}
