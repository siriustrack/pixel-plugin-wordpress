<?php

function PXA_integration_doppus($request, $integration = null)
{
    $data       = $request->get_json_params() ?? $request->get_params();
    $data_token = $request->get_header('doppus_token');

    $int_token       = data_get($integration, PXA_key('token'));
    $disabled_events = data_get($integration, PXA_key('disabled_events'), []);
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

    $status = data_get($data, 'status.code', data_get($data, 'status'));
    $status = EventEnum::doppus(strtolower($status));

    if (in_array($status, $disabled_events) || ($undefined && $status == EventEnum::LEAD)) {
        return new WP_REST_Response([
            'status'   => 401,
            'response' => __('Evento Desativado para Integração', 'pixel-x-app'),
        ]);
    }

    $date  = data_get($data, 'registration_date', data_get($data, 'transaction.registration_date'));
    $value = data_get($data, 'transaction.total_value') ?: (data_get($data, 'transaction.total') / 100);

    $process = PXA_webhook_process([
        'event' => [
            'name'         => $status,
            'date'         => $date,
            'source'       => 'Webhook - ' . $type,
            'product_id'   => data_get($data, 'items.0.code'),
            'product_name' => data_get($data, 'items.0.name'),
            'offer_code'   => data_get($data, 'items.0.sales_plan_code', data_get($data, 'items.0.offer')),
            'offer_name'   => data_get($data, 'items.0.sales_plan_name'),
            'value'        => money_format($value),
            'url'          => data_get($data, 'links.url_checkout'),
        ],
        'lead' => [
            'id'      => data_get($data, 'tracking.external_id'),
            'ip'      => data_get($data, 'customer.ip_address'),
            'name'    => data_get($data, 'customer.name'),
            'email'   => data_get($data, 'customer.email'),
            'phone'   => data_get($data, 'customer.phone'),
            'doc'     => data_get($data, 'customer.doc'),
            'address' => [
                'street'        => data_get($data, 'address.address'),
                'street_number' => data_get($data, 'address.number'),
                'complement'    => data_get($data, 'address.complement'),
                'city'          => data_get($data, 'address.city'),
                'state'         => data_get($data, 'address.state'),
                'zip_code'      => data_get($data, 'address.zipcode'),
                'neighborhood'  => data_get($data, 'address.neighborhood'),
                // 'country'       => data_get($data, 'address.pais'),
            ]
        ],
        'tracking' => [
            'utm_source'   => data_get($data, 'tracking.utm_source'),
            'utm_medium'   => data_get($data, 'tracking.utm_medium'),
            'utm_campaign' => data_get($data, 'tracking.utm_campaign'),
            'utm_content'  => data_get($data, 'tracking.utm_content'),
            'utm_term'     => data_get($data, 'tracking.utm_term'),
            'src'          => data_get($data, 'tracking.src'),
            'sck'          => data_get($data, 'tracking.sck'),
            // 'utm_id'       => data_get($data, 'venda.utm_id'),
            // 'fbc'          => data_get($data, 'venda.fbc'),
            // 'fbp'          => data_get($data, 'fbp'),
        ],
    ]);

    return new WP_REST_Response([
        'status'   => 200,
        'response' => __('Requisição Processada', 'pixel-x-app'),
        'data'     => $process,
    ]);
}
