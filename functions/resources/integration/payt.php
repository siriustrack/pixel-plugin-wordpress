<?php

function PXA_integration_payt($request, $integration = null)
{
    $data       = $request->get_json_params() ?? $request->get_params();
    $data_token = data_get($data, 'integration_key');

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

    $status = EventEnum::payt(data_get($data, 'status'));

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
            'date'         => data_get($data, 'updated_at') ?? data_get($data, 'started_at'),
            'product_id'   => data_get($data, 'product.code'),
            'product_name' => data_get($data, 'product.name'),
            // 'offer_code'   => data_get($data, 'offer.hash'),
            // 'offer_name'   => data_get($data, 'offer.title'),
            'value' => money_format(data_get($data, 'transaction.total_price') / 100),
            // 'currency' => data_get($data, 'currency'),
            'url' => data_get($data, 'link.url'),
        ],
        'lead' => [
            'id'    => data_get($data, 'link.query_params.external_id'),
            'name'  => data_get($data, 'customer.name'),
            'email' => data_get($data, 'customer.email'),
            'phone' => data_get($data, 'customer.phone'),
            'doc'   => data_get($data, 'customer.doc'),
            // 'ip'      => data_get($data, 'browser_ip'),
            'address' => [
                'street'        => data_get($data, 'customer.billing_address.street'),
                'street_number' => data_get($data, 'customer.billing_address.street_number'),
                'complement'    => data_get($data, 'customer.billing_address.complement'),
                'neighborhood'  => data_get($data, 'customer.billing_address.district'),
                'city'          => data_get($data, 'customer.billing_address.city'),
                'state'         => data_get($data, 'customer.billing_address.state'),
                'zip_code'      => data_get($data, 'customer.billing_address.zipcode'),
                'country_code'  => data_get($data, 'customer.billing_address.country'),
                // 'country'       => data_get($data, 'customer.billing_address.country'),
            ]
        ],
        'tracking' => [
            // 'utm_id'       => data_get($data, 'venda.utm_id'),
            'utm_source'   => data_get($data, 'link.sources.utm_source'),
            'utm_medium'   => data_get($data, 'link.sources.utm_medium'),
            'utm_campaign' => data_get($data, 'link.sources.utm_campaign'),
            'utm_content'  => data_get($data, 'link.sources.utm_content'),
            'utm_term'     => data_get($data, 'link.sources.utm_term'),
            'src'          => data_get($data, 'link.sources.src'),
            'sck'          => data_get($data, 'link.query_params.sck'),
            'fbc'          => data_get($data, 'link.query_params.fbc'),
            'fbp'          => data_get($data, 'link.query_params.fbp'),
        ],
    ]);

    return new WP_REST_Response([
        'status'   => 200,
        'response' => __('Requisição Processada', 'pixel-x-app'),
        'data'     => $process,
    ]);
}
