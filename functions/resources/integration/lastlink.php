<?php

function PXA_integration_lastlink($request, $integration = null)
{
    $data       = $request->get_json_params()              ?? $request->get_params();
    $data_token = $request->get_header('x-lastlink-token') ?? $request->get_header('X-LASTLINK-TOKEN');

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

    $status = EventEnum::lastlink(data_get($data, 'Event'));
    $date   = data_get($data, 'CreatedAt');
    $data   = data_get($data, 'Data');

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
            'date'         => $date,
            'product_id'   => data_get($data, 'Products.0.Id'),
            'product_name' => data_get($data, 'Products.0.Name'),
            'offer_code'   => data_get($data, 'Offer.Id'),
            'offer_name'   => data_get($data, 'Offer.Name'),
            'value'        => number_format(data_get($data, 'Purchase.Price.Value'), 2, '.', ''),
            'url'          => data_get($data, 'Offer.Url'),
            // 'currency' => data_get($data, 'currency'),
        ],
        'lead' => [
            // 'id'      => data_get($data, 'Buyer.Id'),
            'name'    => data_get($data, 'Buyer.Name'),
            'email'   => data_get($data, 'Buyer.Email'),
            'phone'   => data_get($data, 'Buyer.PhoneNumber'),
            'doc'     => data_get($data, 'Buyer.Document'),
            'ip'      => data_get($data, 'DeviceInfo.ip'),
            'device'  => data_get($data, 'DeviceInfo.UserAgent'),
            'address' => [
                'street'        => data_get($data, 'Buyer.Address.Street'),
                'street_number' => data_get($data, 'Buyer.Address.StreetNumber'),
                'complement'    => data_get($data, 'Buyer.Address.Complement'),
                'neighborhood'  => data_get($data, 'Buyer.Address.District'),
                'city'          => data_get($data, 'Buyer.Address.City'),
                'state'         => data_get($data, 'Buyer.Address.State'),
                // 'country'       => data_get($data, 'Buyer.Address.country'),
                // 'country_code'  => data_get($data, 'Buyer.Address.country_code'),
                'zip_code' => data_get($data, 'Buyer.Address.ZipCode'),
            ]
        ],
        'tracking' => [
            'utm_source'   => data_get($data, 'Utm.UtmSource'),
            'utm_medium'   => data_get($data, 'Utm.UtmMedium'),
            'utm_campaign' => data_get($data, 'Utm.UtmCampaign'),
            'utm_content'  => data_get($data, 'Utm.UtmContent'),
            'utm_term'     => data_get($data, 'Utm.UtmTerm'),
            // 'src'          => data_get($data, 'src'),
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
