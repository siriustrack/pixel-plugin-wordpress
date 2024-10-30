<?php

header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

/*
 * Link Track
 */
$domain               = get_domain();
$page_url             = get_full_url();
$disabled_user_logged = boolval(PXA_get_setting('disabled_user_logged', false));
$parameters           = $_GET;

/*
 * Link Track
 */
$model_id             = get_the_ID();
$model                = PXA_get_model($model_id);
$link_target          = data_get($model, PXA_key('target'));
$target_is_wordpress  = boolval(data_get($model, PXA_key('target_is_wordpress')));
$link_parameter_lead  = boolval(data_get($model, PXA_key('parameter_passing_lead')));
$link_parameter_first = boolval(data_get($model, PXA_key('parameter_passing_first_access')));
$link_event           = data_get($model, PXA_key('event'));
$link_event_custom    = data_get($model, PXA_key('event_custom'));
$link_content_name    = data_get($model, PXA_key('content_name'));
$link_predicted_ltv   = data_get($model, PXA_key('predicted_ltv'));
$link_product_name    = data_get($model, PXA_key('product_name'));
$link_product_id      = data_get($model, PXA_key('product_id'));
$link_offer_ids       = data_get($model, PXA_key('offer_ids'));
$link_product_value   = data_get($model, PXA_key('product_value'));
$link_currency        = data_get($model, PXA_key('currency'));

// Verificação
if (
    ! PXA_license_status()
    || ! str_contains($page_url, $domain)
    || PXA_is_bot()
    || ($disabled_user_logged && user_logged())
) {
    wp_redirect(
        $link_target,
        301,
        'Pixel X App'
    );
}

// Stats
$link_stats_access = (int) data_get($model, PXA_key('stats_access'));
$link_last_lead    = data_get($model, PXA_key('last_lead'));
$link_last_access  = data_get($model, PXA_key('last_access'));

if ($link_event == EventEnum::CUSTOM && filled($link_event_custom)) {
    $link_event = $link_event_custom;
}

/*
 * Lead
 */
$lead_ip  = data_get($_COOKIE, 'pxa_lead_ip') ?? md_get_client_IP();
$lead_fbc = data_get($_COOKIE, '_fbc', data_get($parameters, 'fbclid'));
$lead_fbp = data_get($_COOKIE, '_fbp', data_get($parameters, 'fbp'));

/*
 * Event
 */
$event_time          = current_time('timestamp', true);
$event_date_day      = wp_date('l', $event_time);
$event_day_in_month  = wp_date('j', $event_time);
$event_month         = wp_date('F', $event_time);
$event_time_interval = wp_date('G', $event_time) . '-' . (wp_date('G', $event_time) + 1);

$event = PXA_event_register([
    // Event
    'event_name'          => $link_event,
    'event_time'          => $event_time,
    'event_day'           => $event_date_day,
    'event_day_in_month'  => $event_day_in_month,
    'event_month'         => $event_month,
    'event_time_interval' => $event_time_interval,
    'event_url'           => $page_url,

    // Content
    'content_name' => $link_content_name,
    'page_id'      => data_get($model, 'ID'),
    'page_title'   => data_get($model, 'post_title'),

    // Product
    'product_id'    => $link_product_id,
    'product_name'  => $link_product_name,
    'content_ids'   => $link_offer_ids,
    'value'         => ($link_product_value) ? money_format($link_product_value) : null,
    'predicted_ltv' => $link_predicted_ltv,
    'currency'      => $link_currency,

    // Lead
    'lead_id'     => data_get($_COOKIE, 'pxa_lead_id'),
    'lead_name'   => data_get($_COOKIE, 'pxa_lead_name', data_get($parameters, 'lead_name', data_get($parameters, 'name'))),
    'lead_email'  => data_get($_COOKIE, 'pxa_lead_email', data_get($parameters, 'lead_email', data_get($parameters, 'email'))),
    'lead_phone'  => data_get($_COOKIE, 'pxa_lead_phone', data_get($parameters, 'lead_phone', data_get($parameters, 'phone', data_get($parameters, 'phonenumber')))),
    'lead_doc'    => data_get($_COOKIE, 'pxa_lead_doc', data_get($parameters, 'lead_doc', data_get($parameters, 'doc'))),
    'lead_ip'     => $lead_ip,
    'lead_device' => data_get($_SERVER, 'HTTP_USER_AGENT'),

    // Tracking
    'traffic_source' => data_get($_SERVER, 'HTTP_REFERER'),
    'utm_source'     => data_get($parameters, 'utm_source'),
    'utm_medium'     => data_get($parameters, 'utm_medium'),
    'utm_campaign'   => data_get($parameters, 'utm_campaign'),
    'utm_id'         => data_get($parameters, 'utm_id'),
    'utm_content'    => data_get($parameters, 'utm_content'),
    'utm_term'       => data_get($parameters, 'utm_term'),
    'src'            => data_get($parameters, 'src'),
    'sck'            => data_get($parameters, 'sck'),
    'fb_fbc'         => $lead_fbc,
    'fb_fbp'         => $lead_fbp,
]);

