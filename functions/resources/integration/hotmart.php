<?php

add_action('rest_api_init', function () {
    register_rest_route(PXA_DOMAIN . '/v1', '/hotmart', [
        'methods'             => 'POST',
        'callback'            => 'PXA_integration_hotmart',
        'permission_callback' => '__return_true',
    ]);
});

function PXA_integration_hotmart($request, $integration = null)
{
    $data       = $request->get_json_params()              ?? $request->get_params();
    $data_token = $request->get_header('x-hotmart-hottok') ?? $request->get_header('X-WEBHOOK-TOKEN');
    // $data_token = data_get($data, 'order.cart_token');

    $int_token       = data_get($integration, PXA_key('token'), PXA_get_setting('hotmart_token'));
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
    $status = EventEnum::hotmart(data_get($data, 'event'));
    $data   = data_get($data, 'data', $data);

    if (in_array($status, $disabled_events) || ($undefined && $status == EventEnum::LEAD)) {
        return new WP_REST_Response([
            'status'   => 401,
            'response' => __('Evento Desativado para Integração', 'pixel-x-app'),
        ]);
    }

    $timestamp = data_get($data, 'purchase.approved_date')             ?? data_get($data, 'purchase.order_date') ?? data_get($data, 'creation_date');
    $value     = data_get($data, 'purchase.full_price.value')          ?? data_get($data, 'purchase.original_offer_price.value');
    $currency  = data_get($data, 'purchase.full_price.currency_value') ?? data_get($data, 'purchase.original_offer_price.currency_value');

    // Recorrência
    $recurrence_number = data_get($data, 'purchase.recurrence_number');
    $predicted_ltv     = PXA_get_setting('predicted_ltv');
    if ($recurrence_number > 1 && data_get($data, 'subscription.status') == 'ACTIVE') {
        $status = EventEnum::SUBSCRIBE;

        if (($value * $recurrence_number) > $predicted_ltv) {
            $predicted_ltv = $value * $recurrence_number;
        }
    }

    $process = PXA_webhook_process([
        'event' => [
            'source'        => 'Webhook - ' . $type,
            'name'          => $status,
            'date'          => $timestamp / 1000,
            'product_id'    => data_get($data, 'product.id'),
            'product_name'  => data_get($data, 'product.name'),
            'offer_code'    => data_get($data, 'offer.code') ?? data_get($data, 'purchase.offer.code'),
            'value'         => money_format($value),
            'predicted_ltv' => money_format($predicted_ltv),
            'currency'      => $currency,
            // 'offer_name'   => data_get($data, 'offer.title'),
            // 'url'      => data_get($data, 'thank_you_page'),
        ],
        'lead' => [
            // 'id'      => data_get($data, 'meta.external_id'),
            'name'    => data_get($data, 'buyer.name'),
            'email'   => data_get($data, 'buyer.email'),
            'phone'   => data_get($data, 'buyer.phone', data_get($data, 'buyer.checkout_phone')),
            'doc'     => data_get($data, 'buyer.document'),
            'ip'      => data_get($data, 'browser_ip'),
            'address' => [
                'street'        => data_get($data, 'buyer.address.address'),
                'street_number' => data_get($data, 'buyer.address.number'),
                'complement'    => data_get($data, 'buyer.address.complement'),
                'neighborhood'  => data_get($data, 'buyer.address.neighborhood'),
                'city'          => data_get($data, 'buyer.address.city'),
                'state'         => data_get($data, 'buyer.address.state'),
                'country'       => data_get($data, 'checkout_country.name', data_get($data, 'buyer.address.country')),
                'country_code'  => data_get($data, 'checkout_country.iso', data_get($data, 'buyer.address.country_iso')),
                'zip_code'      => data_get($data, 'buyer.address.zipcode'),
            ]
        ],
        'tracking' => [
            'utm_source'   => data_get($data, 'utm_source'),
            'utm_medium'   => data_get($data, 'utm_medium'),
            'utm_campaign' => data_get($data, 'utm_campaign'),
            'utm_content'  => data_get($data, 'utm_content'),
            'utm_term'     => data_get($data, 'utm_term'),
            'utm_id'       => data_get($data, 'venda.utm_id'),
            'src'          => data_get($data, 'purchase.origin.src'),
            'sck'          => data_get($data, 'purchase.origin.sck'),
            'fbc'          => data_get($data, 'venda.fbc'),
            'fbp'          => data_get($data, 'fbp'),
        ],
    ]);

    return new WP_REST_Response([
        'status'   => 200,
        'response' => __('Requisição Processada', 'pixel-x-app'),
        'data'     => $process,
    ]);
}
