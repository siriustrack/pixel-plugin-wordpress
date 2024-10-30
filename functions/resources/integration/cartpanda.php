<?php

function PXA_integration_cartpanda($request, $integration = null)
{
    $data       = $request->get_json_params() ?? $request->get_params();
    $data_token = data_get($data, 'order.cart_token');

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

    $status = EventEnum::cartpanda(data_get($data, 'event'));
    $data   = data_get($data, 'order');

    if (in_array($status, $disabled_events) || ($undefined && $status == EventEnum::LEAD)) {
        return new WP_REST_Response([
            'status'   => 401,
            'response' => __('Evento Desativado para Integração', 'pixel-x-app'),
        ]);
    }

    foreach (data_get($data, 'tracking_parameters') as $parameter) {
        $name            = data_get($parameter, 'parameter_name');
        $tracking[$name] = data_get($parameter, 'parameter_value');
    }

    $process = PXA_webhook_process([
        'event' => [
            'name'         => $status,
            'source'       => 'Webhook - ' . $type,
            'date'         => data_get($data, 'updated_at') ?? data_get($data, 'created_at'),
            'product_id'   => data_get($data, 'line_items.0.product_id'),
            'product_name' => data_get($data, 'line_items.0.title'),
            // 'offer_code'   => data_get($data, 'offer.hash'),
            // 'offer_name'   => data_get($data, 'offer.title'),
            'value'    => number_format(data_get($data, 'payment.actual_price_paid'), 2, '.', ''),
            'currency' => data_get($data, 'payment.actual_price_paid_currency'),
            'url'      => data_get($data, 'thank_you_page'),
        ],
        'lead' => [
            'id'      => data_get($tracking, 'external_id'),
            'name'    => data_get($data, 'customer.full_name'),
            'email'   => data_get($data, 'customer.email'),
            'phone'   => data_get($data, 'customer.phone'),
            'doc'     => data_get($data, 'customer.cpf') ?? data_get($data, 'customer.cnpj'),
            'ip'      => data_get($data, 'browser_ip'),
            'device'  => data_get($data, 'user_agent'),
            'address' => [
                'street'        => data_get($data, 'address.address'),
                'street_number' => data_get($data, 'address.house_no'),
                'complement'    => data_get($data, 'address.address2'),
                'neighborhood'  => data_get($data, 'address.neighborhood'),
                'city'          => data_get($data, 'address.city'),
                'state'         => data_get($data, 'address.province'),
                'country'       => data_get($data, 'customer.country'),
                'country_code'  => data_get($data, 'customer.country_code'),
                'zip_code'      => data_get($data, 'address.zip'),
            ]
        ],
        'tracking' => [
            'utm_source'   => data_get($tracking, 'utm_source'),
            'utm_medium'   => data_get($tracking, 'utm_medium'),
            'utm_campaign' => data_get($tracking, 'utm_campaign'),
            'utm_content'  => data_get($tracking, 'utm_content'),
            'utm_term'     => data_get($tracking, 'utm_term'),
            'utm_id'       => data_get($tracking, 'utm_id'),
            'src'          => data_get($tracking, 'src'),
            'sck'          => data_get($tracking, 'sck'),
            'fbc'          => data_get($tracking, 'fbc'),
            'fbp'          => data_get($tracking, 'fbp'),
        ],
    ]);

    return new WP_REST_Response([
        'status'   => 200,
        'response' => __('Requisição Processada', 'pixel-x-app'),
        'data'     => $process,
    ]);
}
