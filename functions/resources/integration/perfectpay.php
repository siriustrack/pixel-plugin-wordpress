<?php

function PXA_integration_perfectpay($request, $integration = null)
{
    $data       = $request->get_json_params() ?? $request->get_params();
    $data_token = data_get($data, 'token');

    $int_token       = data_get($integration, PXA_key('token'));
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

    $status = EventEnum::perfectpay(data_get($data, 'sale_status_enum'));

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
            'date'         => data_get($data, 'date_approved') ?? data_get($data, 'date_created'),
            'product_id'   => data_get($data, 'product.code'),
            'product_name' => data_get($data, 'product.name'),
            'offer_code'   => data_get($data, 'coupon_code') ?? data_get($data, 'plan.code'),
            'offer_name'   => data_get($data, 'plan.name'),
            'value'        => number_format(data_get($data, 'sale_amount'), 2, '.', ''),
            // 'currency' => data_get($data, 'currency'),
            // 'url'      => data_get($data, 'thank_you_page'),
        ],
        'lead' => [
            // 'id'      => data_get($data, 'meta.external_id'),
            'name'    => data_get($data, 'customer.full_name'),
            'email'   => data_get($data, 'customer.email'),
            'phone'   => data_get($data, 'customer.phone_area_code') . data_get($data, 'customer.phone_number'),
            'doc'     => data_get($data, 'customer.identification_number'),
            'ip'      => data_get($data, 'browser_ip'),
            'address' => [
                'street'        => data_get($data, 'address.street_name'),
                'street_number' => data_get($data, 'address.street_number'),
                'complement'    => data_get($data, 'address.complement'),
                'neighborhood'  => data_get($data, 'address.district'),
                'city'          => data_get($data, 'address.city'),
                'state'         => data_get($data, 'address.state'),
                'country'       => data_get($data, 'customer.country'),
                'country_code'  => data_get($data, 'customer.country_code'),
                'zip_code'      => data_get($data, 'address.zip_code'),
            ]
        ],
        'tracking' => [
            'utm_source'   => data_get($data, 'metadata.utm_source'),
            'utm_medium'   => data_get($data, 'metadata.utm_medium'),
            'utm_campaign' => data_get($data, 'metadata.utm_campaign'),
            'utm_content'  => data_get($data, 'metadata.utm_content'),
            'utm_term'     => data_get($data, 'metadata.utm_term'),
            'src'          => data_get($data, 'metadata.src'),
            'fbp'          => data_get($data, 'metadata.fbp'),
            'fbc'          => data_get($data, 'metadata.fbclid') ?: data_get($data, 'metadata.fbc'),
            'sck'          => data_get($data, 'metadata.sck'),
            // 'utm_id'       => data_get($data, 'metadata.venda.utm_id'),
        ],
    ]);

    return new WP_REST_Response([
        'status'   => 200,
        'response' => __('Requisição Processada', 'pixel-x-app'),
        'data'     => $process,
    ]);
}
