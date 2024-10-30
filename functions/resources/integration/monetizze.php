<?php

function PXA_integration_monetizze($request, $integration = null)
{
    $data            = $request->get_json_params() ?? $request->get_params();
    $token           = data_get($integration, PXA_key('token'));
    $disabled_events = PXA_get_post_meta($integration['ID'], 'disabled_events', []);
    $undefined       = boolval(data_get($integration, PXA_key('undefined'), false));
    $type            = IntegrationTypeEnum::from(data_get($integration, PXA_key('type')))->label();

    if ( ! PXA_license_status() || data_get($data, 'chave_unica') != $token) {
        return new WP_REST_Response([
            'status'   => 401,
            'response' => __('Requisição Não Autorizada', 'pixel-x-app'),
        ]);
    }

    $status = EventEnum::monetizze(data_get($data, 'tipoEvento.codigo'));

    if (in_array($status, $disabled_events) || ($undefined && $status == EventEnum::LEAD)) {
        return new WP_REST_Response([
            'status'   => 401,
            'response' => __('Evento Desativado para Integração', 'pixel-x-app'),
        ]);
    }

    $process = PXA_webhook_process([
        'event' => [
            'name'         => $status,
            'date'         => data_get($data, 'venda.dataFinalizada', data_get($data, 'data')),
            'source'       => 'Webhook - ' . $type,
            'product_id'   => data_get($data, 'produto.codigo'),
            'product_name' => data_get($data, 'produto.nome'),
            'offer_code'   => data_get($data, 'plano.codigo'),
            'offer_name'   => data_get($data, 'plano.nome'),
            'value'        => number_format(data_get($data, 'venda.valor'), 2, '.', ''),
            'url'          => data_get($data, 'url_recuperacao'),
        ],
        'lead' => [
            // 'id'      => data_get($data, 'meta.external_id'),
            'name'    => data_get($data, 'comprador.nome'),
            'email'   => data_get($data, 'comprador.email'),
            'phone'   => data_get($data, 'comprador.telefone'),
            'doc'     => data_get($data, 'comprador.cnpj_cpf'),
            'address' => [
                'street'        => data_get($data, 'comprador.endereco'),
                'street_number' => data_get($data, 'comprador.numero'),
                'complement'    => data_get($data, 'comprador.complemento'),
                'city'          => data_get($data, 'comprador.cidade'),
                'state'         => data_get($data, 'comprador.estado'),
                'country'       => data_get($data, 'comprador.pais'),
                'zip_code'      => data_get($data, 'comprador.cep'),
                'neighborhood'  => data_get($data, 'comprador.bairro'),
            ]
        ],
        'tracking' => [
            'utm_source'   => data_get($data, 'venda.utm_source'),
            'utm_medium'   => data_get($data, 'venda.utm_medium'),
            'utm_campaign' => data_get($data, 'venda.utm_campaign'),
            'utm_content'  => data_get($data, 'venda.utm_content'),
            'src'          => data_get($data, 'venda.src'),
            // 'utm_id'       => data_get($data, 'venda.utm_id'),
            // 'utm_term'     => data_get($data, 'venda.utm_term'),
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
