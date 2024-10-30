<?php

function PXA_integration_braip($request, $integration = null)
{
    $data            = $request->get_json_params() ?? $request->get_params();
    $token           = data_get($integration, PXA_key('token'));
    $disabled_events = PXA_get_post_meta($integration['ID'], 'disabled_events', []);
    $undefined       = boolval(data_get($integration, PXA_key('undefined'), false));
    $type            = IntegrationTypeEnum::from(data_get($integration, PXA_key('type')))->label();

    if ( ! PXA_license_status() || data_get($data, 'basic_authentication') != $token) {
        return new WP_REST_Response([
            'status'   => 401,
            'response' => __('Requisição Não Autorizada', 'pixel-x-app'),
        ]);
    }

    if (data_get($data, 'type') == 'ABANDONO') {
        $status = 0;
    } else {
        $status = data_get($data, 'trans_status_code');
    }

    if (in_array($status, $disabled_events) || ($undefined && $status == EventEnum::LEAD)) {
        return new WP_REST_Response([
            'status'   => 401,
            'response' => __('Evento Desativado para Integração', 'pixel-x-app'),
        ]);
    }

    $process = PXA_webhook_process([
        'event' => [
            'name'         => EventEnum::braip($status),
            'date'         => data_get($data, 'trans_updatedate'),
            'source'       => 'Webhook - ' . $type,
            'product_id'   => data_get($data, 'product_key'),
            'product_name' => data_get($data, 'product_name'),
            'offer_code'   => data_get($data, 'plan_key'),
            'offer_name'   => data_get($data, 'plan_name'),
            'value'        => number_format(data_get($data, 'trans_value') / 100, 2, '.', ''),
            // 'url'          => data_get($data, 'checkout_url'),
        ],
        'lead' => [
            'id'      => data_get($data, 'meta.external_id'),
            'name'    => data_get($data, 'client_name'),
            'email'   => data_get($data, 'client_email'),
            'phone'   => data_get($data, 'client_cel'),
            'doc'     => data_get($data, 'client_documment'),
            'address' => [
                'street'        => data_get($data, 'client_address'),
                'street_number' => data_get($data, 'client_address_number'),
                'complement'    => data_get($data, 'client_address_comp'),
                'city'          => data_get($data, 'client_address_city'),
                'state'         => data_get($data, 'client_address_state'),
                'country'       => data_get($data, 'client_address_country'),
                'zip_code'      => data_get($data, 'client_zip_code'),
                'neighborhood'  => data_get($data, 'client_address_district'),
            ]
        ],
        'tracking' => [
            'utm_source'   => data_get($data, 'meta.utm_source'),
            'utm_medium'   => data_get($data, 'meta.utm_medium'),
            'utm_campaign' => data_get($data, 'meta.utm_campaign'),
            'utm_id'       => data_get($data, 'meta.utm_id'),
            'utm_content'  => data_get($data, 'meta.utm_content'),
            'utm_term'     => data_get($data, 'meta.utm_term'),
            'src'          => data_get($data, 'meta.src'),
            'sck'          => data_get($data, 'meta.sck'),
            'fbc'          => data_get($data, 'meta.fbc'),
            // 'fbp'          => data_get($data, 'fbp'),
        ],
    ]);

    return new WP_REST_Response([
        'status'   => 200,
        'response' => __('Requisição Processada', 'pixel-x-app'),
        'data'     => $process,
    ]);
}
