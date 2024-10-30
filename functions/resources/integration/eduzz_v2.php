<?php

function PXA_integration_eduzz_v2($request, $integration = null)
{
    $data       = $request->get_json_params() ?? $request->get_params();
    $data_token = data_get($data, 'data.producer.id');

    $int_token       = data_get($integration, PXA_key('token'));
    $disabled_events = data_get($integration, PXA_key('disabled_events'), []);
    $undefined       = data_get($integration, PXA_key('undefined'), false);
    $type            = IntegrationTypeEnum::from(data_get($integration, PXA_key('type')))->label();

    // $data_signature = $request->get_header('x-signature');
    // $signature      = hash_hmac('sha256', $request->get_body(), $int_token);

    if (
        ! PXA_license_status()
        || $data_token != $int_token
    ) {
        return new WP_REST_Response([
            'status'   => 401,
            'response' => __('Requisição Não Autorizada', 'pixel-x-app'),
        ]);
    }

    $data   = data_get($data, 'data', $data);
    $status = EventEnum::eduzz(data_get($data, 'status'));

    if (in_array($status, $disabled_events)) {
        return new WP_REST_Response([
            'status'   => 401,
            'response' => __('Evento Desativado para Integração', 'pixel-x-app'),
        ]);
    }

    $product = data_get($data, 'items.0');
    $value   = data_get($data, 'price.value');

    // Recorrência
    //     $recurrence_number = data_get($data, 'installments');
    //     $predicted_ltv     = PXA_get_setting('predicted_ltv');
    //     if ($recurrence_number > 1 && data_get($data, 'status') == 'paid') {
    //         $status           = EventEnum::SUBSCRIBE;
    //         $recurrence_value = $value * $recurrence_number;
    //
    //         if ($recurrence_value > $predicted_ltv) {
    //             $predicted_ltv = $recurrence_value;
    //         }
    //     }

    $process = PXA_webhook_process([
        'event' => [
            'name'          => $status,
            'source'        => 'Webhook - ' . $type,
            'date'          => data_get($data, 'createdAt'),
            'product_id'    => data_get($product, 'productId'),
            'product_name'  => data_get($product, 'name'),
            'value'         => money_format($value),
            'currency'      => data_get($data, 'price.currency'),
            'predicted_ltv' => $predicted_ltv,
            // 'offer_code'   => data_get($data, 'offer.hash'),
            // 'offer_name'   => data_get($data, 'offer.title'),
            // 'url'      => data_get($data, 'thank_you_page'),
        ],
        'lead' => [
            // 'id'      => data_get($data, 'meta.external_id'),
            'name'  => data_get($data, 'buyer.name'),
            'email' => data_get($data, 'buyer.email'),
            'phone' => data_get($data, 'buyer.cellphone', data_get($data, 'buyer.phone')),
            // 'doc'     => data_get($data, 'buyer.cpf') ?? data_get($data, 'customer.cnpj'),
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
        'tracking' => [
            'utm_source'   => data_get($data, 'utm.source'),
            'utm_medium'   => data_get($data, 'utm.medium'),
            'utm_campaign' => data_get($data, 'utm.campaign'),
            'utm_content'  => data_get($data, 'utm.content'),
            'utm_term'     => data_get($data, 'utm.term'),
            'utm_id'       => data_get($data, 'utm.id'),
            'src'          => data_get($data, 'utm.src'),
            'sck'          => data_get($data, 'utm.sck'),
            'fbc'          => data_get($data, 'utm.fbc'),
            'fbp'          => data_get($data, 'utm.fbp'),
        ],
    ]);

    return new WP_REST_Response([
        'status'   => 200,
        'response' => __('Requisição Processada', 'pixel-x-app'),
        'data'     => $process,
    ]);
}
