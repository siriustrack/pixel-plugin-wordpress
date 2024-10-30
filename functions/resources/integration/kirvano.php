<?php

function PXA_integration_kirvano($request, $integration = null)
{
    $data       = $request->get_json_params() ?? $request->get_params();
    $data_token = data_get($data, 'token');

    $int_token       = data_get($integration, PXA_key('token'));
    $disabled_events = PXA_get_post_meta($integration['ID'], 'disabled_events', []);
    $undefined       = boolval(data_get($integration, PXA_key('undefined'), false));
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

    $status = EventEnum::kirvano(data_get($data, 'event'));

    $value = preg_replace('/[^\d,]/', '', data_get($data, 'total_price'));
    $value = str_replace(',', '.', $value);
    $value = floatval($value);

    if (in_array($status, $disabled_events) || ($undefined && $status == EventEnum::LEAD)) {
        return new WP_REST_Response([
            'status'   => 401,
            'response' => __('Evento Desativado para Integração', 'pixel-x-app'),
        ]);
    }

    $process = PXA_webhook_process([
        'event' => [
            'name'         => $status,
            'source'       => 'Webhook - ' . $type,
            'date'         => data_get($data, 'created_at'),
            'product_id'   => data_get($data, 'products.0.id'),
            'product_name' => data_get($data, 'products.0.name'),
            'offer_code'   => data_get($data, 'products.0.offer_id'),
            'offer_name'   => data_get($data, 'products.0.offer_name'),
            'value'        => money_format($value),
            // 'currency'   => data_get($data, 'currency'),
            // 'url'        => data_get($data, 'thank_you_page'),
        ],
        'lead' => [
            'id'    => data_get($data, 'utm.external_id'),
            'name'  => data_get($data, 'customer.name'),
            'email' => data_get($data, 'customer.email'),
            'phone' => data_get($data, 'customer.phone_number'),
            'doc'   => data_get($data, 'customer.document'),
            // 'ip'      => data_get($data, 'browser_ip'),
            'address' => [
                'street'        => data_get($data, 'customer.address.address'),
                'street_number' => data_get($data, 'customer.address.house_no'),
                'complement'    => data_get($data, 'customer.address.address2'),
                'neighborhood'  => data_get($data, 'customer.address.neighborhood'),
                'city'          => data_get($data, 'customer.address.city'),
                'state'         => data_get($data, 'customer.address.province'),
                'country'       => data_get($data, 'customer.address.country'),
                'country_code'  => data_get($data, 'customer.address.country_code'),
                'zip_code'      => data_get($data, 'customer.address.zip'),
            ]
        ],
        'tracking' => [
            'utm_source'   => data_get($data, 'utm.utm_source'),
            'utm_medium'   => data_get($data, 'utm.utm_medium'),
            'utm_campaign' => data_get($data, 'utm.utm_campaign'),
            'utm_content'  => data_get($data, 'utm.utm_content'),
            'utm_term'     => data_get($data, 'utm.utm_term'),
            'src'          => data_get($data, 'utm.src'),
            'utm_id'       => data_get($data, 'utm.utm_id'),
            'sck'          => data_get($data, 'utm.sck'),
            'fbc'          => data_get($data, 'utm.fbc'),
            'fbp'          => data_get($data, 'utm.fbp'),
        ],
    ]);

    return new WP_REST_Response([
        'status'   => 200,
        'response' => __('Requisição Processada', 'pixel-x-app'),
        'data'     => $process,
    ]);
}
