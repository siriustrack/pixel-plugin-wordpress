<?php

function PXA_integration_green($request, $integration = null)
{
    $data       = $request->get_json_params()             ?? $request->get_params();
    $data_token = $request->get_header('x-webhook-token') ?? $request->get_header('X-WEBHOOK-TOKEN');

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

    $status = EventEnum::green(data_get($data, 'currentStatus', data_get($data, 'event')));

    if (in_array($status, $disabled_events) || ($undefined && $status == EventEnum::LEAD)) {
        return new WP_REST_Response([
            'status'   => 401,
            'response' => __('Evento Desativado para Integração', 'pixel-x-app'),
        ]);
    }

    // Recorrência
    // $recurrence_number = data_get($data, 'purchase.recurrence_number');
    // $predicted_ltv     = PXA_get_setting('predicted_ltv');
    // if ($recurrence_number > 1 && data_get($data, 'type') == 'contract') {
    //     $status = EventEnum::SUBSCRIBE;
    //
    //     if (($value * $recurrence_number) > $predicted_ltv) {
    //         $predicted_ltv = $value * $recurrence_number;
    //     }
    // }

    $client = data_get($data, 'client', data_get($data, 'lead'));
    $sale   = data_get($data, 'sale', data_get($data, 'currentSale'));

    // Tracking
    $query_params = [
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_id',
        'utm_content',
        'utm_term',
        'src',
        'sck',
        'fbc',
        'fbp',
    ];
    foreach (data_get($data, 'saleMetas') as $meta) {
        $param = data_get($meta, 'meta_key');
        if (in_array($param, $query_params)) {
            $$param = data_get($meta, 'meta_value');
        }
    }

    $process = PXA_webhook_process([
        'event' => [
            'name'         => $status,
            'source'       => 'Webhook - ' . $type,
            'date'         => str_before(data_get($sale, 'updated_at'), '.'),
            'product_id'   => data_get($data, 'product.id'),
            'product_name' => data_get($data, 'product.name'),
            'offer_code'   => data_get($sale, 'coupon.id'),
            'offer_name'   => data_get($sale, 'coupon.name'),
            'value'        => money_format(data_get($sale, 'amount')),
            // 'predicted_ltv' => money_format($predicted_ltv),
            // 'currency' => data_get($data, 'currency'),
            'url' => data_get($data, 'product.thank_you_page'),
        ],
        'lead' => [
            // 'id'      => data_get($client, 'meta.external_id'),
            'name'  => data_get($client, 'name'),
            'email' => data_get($client, 'email'),
            'phone' => data_get($client, 'cellphone'),
            'doc'   => data_get($client, 'cpf_cnpj'),
            // 'ip'      => data_get($client, 'browser_ip'),
            'address' => [
                'street'        => data_get($client, 'street'),
                'street_number' => data_get($client, 'number'),
                'complement'    => data_get($client, 'complement'),
                'neighborhood'  => data_get($client, 'neighborhood'),
                'city'          => data_get($client, 'city'),
                'state'         => data_get($client, 'uf'),
                'zip_code'      => data_get($client, 'zipcode'),
                // 'country'       => data_get($client, 'country'),
                // 'country_code'  => data_get($client, 'country_code'),
            ]
        ],
        'tracking' => [
            'utm_source'   => $utm_source ?: null,
            'utm_medium'   => $utm_medium ?: null,
            'utm_campaign' => $utm_campaign ?: null,
            'utm_id'       => $utm_id ?: null,
            'utm_content'  => $utm_content ?: null,
            'utm_term'     => $utm_term ?: null,
            'src'          => $src ?: null,
            'sck'          => $sck ?: null,
            'fbc'          => $fbc ?: null,
            'fbp'          => $fbp ?: null,
        ],
    ]);

    return new WP_REST_Response([
        'status'   => 200,
        'response' => __('Requisição Processada', 'pixel-x-app'),
        'data'     => $process,
    ]);
}
