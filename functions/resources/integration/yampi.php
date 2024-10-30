<?php

function PXA_integration_yampi($request, $integration = null)
{
    $data       = $request->get_json_params() ?? $request->get_params();
    $data_token = $request->get_header('x-yampi-hmac-sha256');
    // $data_token = data_get($data, 'order.cart_token');

    $int_token       = data_get($integration, PXA_key('token'));
    $disabled_events = PXA_get_post_meta($integration['ID'], 'disabled_events', []);
    $undefined       = boolval(data_get($integration, PXA_key('undefined'), false));
    $type            = IntegrationTypeEnum::from(data_get($integration, PXA_key('type')))->label();
    $signature       = yampi_signature($data, $int_token);

    if (
        ! PXA_license_status()
        || $data_token != $signature
    ) {
        return new WP_REST_Response([
            'status'   => 401,
            'response' => __('Requisição Não Autorizada', 'pixel-x-app'),
        ]);
    }

    $status   = EventEnum::yampi(data_get($data, 'event'));
    $data     = data_get($data, 'resource', $data);
    $customer = data_get($data, 'customer.data');
    $address  = data_get($data, 'shipping_address.data');
    $product  = data_get($data, 'items.data.0.sku.data');
    $value    = data_get($data, 'totalizers.total', data_get($data, 'value_total'));

    if (in_array($status, $disabled_events) || ($undefined && $status == EventEnum::LEAD)) {
        return new WP_REST_Response([
            'status'   => 401,
            'response' => __('Evento Desativado para Integração', 'pixel-x-app'),
        ]);
    }

    $process = PXA_webhook_process([
        'event' => [
            'source'       => 'Webhook - ' . $type,
            'name'         => $status,
            'date'         => data_get($data, 'created_at.date'),
            'product_id'   => data_get($product, 'product_id'),
            'product_name' => data_get($product, 'title'),
            'offer_code'   => data_get($product, 'sku'),
            // 'offer_name'   => data_get($product, 'offer.title'),
            'value'    => number_format($value, 2, '.', ''),
            'currency' => data_get($data, 'currency'),
            'url'      => data_get($data, 'simulate_url', data_get($product, 'purchase_url')),
        ],
        'lead' => [
            // 'id'      => data_get($data, 'meta.external_id'),
            'name'    => data_get($customer, 'name'),
            'email'   => data_get($customer, 'email'),
            'phone'   => data_get($customer, 'phone.full_number'),
            'doc'     => data_get($customer, 'cpf', data_get($customer, 'cnpj')),
            'ip'      => data_get($customer, 'ip'),
            'address' => [
                'street'        => data_get($address, 'street'),
                'street_number' => data_get($address, 'number'),
                'complement'    => data_get($address, 'complement'),
                'neighborhood'  => data_get($address, 'neighborhood'),
                'city'          => data_get($address, 'city'),
                'state'         => data_get($address, 'state'),
                'country_code'  => data_get($address, 'country'),
                'zip_code'      => data_get($address, 'zipcode'),
                // 'country'       => data_get($address, 'country'),
            ]
        ],
        'tracking' => [
            'utm_source'   => data_get($data, 'utm_source'),
            'utm_medium'   => data_get($data, 'utm_medium'),
            'utm_campaign' => data_get($data, 'utm_campaign'),
            'utm_content'  => data_get($data, 'utm_content'),
            'utm_term'     => data_get($data, 'utm_term'),
            'src'          => data_get($data, 'src'),
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

function yampi_signature(array $body, $webHookSecret)
{
    $payload = json_encode($body);

    return base64_encode(hash_hmac('sha256', $payload, $webHookSecret, true));
}
