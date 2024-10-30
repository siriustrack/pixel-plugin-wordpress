<?php

function PXA_integration_hubla($request, $integration = null)
{
    $data       = $request->get_json_params() ?? $request->get_params();
    $data_token = $request->get_header('x-hubla-token');

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

    $status = EventEnum::{$type}(data_get($data, 'type'));
    $data   = data_get($data, 'event');

    if (in_array($status, $disabled_events) || ($undefined && $status == EventEnum::LEAD)) {
        return new WP_REST_Response([
            'status'   => 401,
            'response' => __('Evento Desativado para Integração', 'pixel-x-app'),
        ]);
    }

    $process = PXA_webhook_process([
        'event' => [
            'name'         => $status,
            'date'         => data_get($data, 'paidAt') ?? data_get($data, 'createdAt'),
            'source'       => 'Webhook - ' . $type,
            'product_id'   => data_get($data, 'productId')   ?? data_get($data, 'groupId'),
            'product_name' => data_get($data, 'productName') ?? data_get($data, 'groupName'),
            'offer_code'   => data_get($data, 'offerId'),
            'offer_name'   => data_get($data, 'offerName'),
            'value'        => number_format(data_get($data, 'totalAmount'), 2, '.', ''),
            'url'          => data_get($data, 'url'),
        ],
        'lead' => [
            // 'id'      => data_get($data, 'meta.external_id'),
            // 'ip'      => data_get($data, 'ip'),
            'name'  => data_get($data, 'userName'),
            'email' => data_get($data, 'userEmail'),
            'phone' => data_get($data, 'userPhone'),
            'doc'   => data_get($data, 'userDocument'),
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
        // 'tracking' => [
        //     'utm_source'   => data_get($data, 'utm_source'),
        //     'utm_medium'   => data_get($data, 'utm_medium'),
        //     'utm_campaign' => data_get($data, 'utm_campaign'),
        //     'utm_content'  => data_get($data, 'utm_content'),
        //     'utm_term'     => data_get($data, 'utm_term'),
        //     'src'          => data_get($data, 'src'),
        //     // 'utm_id'       => data_get($data, 'venda.utm_id'),
        //     // 'sck'          => data_get($data, 'venda.sck'),
        //     // 'fbc'          => data_get($data, 'venda.fbc'),
        //     // 'fbp'          => data_get($data, 'fbp'),
        // ],
    ]);

    return new WP_REST_Response([
        'status'   => 200,
        'response' => __('Requisição Processada', 'pixel-x-app'),
        'data'     => $process,
    ]);
}