$event      = data_get($event, 'data.data');
$event_id   = data_get($event, 'event_id');
$lead_id    = data_get($event, 'lead_id');
$lead       = PXA_get_model($lead_id, 'lead');
$lead_uid   = data_get($lead, 'pxa_id');
$lead_name  = data_get($lead, 'pxa_name');
$lead_phone = number_raw(data_get($lead, 'pxa_phone')) ?: null;
$lead_email = data_get($lead, 'pxa_email');
$lead_doc   = data_get($lead, 'pxa_document');

// Lead Parameters
if ($link_parameter_lead) {
    // Hotmart, BlitzPay
    if (str_contains($link_target, 'hotmart.com') || str_contains($link_target, 'pay.blitzpay.com.br')) {
        $parameters['name']        = $lead_name;
        $parameters['phonenumber'] = $lead_phone;
        $parameters['email']       = $lead_email;
        $parameters['doc']         = $lead_doc;
        $parameters['zip']         = data_get($lead, 'pxa_adress_zipcode');
    }
    // Eduzz
    elseif (str_contains($link_target, 'eduzz.com')) {
        $parameters['name']  = $lead_name;
        $parameters['phone'] = $lead_phone;
        $parameters['email'] = $lead_email;
        $parameters['doc']   = $lead_doc;
        $parameters['cep']   = data_get($lead, 'pxa_adress_zipcode');
        $parameters['num']   = data_get($lead, 'pxa_adress_street_number');
        $parameters['comp']  = data_get($lead, 'pxa_adress_complement');
    }
    // Ticto
    elseif (str_contains($link_target, 'ticto.app')) {
        $parameters['name']        = $lead_name;
        $parameters['phonenumber'] = $lead_phone;
        $parameters['email']       = $lead_email;
        $parameters['doc']         = $lead_doc;
    }
    // Braip
    elseif (str_contains($link_target, 'braip.com')) {
        $parameters['nome']    = $lead_name;
        $parameters['celular'] = $lead_phone;
        $parameters['email']   = $lead_email;
        $parameters['doc']     = $lead_doc;
    }
    // Greenn
    elseif (str_contains($link_target, 'greenn.com.br')) {
        $parameters['fn']  = $lead_name;
        $parameters['ph']  = $lead_phone;
        $parameters['em']  = $lead_email;
        $parameters['doc'] = $lead_doc;
    }
    // Herospark
    elseif (str_contains($link_target, 'herospark.com')) {
        $parameters['name']  = $lead_name;
        $parameters['tel']   = $lead_phone;
        $parameters['email'] = $lead_email;
        $parameters['doc']   = $lead_doc;
    }
    // PayT
    elseif (str_contains($link_target, 'payt.com.br')) {
        $parameters['full_name'] = $lead_name;
        $parameters['email']     = $lead_email;
        $parameters['phone']     = phone_nacional($lead_phone);
        ;
    }
    // LastLink
    elseif (str_contains($link_target, 'lastlink.com')) {
        $parameters['name']     = $lead_name;
        $parameters['phone']    = $lead_phone;
        $parameters['email']    = $lead_email;
        $parameters['document'] = $lead_doc;
        $parameters['cep']      = data_get($lead, 'pxa_adress_zipcode');
        $parameters['number']   = data_get($lead, 'pxa_adress_street_number');
    }
    // Kirvano
    elseif (str_contains($link_target, 'kirvano.com')) {
        $parameters['customer.name']     = $lead_name;
        $parameters['customer.phone']    = $lead_phone;
        $parameters['customer.email']    = $lead_email;
        $parameters['customer.document'] = $lead_doc;
    }
    // Monetizze
    elseif (str_contains($link_target, 'monetizze.com.br')) {
        $parameters['nome']         = $lead_name;
        $parameters['email']        = $lead_email;
        $parameters['cnpj_cpf']     = $lead_doc;
        $parameters['cep']          = data_get($lead, 'pxa_adress_zipcode');
        $parameters['numero']       = data_get($lead, 'pxa_adress_street_number');
        $parameters['complemento']  = data_get($lead, 'pxa_adress_complement');
        $parameters['city']         = data_get($lead, 'pxa_adress_city');
        $parameters['estado']       = data_get($lead, 'pxa_adress_state');
        $parameters['neighborhood'] = data_get($lead, 'pxa_adress_neighborhood');
        $parameters['telefone']     = phone_nacional($lead_phone);
    }
    // Cakto
    elseif (str_contains($link_target, 'cakto.com.br')) {
        $parameters['name']  = $lead_name;
        $parameters['phone'] = phone_nacional($lead_phone);
        $parameters['email'] = $lead_email;
    }
    // Se no mesmo domínio ou em site WordPress
    elseif (str_contains($link_target, $domain) || $target_is_wordpress) {
        $parameters['lead_name']  = $lead_name;
        $parameters['lead_email'] = $lead_email;
        $parameters['lead_doc']   = $lead_doc;
    }
    /*
     * Default
     * DMG
     * Kiwify (kiwify.com.br)
     * Celetus (celetus.com)
     * Hubla (hub.la)
     * PerfectPay (perfectpay.com.br | siteseguro.net)
     */
    else {
        $parameters['name']  = $lead_name;
        $parameters['phone'] = $lead_phone;
        $parameters['email'] = $lead_email;
    }
}

