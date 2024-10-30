<?php

function PXA_webhook_process($data)
{
    if ( ! PXA_license_status()) {
        return;
    }

    /*
     * Global
     */
    $send_event_immediate = boolval(PXA_get_setting('send_event_immediate')) ?? false;
    $currency             = data_get($data, 'event.currency', PXA_get_setting('currency'));

    /*
     * Lead
     */
    $lead         = null;
    $lead_uid     = data_get($data, 'lead.id');
    $lead_name    = data_get($data, 'lead.name');
    $lead_email   = data_get($data, 'lead.email');
    $lead_phone   = number_raw(data_get($data, 'lead.phone'));
    $lead_doc     = data_get($data, 'lead.doc');
    $lead_ip      = data_get($data, 'lead.ip');
    $tracking_fbp = data_get($data, 'tracking.fbp');
    $tracking_fbc = data_get($data, 'tracking.fbc');
    $tracking_sck = data_get($data, 'tracking.sck');

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

        // Set Lead ID
        carbon_set_post_meta($lead_id, PXA_key('id'), $lead_uid);
    } else {
        $lead_id  = data_get($lead, 'ID');
        $lead_uid = data_get($lead, PXA_key('id'));
        $lead_uid = data_get($lead, 'post_title');
    }

    // Set Lead Name
    if ($lead_name) {
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

    // Set Lead Phone
    if ($lead_doc) {
        carbon_set_post_meta($lead_id, PXA_key('document'), $lead_doc);
    }

    /*
     * Event
     */
    $event_name = data_get($data, 'event.name');

    /*
     * Get Event
     */
    //     $current_time       = current_time('timestamp');
    //     $twenty_minutes_ago = strtotime('-20 minutes', $current_time);
    //
    //     $event = PXA_get_model($event_name, 'event', [
    //         [
    //             'relation' => 'AND',
    //             [
    //                 'key'     => PXA_key('event_name'),
    //                 'value'   => $event_name,
    //                 'compare' => '='
    //             ],
    //             [
    //                 'key'     => PXA_key('lead_id'),
    //                 'value'   => data_get($lead, 'ID'),
    //                 'compare' => '='
    //             ],
    //             [
    //                 'key'     => PXA_key('event_time'),
    //                 'value'   => $twenty_minutes_ago,
    //                 'compare' => '>='
    //             ],
    //         ]
    //     ]);

    /*
     * Create Event
     */
    $event_time = data_get($data, 'event.date', current_time('timestamp'));
    $post_date  = (is_string($event_time))
        ? date_format(date_create($event_time), 'Y-m-d H:i:s')
        : wp_date('Y-m-d H:i:s', $event_time);
    $post_date_gmt = wp_date_utc($post_date);
    $event_uid     = wp_generate_uuid4();

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
        $event_id  = data_get($event, 'ID');
        $event_uid = data_get($event, PXA_key('id'));

        wp_update_post([
            'ID'            => $event_id,
            'post_date'     => $post_date,
            'post_date_gmt' => $post_date_gmt,
        ]);
    }

    /*
     * Set Fields
     */
    // Geolocation
    $geo_ip           = data_get($data, 'lead.ip')                   ?? PXA_get_post_meta($lead_id, 'ip');
    $geo_device       = data_get($data, 'lead.device')               ?? PXA_get_post_meta($lead_id, 'device');
    $geo_country_code = data_get($data, 'lead.address.country_code') ?? PXA_get_post_meta($lead_id, 'adress_country');
    $geo_country_name = data_get($data, 'lead.address.country')      ?? PXA_get_post_meta($lead_id, 'adress_country_name');
    $geo_city         = data_get($data, 'lead.address.city')         ?? PXA_get_post_meta($lead_id, 'adress_city');
    $geo_state        = data_get($data, 'lead.address.state')        ?? PXA_get_post_meta($lead_id, 'adress_state');
    $geo_zipcode      = data_get($data, 'lead.address.zip_code')     ?? PXA_get_post_meta($lead_id, 'adress_zipcode');

    // Se tem IP e não tem Geo
    if (filled($geo_ip)) {
        $geolocation = pxa_cache_remember(
            'geolocation_' . $geo_ip,
            function () use ($geo_ip) {
                $geolocation = wp_remote_get('https://pro.ip-api.com/json/' . $geo_ip . '?key=TOLoWxdNIA0zIZm');
                $geolocation = wp_remote_retrieve_body($geolocation);

                return json_decode($geolocation, true);
            },
            get_time(10, 'minutes')
        );

        if (blank($geo_country_code)) {
            $geo_country_code = data_get($geolocation, 'countryCode');
        }

        if (blank($geo_country_name)) {
            $geo_country_name = data_get($geolocation, 'country');
        }

        if (blank($geo_city)) {
            $geo_city = data_get($geolocation, 'city');
        }

        if (blank($geo_state)) {
            $geo_state = data_get($geolocation, 'regionName');
        }

        if (blank($geo_zipcode)) {
            $geo_zipcode = data_get($geolocation, 'zip');
        }
    }

    // Event
    carbon_set_post_meta($event_id, PXA_key('geo_ip'), $geo_ip);
    carbon_set_post_meta($event_id, PXA_key('geo_device'), $geo_device);
    carbon_set_post_meta($event_id, PXA_key('geo_country'), $geo_country_code);
    carbon_set_post_meta($event_id, PXA_key('geo_country_name'), $geo_country_name);
    carbon_set_post_meta($event_id, PXA_key('geo_city'), $geo_city);
    carbon_set_post_meta($event_id, PXA_key('geo_state'), $geo_state);
    carbon_set_post_meta($event_id, PXA_key('geo_zipcode'), $geo_zipcode);
    carbon_set_post_meta($event_id, PXA_key('geo_currency'), $currency);

    // Lead
    carbon_set_post_meta($lead_id, PXA_key('ip'), $geo_ip);
    carbon_set_post_meta($lead_id, PXA_key('device'), $geo_device);
    carbon_set_post_meta($lead_id, PXA_key('adress_street'), data_get($data, 'lead.address.street'));
    carbon_set_post_meta($lead_id, PXA_key('adress_street_number'), data_get($data, 'lead.address.street_number'));
    carbon_set_post_meta($lead_id, PXA_key('adress_complement'), data_get($data, 'lead.address.complement'));
    carbon_set_post_meta($lead_id, PXA_key('adress_country'), $geo_country_code);
    carbon_set_post_meta($lead_id, PXA_key('adress_country_name'), $geo_country_name);
    carbon_set_post_meta($lead_id, PXA_key('adress_city'), $geo_city);
    carbon_set_post_meta($lead_id, PXA_key('adress_state'), $geo_state);
    carbon_set_post_meta($lead_id, PXA_key('adress_zipcode'), $geo_zipcode);

    // Content
    carbon_set_post_meta($event_id, PXA_key('page_id'), data_get($data, 'event.page_id'));
    carbon_set_post_meta($event_id, PXA_key('page_title'), data_get($data, 'event.page_title'));
    carbon_set_post_meta($event_id, PXA_key('product_id'), data_get($data, 'event.product_id'));
    carbon_set_post_meta($event_id, PXA_key('product_name'), data_get($data, 'event.product_name'));
    carbon_set_post_meta($event_id, PXA_key('product_value'), data_get($data, 'event.value'));
    carbon_set_post_meta($event_id, PXA_key('predicted_ltv'), data_get($data, 'event.predicted_ltv'));
    carbon_set_post_meta($event_id, PXA_key('offer_ids'), data_get($data, 'event.offer_code') ?? data_get($data, 'event.offer_ids'));
    carbon_set_post_meta($event_id, PXA_key('content_name'), data_get($data, 'event.offer_name') ?? data_get($data, 'event.product_name') ?? data_get($data, 'event.content_name', ''));

    // Event
    $event_time          = strtotime($post_date_gmt);
    $event_date_day      = wp_date('l', $event_time);
    $event_day_in_month  = wp_date('j', $event_time);
    $event_month         = wp_date('F', $event_time);
    $event_time_start    = wp_date('G', $event_time);
    $event_time_end      = (wp_date('G', $event_time) + 1);
    $event_time_interval = str_pad($event_time_start, 2, '0', STR_PAD_LEFT) . '-' . str_pad($event_time_end, 2, '0', STR_PAD_LEFT);

    carbon_set_post_meta($event_id, PXA_key('event_name'), $event_name);
    carbon_set_post_meta($event_id, PXA_key('event_day'), $event_date_day);
    carbon_set_post_meta($event_id, PXA_key('event_day_in_month'), $event_day_in_month);
    carbon_set_post_meta($event_id, PXA_key('event_month'), $event_month);
    carbon_set_post_meta($event_id, PXA_key('event_time'), $event_time);
    carbon_set_post_meta($event_id, PXA_key('event_time_interval'), $event_time_interval);
    carbon_set_post_meta($event_id, PXA_key('event_url'), data_get($data, 'event.url'));

    // Parameters
    carbon_set_post_meta($event_id, PXA_key('traffic_source'), data_get($data, 'event.source'));
    carbon_set_post_meta($event_id, PXA_key('utm_source'), data_get($data, 'tracking.utm_source'));
    carbon_set_post_meta($event_id, PXA_key('utm_medium'), data_get($data, 'tracking.utm_medium'));
    carbon_set_post_meta($event_id, PXA_key('utm_campaign'), data_get($data, 'tracking.utm_campaign'));
    carbon_set_post_meta($event_id, PXA_key('utm_id'), data_get($data, 'tracking.utm_id'));
    carbon_set_post_meta($event_id, PXA_key('utm_term'), data_get($data, 'tracking.utm_term'));
    carbon_set_post_meta($event_id, PXA_key('utm_content'), data_get($data, 'tracking.utm_content'));
    carbon_set_post_meta($event_id, PXA_key('src'), data_get($data, 'tracking.src'));
    carbon_set_post_meta($event_id, PXA_key('sck'), data_get($data, 'tracking.sck'));

    // Facebook
    $fbc = data_get($data, 'tracking.fbc') ?? PXA_get_post_meta($lead_id, 'fbc');
    carbon_set_post_meta($event_id, PXA_key('fbc'), $fbc);

    $fbp = $tracking_fbp ?? PXA_get_post_meta($lead_id, 'fbp');
    carbon_set_post_meta($event_id, PXA_key('fbp'), $fbp);

    if ($send_event_immediate) {
        PXA_send_event_to_server([$event_id]);
    }

    // pxa_cache_flush();

    return [
        'lead_id'  => $lead_uid,
        'event_id' => $event_uid,
    ];
}
