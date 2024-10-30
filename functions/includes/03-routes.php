<?php

add_action('rest_api_init', function () {
    register_rest_route(PXA_DOMAIN . '/v1', '/lead', [
        'methods'             => 'POST',
        'callback'            => 'PXA_lead_upsert',
        'permission_callback' => '__return_true',
    ]);
});

function PXA_lead_upsert($request)
{
    if ( ! PXA_license_status()) {
        return new WP_REST_Response([
            'status'   => 401,
            'response' => __('Requisição Não Autorizada', 'pixel-x-app'),
        ], 401);
    }

    // $data = $request->get_params();
    $data = (is_object($request)) ? $request->get_json_params() : $request;

    /*
     * Lead
     */
    $lead       = null;
    $lead_uid   = data_get($data, 'lead_id');
    $lead_email = data_get($data, 'lead_email');
    $lead_phone = number_raw(data_get($data, 'lead_phone'));
    $lead_ip    = data_get($data, 'ip');

    if (filled(data_get($data, 'lead_name'))) {
        $lead_name = data_get($data, 'lead_name');
    } elseif (filled(data_get($data, 'lead_fname')) || filled(data_get($data, 'lead_lname'))) {
        $lead_name = data_get($data, 'lead_fname') . ' ' . data_get($data, 'lead_lname');
    }

    /*
     * Get Lead
     */
    if ($lead_uid) {
        $lead = PXA_get_model($lead_uid, 'lead');
    }

    if (is_null($lead) && $lead_email) {
        $lead = PXA_get_model($lead_email, 'lead');
    }

    if (is_null($lead) && $lead_phone) {
        $lead = PXA_get_model($lead_phone, 'lead');
    }

    if (is_null($lead) && $lead_ip) {
        $lead = PXA_get_model($lead_ip, 'lead');
    }

    /*
     * Create Lead
     */
    if (is_null($lead)) {
        $lead_uid = $lead_uid ?: wp_generate_uuid4();

        $lead_id = wp_insert_post([
            'post_title'  => $lead_uid,
            'post_status' => 'publish',
            'post_type'   => PXA_key('lead'),
        ]);
        $lead = PXA_get_model($lead_id);

        // Set Lead UID
        carbon_set_post_meta($lead_id, PXA_key('id'), $lead_uid);
    } else {
        $lead_id  = data_get($lead, 'ID');
        $lead_uid = carbon_get_post_meta($lead_id, PXA_key('id'));
    }

    // Set Lead Name
    if (isset($lead_name)) {
        carbon_set_post_meta($lead_id, PXA_key('name'), $lead_name);
    }
    $lead_name = carbon_get_post_meta($lead_id, PXA_key('name'));

    // Set Lead Email
    if ($lead_email) {
        carbon_set_post_meta($lead_id, PXA_key('email'), $lead_email);
    }
    $lead_email = carbon_get_post_meta($lead_id, PXA_key('email'));

    // Set Lead Phone
    if ($lead_phone) {
        carbon_set_post_meta($lead_id, PXA_key('phone'), $lead_phone);
    }
    $lead_phone = carbon_get_post_meta($lead_id, PXA_key('phone'));

    /**
     * Geolocation
     */
    $geo_keys = [
        'ip',
        'device',
        'adress_city',
        'adress_state',
        'adress_zipcode',
        'adress_country_name',
        'adress_country',
    ];

    foreach ($geo_keys as $key) {
        if (filled(data_get($data, $key))) {
            carbon_set_post_meta($lead_id, PXA_key($key), data_get($data, $key));
        }

        $$key = carbon_get_post_meta($lead_id, PXA_key($key));
    }

    $track_keys = [
        'fbc',
        'fbp',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_id',
        'utm_term',
        'utm_content',
        'src',
        'sck',
    ];

    foreach ($track_keys as $key) {
        if (filled(data_get($data, $key))) {
            $value = data_get($data, $key);

            // First Parameters
            $first_key     = 'first_' . $key;
            $pxa_first_key = PXA_key($first_key);
            if (blank(data_get($lead, $pxa_first_key))) {
                carbon_set_post_meta($lead_id, $pxa_first_key, $value);
            }

            // Last Parameters
            carbon_set_post_meta($lead_id, PXA_key($key), $value);
        }

        $$key       = carbon_get_post_meta($lead_id, PXA_key($key));
        $$first_key = carbon_get_post_meta($lead_id, $pxa_first_key);
    }

    return new WP_REST_Response([
        'status'   => 200,
        'response' => __('Requisição Recebida!', 'pixel-x-app'),
        'data'     => [
            'id'    => $lead_uid,
            'name'  => $lead_name ?: null,
            'fname' => (str_contains($lead_name, ' ')) ? str_before($lead_name, ' ') : $lead_name,
            'lname' => (str_contains($lead_name, ' ')) ? str_after($lead_name, ' ') : null,
            'email' => $lead_email ?: null,
            'phone' => $lead_phone ?: null,
            // Geo
            'ip'                  => $ip ?: null,
            'device'              => $device ?: null,
            'adress_city'         => $adress_city ?: null,
            'adress_state'        => $adress_state ?: null,
            'adress_zipcode'      => $adress_zipcode ?: null,
            'adress_country_name' => $adress_country_name ?: null,
            'adress_country'      => $adress_country ?: null,
            // Tracking
            'fbc'          => $fbc ?: null,
            'first_fbc'    => $first_fbc ?: null,
            'fbp'          => $fbp ?: null,
            'utm_source'   => $utm_source ?: null,
            'utm_medium'   => $utm_medium ?: null,
            'utm_campaign' => $utm_campaign ?: null,
            'utm_id'       => $utm_id ?: null,
            'utm_term'     => $utm_term ?: null,
            'utm_content'  => $utm_content ?: null,
            'src'          => $src ?: null,
            'sck'          => $sck ?: null,
        ]
    ]);
}

