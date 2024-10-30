<?php

function PXA_integration_zippify($request, $integration = null)
{
    $data            = $request->get_json_params() ?? $request->get_params();
    $token           = data_get($integration, PXA_key('token'));
    $disabled_events = PXA_get_post_meta($integration['ID'], 'disabled_events', []);
    $undefined       = boolval(data_get($integration, PXA_key('undefined'), false));
    $type            = IntegrationTypeEnum::from(data_get($integration, PXA_key('type')))->label();

    if (
        ! PXA_license_status()
        // || data_get($data, 'chave_unica') != $token
    ) {
        return new WP_REST_Response([
            'status'   => 401,
            'response' => __('Requisição Não Autorizada', 'pixel-x-app'),
        ]);
    }

    $status = data_get($data, 'status');
    $status = EventEnum::zippify($status);

    if (in_array($status, $disabled_events) || ($undefined && $status == EventEnum::LEAD)) {
        return new WP_REST_Response([
            'status'   => 401,
            'response' => __('Evento Desativado para Integração', 'pixel-x-app'),
        ]);
    }

    $product = data_get($data, 'items.0');

    $process = PXA_webhook_process([
        'event' => [
            'source'       => 'Webhook - ' . $type,
            'name'         => $status,
            'date'         => data_get($data, 'created_at'),
            'product_id'   => data_get($product, 'product_hash'),
            'product_name' => data_get($product, 'title'),
            'offer_code'   => data_get($data, 'offer.hash'),
            'offer_name'   => data_get($data, 'offer.title'),
            'value'        => number_format(data_get($data, 'transaction.amount'), 2, '.', ''),
            'url'          => data_get($data, 'transaction.url'),
        ],
        'lead' => [
            // 'id'      => data_get($data, 'meta.external_id'),
            'name'  => data_get($data, 'customer.name'),
            'email' => data_get($data, 'customer.email'),
            'phone' => data_get($data, 'customer.phone'),
            'doc'   => data_get($data, 'customer.document'),
            // 'ip'      => data_get($data, 'ip'),
            // 'address' => [
            //     'street'        => data_get($data, 'customer.street_name'),
            //     'street_number' => data_get($data, 'customer.number'),
            //     'complement'    => data_get($data, 'customer.complement'),
            //     'city'          => data_get($data, 'customer.city'),
            //     'state'         => data_get($data, 'customer.state'),
            //     'zip_code'      => data_get($data, 'customer.zip_code'),
            //     'neighborhood'  => data_get($data, 'customer.neighborhood'),
            //     // 'country'       => data_get($data, 'customer.country'),
            // ]
        ],
        'tracking' => [
            'utm_source'   => data_get($data, 'tracking.utm_source'),
            'utm_medium'   => data_get($data, 'tracking.utm_medium'),
            'utm_campaign' => data_get($data, 'tracking.utm_campaign'),
            'utm_content'  => data_get($data, 'tracking.utm_content'),
            'utm_term'     => data_get($data, 'tracking.utm_term'),
            'src'          => data_get($data, 'tracking.src'),
            // 'utm_id'       => data_get($data, 'venda.utm_id'),
            // 'sck'          => data_get($data, 'venda.sck'),
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
