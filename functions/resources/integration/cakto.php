<?php

function PXA_integration_cakto($request, $integration = null)
{
    $data       = $request->get_json_params() ?? $request->get_params();
    $data_token = data_get($data, 'secret');
    // $data_token = $request->get_header('x-webhook-token') ?? $request->get_header('X-WEBHOOK-TOKEN');

    $int_token       = PXA_get_custom_token();
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

    $status = EventEnum::{$type}(data_get($data, 'event'));

    if (in_array($status, $disabled_events) || ($undefined && $status == EventEnum::LEAD)) {
        return new WP_REST_Response([
            'status'   => 401,
            'response' => __('Evento Desativado para Integração', 'pixel-x-app'),
        ]);
    }

    $data  = data_get($data, 'data');
    $value = data_get($data, 'amount') ?: data_get($data, 'offer.price');

    $process = PXA_webhook_process([
        'event' => [
            'name'         => $status,
            'source'       => 'Webhook - ' . $type,
            'date'         => data_get($data, 'paidAt') ?: data_get($data, 'createdAt'),
            'product_id'   => data_get($data, 'product.id'),
            'product_name' => data_get($data, 'product.name'),
            'offer_code'   => data_get($data, 'offer.id'),
            'offer_name'   => data_get($data, 'offer.name'),
            'value'        => money_format($value),
            // 'currency'     => data_get($data, 'currency'),
            'url' => data_get($data, 'checkoutUrl'),
        ],
        'lead' => [
            // 'id'      => data_get($data, 'meta.external_id'),
            'name'  => data_get($data, 'customer.name') ?: data_get($data, 'customerName'),
            'email' => data_get($data, 'customer.email') ?: data_get($data, 'customerEmail'),
            'phone' => data_get($data, 'customer.phone') ?: data_get($data, 'customerCellphone'),
            // 'doc'     => data_get($data, 'customer.cpf') ?? data_get($data, 'customer.cnpj'),
            // 'ip'      => data_get($data, 'browser_ip'),
            // 'address' => [
            //     'street'        => data_get($data, 'address.address'),
            //     'street_number' => data_get($data, 'address.house_no'),
            //     'complement'    => data_get($data, 'address.address2'),
            //     'neighborhood'  => data_get($data, 'address.neighborhood'),
            //     'city'          => data_get($data, 'address.city'),
            //     'state'         => data_get($data, 'address.province'),
            //     'country'       => data_get($data, 'customer.country'),
            //     'country_code'  => data_get($data, 'customer.country_code'),
            //     'zip_code'      => data_get($data, 'address.zip'),
            // ]
        ],
        // 'tracking' => [
        //     // 'utm_source'   => data_get($data, 'utm_source'),
        //     // 'utm_medium'   => data_get($data, 'utm_medium'),
        //     // 'utm_campaign' => data_get($data, 'utm_campaign'),
        //     // 'utm_content'  => data_get($data, 'utm_content'),
        //     // 'utm_term'     => data_get($data, 'utm_term'),
        //     // 'src'          => data_get($data, 'src'),
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