add_action('rest_api_init', function () {
    register_rest_route(PXA_DOMAIN . '/v1', '/event', [
        'methods'             => 'POST',
        'callback'            => 'PXA_event_register',
        'permission_callback' => '__return_true',
    ]);
});

function PXA_event_register($request)
{
    // $data = $request->get_params();
    $data                 = (is_object($request)) ? $request->get_json_params() : $request;
    $send_event_immediate = boolval(PXA_get_setting('send_event_immediate')) ?: false;

    if ( ! PXA_license_status()) {
        return new WP_REST_Response([
            'status'   => 401,
            'response' => __('Requisição Não Autorizada', 'pixel-x-app'),
        ]);
    }

    $event_url = data_get($data, 'event_url', data_get($data, 'page_url'));

    if ( ! str_contains($event_url, get_domain())) {
        return new WP_REST_Response([
            'status'   => 401,
            'response' => __('Domínio Não Autorizado', 'pixel-x-app'),
        ]);
    }

    /*
     * Lead
     */
    $lead         = null;
    $lead_uid     = data_get($data, 'lead_id', data_get($data, 'external_id'));
    $lead_email   = data_get($data, 'lead.email', data_get($data, 'lead_email'));
    $lead_phone   = number_raw(data_get($data, 'lead.phone', data_get($data, 'lead_phone')));
    $lead_doc     = data_get($data, 'lead.doc', data_get($data, 'lead_doc'));
    $lead_ip      = data_get($data, 'lead.ip', data_get($data, 'lead_ip'));
    $tracking_fbp = data_get($data, 'tracking.fbp', data_get($data, 'fb_fbp'));
    $tracking_fbc = data_get($data, 'tracking.fbc', data_get($data, 'fb_fbc'));
    $tracking_sck = data_get($data, 'tracking.sck', data_get($data, 'sck'));

    if (filled(data_get($data, 'lead.name', data_get($data, 'lead_name')))) {
        $lead_name = data_get($data, 'lead.name', data_get($data, 'lead_name'));
    } elseif (filled(data_get($data, 'lead_fname')) || filled(data_get($data, 'lead_lname'))) {
        $lead_name = data_get($data, 'lead_fname') . ' ' . data_get($data, 'lead_lname');
    }

    /*
     * Get Lead
     */
    if ($lead_uid) {
        $lead = PXA_get_model($lead_uid, 'lead');
    }

    if (is_null($lead) && $lead_email) {
        $lead = PXA_get_model($lead_email, 'lead');
    }

    if (is_null($lead) && $lead_phone) {
        $lead = PXA_get_model($lead_phone, 'lead');
    }

    if (is_null($lead) && $lead_doc) {
        $lead = PXA_get_model($lead_doc, 'lead');
    }

    if (is_null($lead) && $lead_ip) {
        $lead = PXA_get_model($lead_ip, 'lead');
    }

    if (is_null($lead) && $tracking_fbp) {
        $lead = PXA_get_model($tracking_fbp, 'lead');
    }

    if (is_null($lead) && $tracking_fbc) {
        $lead = PXA_get_model($tracking_fbc, 'lead');
    }

    if (is_null($lead) && $tracking_sck) {
        $lead = PXA_get_model($tracking_sck, 'lead');
    }

    /*
     * Create Lead
     */
    if (is_null($lead)) {
        $lead_uid = $lead_uid ?: wp_generate_uuid4();

        $lead_id = wp_insert_post([
            'post_title'  => $lead_uid,
            'post_status' => 'publish',
            'post_type'   => PXA_key('lead'),
        ]);
        $lead = PXA_get_model($lead_id);

        // Set Lead UID
        carbon_set_post_meta($lead_id, PXA_key('id'), $lead_uid);
    } else {
        $lead_id  = data_get($lead, 'ID');
        $lead_uid = data_get($lead, PXA_key('id'));
        $lead_uid = data_get($lead, 'post_title');
    }

    // Set Lead Name
    if (isset($lead_name)) {
        carbon_set_post_meta($lead_id, PXA_key('name'), $lead_name);
    }

    // Set Lead Email
    if ($lead_email) {
        carbon_set_post_meta($lead_id, PXA_key('email'), $lead_email);
    }

    // Set Lead Phone
    if ($lead_phone) {
        carbon_set_post_meta($lead_id, PXA_key('phone'), $lead_phone);
    }

    /*
     * Event
     */
    $event         = null;
    $event_uid     = data_get($data, 'event_id');
    $event_time    = data_get($data, 'event_time');
    $post_date_gmt = wp_date_utc($event_time);
    $post_date     = wp_date('Y-m-d H:i:s', $event_time);

    if ($event_uid) {
        $event = PXA_get_model($event_uid, 'event');
    } else {
        $event_uid = wp_generate_uuid4();
    }

    if (blank($event)) {
        // Insert the post into the database
        $event_id = wp_insert_post([
            'post_title'    => $event_uid,
            'post_status'   => 'pending',
            'post_type'     => PXA_key('event'),
            'post_date'     => $post_date,
            'post_date_gmt' => $post_date_gmt,
        ]);
        $event = PXA_get_model($event_id);

        // Set Event ID
        carbon_set_post_meta($event_id, PXA_key('id'), $event_uid);

        // Set Lead
        carbon_set_post_meta($event_id, PXA_key('lead_id'), data_get($lead, 'ID'));
    } else {
        $event_id = data_get($event, 'ID');

        wp_update_post([
            'ID'            => $event_id,
            'post_date'     => $post_date,
            'post_date_gmt' => $post_date_gmt,
        ]);
    }

    // Set Fields
    carbon_set_post_meta($event_id, PXA_key('lead_id'), $lead_id);

    $ip                  = data_get($data, 'geolocation.pxa_lead_ip') ?: data_get($data, 'lead_ip') ?: data_get($lead, PXA_key('ip'));
    $device              = data_get($data, 'user_agent') ?: data_get($data, 'lead_device') ?: data_get($lead, PXA_key('device'));
    $adress_country_name = data_get($data, 'geolocation.pxa_lead_country') ?: data_get($lead, PXA_key('adress_country_name'));
    $adress_country      = data_get($data, 'geolocation.pxa_lead_country_code') ?: data_get($lead, PXA_key('adress_country'));
    $adress_state        = data_get($data, 'geolocation.pxa_lead_region') ?: data_get($lead, PXA_key('adress_state'));
    $adress_city         = data_get($data, 'geolocation.pxa_lead_city') ?: data_get($lead, PXA_key('adress_city'));
    $adress_zipcode      = data_get($data, 'geolocation.pxa_lead_zipcode') ?: data_get($lead, PXA_key('adress_zipcode'));
    $adress_currency     = data_get($data, 'geolocation.pxa_lead_currency') ?: data_get($data, 'currency');

    // Geolocation Lead
    carbon_set_post_meta($lead_id, PXA_key('ip'), $ip);
    carbon_set_post_meta($lead_id, PXA_key('device'), $device);
    carbon_set_post_meta($lead_id, PXA_key('adress_country_name'), $adress_country_name);
    carbon_set_post_meta($lead_id, PXA_key('adress_country'), $adress_country);
    carbon_set_post_meta($lead_id, PXA_key('adress_state'), $adress_state);
    carbon_set_post_meta($lead_id, PXA_key('adress_city'), $adress_city);
    carbon_set_post_meta($lead_id, PXA_key('adress_currency'), $adress_currency);
    carbon_set_post_meta($lead_id, PXA_key('adress_zipcode'), $adress_zipcode);

    // Geolocation Event
    carbon_set_post_meta($event_id, PXA_key('geo_ip'), $ip);
    carbon_set_post_meta($event_id, PXA_key('geo_device'), $device);
    carbon_set_post_meta($event_id, PXA_key('geo_country_name'), $adress_country_name);
    carbon_set_post_meta($event_id, PXA_key('geo_country'), $adress_country);
    carbon_set_post_meta($event_id, PXA_key('geo_state'), $adress_state);
    carbon_set_post_meta($event_id, PXA_key('geo_city'), $adress_city);
    carbon_set_post_meta($event_id, PXA_key('geo_currency'), $adress_currency);
    carbon_set_post_meta($event_id, PXA_key('geo_zipcode'), $adress_zipcode);

    // Content
    carbon_set_post_meta($event_id, PXA_key('page_id'), data_get($data, 'page_id'));
    carbon_set_post_meta($event_id, PXA_key('page_title'), data_get($data, 'page_title'));
    carbon_set_post_meta($event_id, PXA_key('product_id'), data_get($data, 'product_id'));
    carbon_set_post_meta($event_id, PXA_key('product_name'), data_get($data, 'product_name'));
    carbon_set_post_meta($event_id, PXA_key('product_value'), data_get($data, 'value'));
    carbon_set_post_meta($event_id, PXA_key('predicted_ltv'), data_get($data, 'predicted_ltv'));
    carbon_set_post_meta($event_id, PXA_key('offer_ids'), data_get($data, 'content_ids'));
    carbon_set_post_meta($event_id, PXA_key('content_name'), data_get($data, 'content_name'));

    // Event
    carbon_set_post_meta($event_id, PXA_key('event_name'), data_get($data, 'event_name'));
    carbon_set_post_meta($event_id, PXA_key('event_day'), data_get($data, 'event_day'));
    carbon_set_post_meta($event_id, PXA_key('event_day_in_month'), data_get($data, 'event_day_in_month'));
    carbon_set_post_meta($event_id, PXA_key('event_month'), data_get($data, 'event_month'));
    carbon_set_post_meta($event_id, PXA_key('event_time'), $event_time);
    carbon_set_post_meta($event_id, PXA_key('event_time_interval'), data_get($data, 'event_time_interval'));
    carbon_set_post_meta($event_id, PXA_key('event_url'), $event_url);

    // Parameters
    $utm_source   = data_get($data, 'utm_source') ?: data_get($lead, PXA_key('utm_source'));
    $utm_medium   = data_get($data, 'utm_medium') ?: data_get($lead, PXA_key('utm_medium'));
    $utm_campaign = data_get($data, 'utm_campaign') ?: data_get($lead, PXA_key('utm_campaign'));
    $utm_id       = data_get($data, 'utm_id') ?: data_get($lead, PXA_key('utm_id'));
    $utm_term     = data_get($data, 'utm_term') ?: data_get($lead, PXA_key('utm_term'));
    $utm_content  = data_get($data, 'utm_content') ?: data_get($lead, PXA_key('utm_content'));
    $src          = data_get($data, 'src');
    $sck          = data_get($data, 'sck');
    $fbc          = data_get($data, 'fb_fbc', data_get($data, 'fbc'));
    $fbp          = data_get($data, 'fb_fbp', data_get($data, 'fbp'));

    carbon_set_post_meta($event_id, PXA_key('traffic_source'), data_get($data, 'traffic_source'));
    carbon_set_post_meta($event_id, PXA_key('utm_source'), $utm_source);
    carbon_set_post_meta($event_id, PXA_key('utm_medium'), $utm_medium);
    carbon_set_post_meta($event_id, PXA_key('utm_campaign'), $utm_campaign);
    carbon_set_post_meta($event_id, PXA_key('utm_id'), $utm_id);
    carbon_set_post_meta($event_id, PXA_key('utm_term'), $utm_term);
    carbon_set_post_meta($event_id, PXA_key('utm_content'), $utm_content);
    carbon_set_post_meta($event_id, PXA_key('src'), $src);
    carbon_set_post_meta($event_id, PXA_key('sck'), $sck);

    // Parameters in Lead
    $parameters = [
        'utm_source'   => $utm_source,
        'utm_medium'   => $utm_medium,
        'utm_campaign' => $utm_campaign,
        'utm_id'       => $utm_id,
        'utm_term'     => $utm_term,
        'utm_content'  => $utm_content,
        'src'          => $src,
        'sck'          => $sck,
        'fbc'          => $fbc,
        'fbp'          => $fbp,
    ];

    // Atualiza os metadados para cada parâmetro
    foreach ($parameters as $key => $value) {
        if (filled($value)) {
            // First Parameters
            $first_key = PXA_key('first_' . $key);
            if (blank(data_get($lead, $first_key))) {
                carbon_set_post_meta($lead_id, $first_key, $value);
            }

            // Last Parameters
            carbon_set_post_meta($lead_id, PXA_key($key), $value);
        }
    }

    // Facebook
    carbon_set_post_meta($event_id, PXA_key('fbc'), $fbc);
    carbon_set_post_meta($event_id, PXA_key('fbp'), $fbp);

    // Pixel
    update_post_meta($event_id, PXA_key('fb_pixels'), data_get($data, 'fb_pixels'));

    if ($send_event_immediate) {
        PXA_send_event_to_server([$event_id]);
    }

    return new WP_REST_Response([
        'status'   => 200,
        'response' => __('Requisição Recebida!', 'pixel-x-app'),
        'data'     => [
            'event_uid' => $event_uid,
            'lead_uid'  => $lead_uid,
            'event_id'  => $event_id,
            'lead_id'   => $lead_id,
        ],
    ]);
}