// Facebook e UTMs do Lead
// Repassar Parâmetros Capturados no Primeiro Acesso
if ($link_parameter_first) {
    // Facebook e UTMs do Lead
    $fields = [
        'fbclid'       => PXA_key('first_fbc'),
        'fbp'          => PXA_key('fbp'),
        'utm_source'   => PXA_key('first_utm_source'),
        'utm_medium'   => PXA_key('first_utm_medium'),
        'utm_campaign' => PXA_key('first_utm_campaign'),
        'utm_id'       => PXA_key('first_utm_id'),
        'utm_content'  => PXA_key('first_utm_content'),
        'utm_term'     => PXA_key('first_utm_term'),
        'src'          => PXA_key('first_src'),
        'sck'          => PXA_key('first_sck') ,
        'sck'          => PXA_key('id'),
    ];

    foreach ($fields as $paramKey => $leadKey) {
        if (blank(data_get($parameters, $paramKey)) && filled(data_get($lead, $leadKey))) {
            $parameters[$paramKey] = data_get($lead, $leadKey);
        }
    }
} else {
    // Repassar Parâmetros Capturados no Último Acesso e Acesso Atual
    $fields = [
        'fbclid'       => PXA_key('fbc'),
        'fbp'          => PXA_key('fbp'),
        'utm_source'   => PXA_key('utm_source'),
        'utm_medium'   => PXA_key('utm_medium'),
        'utm_campaign' => PXA_key('utm_campaign'),
        'utm_id'       => PXA_key('utm_id'),
        'utm_content'  => PXA_key('utm_content'),
        'utm_term'     => PXA_key('utm_term'),
        'src'          => PXA_key('src'),
        'sck'          => PXA_key('sck'),
        'sck'          => PXA_key('id'),
    ];

    foreach ($fields as $paramKey => $leadKey) {
        if (blank(data_get($parameters, $paramKey)) && filled(data_get($lead, $leadKey))) {
            $parameters[$paramKey] = data_get($lead, $leadKey);
        }
    }
}

// Facebook e UTMs do Link Rastreado
$fields = [
    'utm_source'   => PXA_key('utm_source'),
    'utm_medium'   => PXA_key('utm_medium'),
    'utm_campaign' => PXA_key('utm_campaign'),
    'utm_id'       => PXA_key('utm_id'),
    'utm_content'  => PXA_key('utm_content'),
    'utm_term'     => PXA_key('utm_term'),
    'src'          => PXA_key('src'),
];

foreach ($fields as $param => $key) {
    if (blank(data_get($parameters, $param)) && filled(data_get($model, $key))) {
        $value              = data_get($model, $key);
        $parameters[$param] = $value;

        carbon_set_post_meta($lead_id, $key, $value);
        carbon_set_post_meta($event_id, $key, $value);
    }
}

// URL Define
$parameters = http_build_query(removeNullValues($parameters));

if (str_contains($link_target, '?')) {
    $link_target = $link_target . '&' . $parameters;
} else {
    $link_target = $link_target . '?' . $parameters;
}

// Update Link Track
carbon_set_post_meta($model_id, PXA_key('stats_access'), $link_stats_access + 1);
carbon_set_post_meta($model_id, PXA_key('last_lead'), $lead_uid);
carbon_set_post_meta($model_id, PXA_key('last_access'), wp_date('Y-m-d H:i:s', $event_time));

// Cookies
$cookie_time = time() + 15552000;
$cookies     = [
    'pxa_lead_id'           => $lead_uid,
    'pxa_lead_name'         => $lead_name,
    'pxa_lead_fname'        => str_before($lead_name, ' '),
    'pxa_lead_lname'        => str_after($lead_name, ' '),
    'pxa_lead_email'        => $lead_email,
    'pxa_lead_phone'        => $lead_phone,
    'pxa_lead_ip'           => $lead_ip,
    'pxa_lead_city'         => data_get($lead, PXA_key('adress_city')),
    'pxa_lead_region'       => data_get($lead, PXA_key('adress_state')),
    'pxa_lead_country'      => data_get($lead, PXA_key('adress_country_name')),
    'pxa_lead_country_code' => data_get($lead, PXA_key('adress_country')),
    'pxa_lead_zipcode'      => data_get($lead, PXA_key('adress_zipcode')),
    '_fbc'                  => data_get($lead, PXA_key('fbc')),
    '_fbp'                  => data_get($lead, PXA_key('fbp')),
];

foreach ($cookies as $cookie_key => $cookie_value) {
    if (blank(data_get($_COOKIE, $cookie_key)) && filled($cookie_value)) {
        setcookie($cookie_key, $cookie_value, $cookie_time, '/', $domain, true, false);
    }
}

// Redirect
wp_redirect(
    $link_target,
    301,
    'Pixel X App'
);

exit;
