<?php

add_action('rest_api_init', function () {
    register_rest_route(PXA_DOMAIN . '/v1', '/integration', [
        'methods'             => 'POST',
        'callback'            => 'PXA_integration',
        'permission_callback' => '__return_true',
    ]);
});

function PXA_integration($request)
{
    $pid = $request->get_param('pid');

    // Get Integration
    $integration = PXA_get_model($pid, 'integration');

    // Integration Not Found
    if (blank($integration)) {
        return new WP_REST_Response([
            'status'   => 404,
            'response' => __('Integração Não Localizada', 'pixel-x-app'),
        ], 404);
    }

    // Corpo
    $body = $request->get_json_params() ?? $request->get_params();

    if (blank($body)) {
        return new WP_REST_Response([
            'status'   => 400,
            'response' => __('Requisição Sem Dados', 'pixel-x-app'),
        ], 400);
    }

    // Validação de Token
    if ( ! PXA_license_status()) {
        return new WP_REST_Response([
            'status'   => 401,
            'response' => __('Requisição Não Autorizada', 'pixel-x-app'),
        ]);
    }

    $type     = data_get($integration, PXA_key('type'));
    $function = 'PXA_integration_' . $type;

    if (function_exists($function)) {
        return $function($request, $integration);
    }

    return new WP_REST_Response([
        'status'   => 404,
        'response' => __('Tipo Não Identificado', 'pixel-x-app'),
    ], 404);
}

add_action('rest_api_init', function () {
    register_rest_route(PXA_DOMAIN . '/v1', '/blitzpay', [
        'methods'             => 'POST',
        'callback'            => 'PXA_integration_blitzpay',
        'permission_callback' => '__return_true',
    ]);
});
