<?php

/**
 * Action Scheduler
 */
add_action('init', function () {
    // Registro de Dispatch de Evento
    add_action('PXA_send_event_to_server', 'PXA_send_event_to_server');
    add_action('PXA_send_events_to_server', 'PXA_send_events_to_server');
    add_action('PXA_check_license', 'PXA_check_license');
    add_action('PXA_check_update', 'PXA_check_update');
    add_action('PXA_models_cleaner', 'PXA_models_cleaner');
    add_action('PXA_models_delete', 'PXA_models_delete');
    add_action('PXA_queues_cleaner', 'PXA_queues_cleaner');
    add_action('PXA_update_pixels', 'PXA_update_pixels');
    add_action('PXA_send_webhooks', 'PXA_send_webhooks');

    // Adicionar Agendamentos
    if ( ! as_has_scheduled_action('PXA_send_events_to_server', [], PXA_NAME)) {
        as_schedule_recurring_action(
            strtotime('today'),
            get_time(5, 'minutes'),
            'PXA_send_events_to_server',
            [],
            PXA_NAME,
            true
        );
    }

    if ( ! as_has_scheduled_action('PXA_check_license', [], PXA_NAME)) {
        if (PXA_license_status()) {
            as_schedule_recurring_action(
                strtotime('today'),
                get_time(7, 'days'),
                'PXA_check_license',
                [],
                PXA_NAME,
                true
            );
        } else {
            as_schedule_recurring_action(
                strtotime('today'),
                get_time(15, 'minutes'),
                'PXA_check_license',
                [],
                PXA_NAME,
                true
            );
        }
    }

    if ( ! as_has_scheduled_action('PXA_check_update', [], PXA_NAME)) {
        as_schedule_recurring_action(
            strtotime('today'),
            get_time(12, 'hours'),
            'PXA_check_update',
            [],
            PXA_NAME,
            true
        );
    }

    if ( ! as_has_scheduled_action('PXA_update_pixels', [], PXA_NAME)) {
        as_schedule_recurring_action(
            strtotime('today'),
            get_time(7, 'days'),
            'PXA_update_pixels',
            [],
            PXA_NAME,
            true
        );
    }

    // Limpeza Automática
    if (PXA_get_setting('cleaning_status', false)) {
        if ( ! as_has_scheduled_action('PXA_queues_cleaner', [], PXA_NAME)) {
            as_schedule_recurring_action(
                strtotime(PXA_get_setting('cleaning_time', '02:00:00')),
                get_time(6, 'hours'),
                'PXA_queues_cleaner',
                [],
                PXA_NAME,
                true
            );
        }

        if ( ! as_has_scheduled_action('PXA_models_cleaner', [], PXA_NAME)) {
            as_schedule_recurring_action(
                strtotime(PXA_get_setting('cleaning_time', '02:00:00')),
                get_time(6, 'hours'),
                'PXA_models_cleaner',
                [],
                PXA_NAME,
                true
            );
        }
    } else {
        as_unschedule_all_actions('PXA_queues_cleaner', [], PXA_NAME);
        as_unschedule_all_actions('PXA_models_cleaner', [], PXA_NAME);
    }

    if ( ! as_has_scheduled_action('PXA_send_webhooks', [], PXA_NAME)) {
        as_schedule_recurring_action(
            strtotime('today'),
            get_time(1, 'hours'),
            'PXA_send_webhooks',
            [],
            PXA_NAME,
            true
        );
    }
});

/**
 * Rest Cron
 */
add_action('rest_api_init', function () {
    register_rest_route(PXA_DOMAIN . '/v1', '/remote-run', [
        'methods'  => 'GET',
        'callback' => function ($request) {
            $data = $request->get_params();

            if (data_get($data, 'token') != PXA_get_custom_token()) {
                return new WP_REST_Response([
                    'status'   => 401,
                    'response' => __('Requisição Não Autorizada', 'pixel-x-app'),
                ]);
            }

            PXA_queues_worker();

            if (data_get($data, 'clean') == true) {
                PXA_models_cleaner();
                PXA_queues_cleaner();
            }

            return new WP_REST_Response([
                'status'   => 200,
                'response' => __('Requisição Recebida!', 'pixel-x-app'),
            ]);
        },
        'permission_callback' => '__return_true',
    ]);
});

/**
 * Functions
 */
function PXA_queues_worker()
{
    if (class_exists('ActionScheduler')) {
        ActionScheduler::runner()->run();
    } else {
        shell_exec('wp action-scheduler run');
        // shell_exec('wp action-scheduler run --force');
        // shell_exec('wp cron event run --all --quiet');
        // PXA_send_events_to_server();
    }
}

function PXA_queues_cleaner($all = false)
{
    global $wpdb;

    // Nome da tabela do Action Scheduler
    $table_actions = $wpdb->prefix . 'actionscheduler_actions';

    if ($all) {
        // Excluir todas as ações completadas, canceladas ou falhas
        $query = "DELETE FROM {$table_actions} WHERE status IN ('complete', 'canceled', 'failed', 'pending')";
    } else {
        // Excluir ações completadas, canceladas ou falhas com mais de 1 dia
        $yesterday = wp_date_utc(current_time('timestamp') - DAY_IN_SECONDS);

        $query = "DELETE FROM {$table_actions}
                      WHERE status IN ('complete', 'canceled', 'failed', 'pending')
                      AND scheduled_date_gmt < %s";

        $query = $wpdb->prepare($query, $yesterday);
    }

    // Executar a consulta para excluir ações
    $wpdb->query($query);

    $wpdb->query('TRUNCATE TABLE ' . $wpdb->prefix . 'actionscheduler_logs');
}

function PXA_update_pixels()
{
    $pixels = [
        [
            'name' => 'fb.js',
            'url'  => 'https://connect.facebook.net/en_US/fbevents.js',
        ],
        [
            'name' => 'gtag.js',
            'url'  => 'https://www.googletagmanager.com/gtag/js',
        ],
        // [
        //     'name' => 'ttk',
        //     'url'  => 'https://analytics.tiktok.com/i18n/pixel/events.js',
        // ],
        // [
        //     'name' => 'ptr',
        //     'url'  => 'https://s.pinimg.com/ct/core.js',
        // ],
        // [
        //     'name' => 'ttr',
        //     'url'  => 'https://platform.twitter.com/oct.js',
        // ],
        // [
        //     'name' => 'tbl',
        //     'url'  => 'https://cdn.taboola.com/libtrc/trc.js',
        // ],
    ];

    foreach ($pixels as $pixel) {
        // Baixa o arquivo
        $response = wp_remote_get($pixel['url']);

        if (is_wp_error($response)) {
            continue;
        }

        // Salva o arquivo
        $pixel_path = PXA_DIR . 'assets/js/pxa-' . $pixel['name'];
        file_put_contents($pixel_path, $response['body']);
    }

    $libs = [
        [
            'folder' => 'imask',
            'name'   => 'imask.js',
            'url'    => 'https://cdn.jsdelivr.net/npm/imask/dist/imask.min.js',
        ],
    ];

    foreach ($libs as $lib) {
        // Baixa o arquivo
        $response = wp_remote_get($lib['url']);

        if (is_wp_error($response)) {
            continue;
        }

        // Cria pasta
        $lib_folder = PXA_DIR . 'assets/libs/' . $lib['folder'];
        if ( ! is_dir($lib_folder)) {
            mkdir($lib_folder);
        }

        // Salva o arquivo
        $lib_path = $lib_folder . '/' . $lib['name'];
        file_put_contents($lib_path, $response['body']);
    }

    // Minify Base
    $js_base = file_get_contents(PXA_DIR . 'assets/js/pxa-base.js');
    file_put_contents(PXA_DIR . 'assets/js/pxa-base.min.js', minifierJS($js_base));
}

function PXA_models_cleaner()
{
    // Events
    PXA_delete_olds('event', -1, PXA_get_setting('cleaning_period_event', 3));

    // Leads
    $cleaning_period_lead = PXA_get_setting('cleaning_period_lead', 7);
    if ($cleaning_period_lead > 0) {
        PXA_delete_olds_wp('lead', -1, $cleaning_period_lead);
    }

    pxa_delete_orphaned();
}

function PXA_models_delete_schedule($models, $days = 0)
{
    // Certifique-se de que $models é um array
    if (is_int($models)) {
        $models = [$models];
    }

    // Verifique se a ação já está agendada
    if ( ! as_has_scheduled_action(
        'PXA_models_delete',
        [$models],
        PXA_NAME
    )) {
        // Calcule o tempo de agendamento corretamente
        $schedule_time = time() + get_time($days, 'days');

        // Agende a ação
        as_schedule_single_action(
            $schedule_time,
            'PXA_models_delete',
            [$models],
            PXA_NAME
        );
    }
}

function PXA_models_delete($models)
{
    // Certifique-se de que $models é um array
    if (is_int($models)) {
        $models = [$models];
    }

    foreach ($models as $model) {
        // Delete o post permanentemente
        wp_delete_post(intval($model), true);
    }
}

/*
 * Webhook
 */
function PXA_send_webhooks($force = false)
{
    // Get Webhooks
    $webhooks     = collect(PXA_get_setting('webhooks', []));
    $current_time = current_time('timestamp', true);
    $date_query   = null;

    if ( ! $force) {
        $date_query = [
            'relation' => 'OR',
            [
                'column'    => 'post_date_gmt',
                'after'     => wp_date_utc($current_time - get_time(1, 'hours')),
                'before'    => wp_date_utc($current_time),
                'inclusive' => true,
            ], [
                'column'    => 'post_date',
                'after'     => wp_date('Y-m-d H:i:s', $current_time - get_time(1, 'hours')),
                'before'    => wp_date('Y-m-d H:i:s', $current_time),
                'inclusive' => true,
            ]
        ];
    }

    if ( ! $webhooks->count()) {
        return;
    }

    /*
     * Get Data for Webhook
     */
    // Events
    $events = PXA_query([
        'post_type'      => PXA_key('event'),
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        // 'posts_per_page' => $batch_size,
        // 'paged'          => $batch + 1,
        // 'fields'         => 'ids',
        'date_query' => $date_query,
    ], true)['data'];
    $events = collect($events)
    ->map(function ($item) {
        return [
            'event_id'            => data_get($item, 'ID'),
            'event_uid'           => data_get($item, 'post_title'),
            'event_date'          => data_get($item, 'post_date'),
            'event_date_gmt'      => data_get($item, 'post_date_gmt'),
            'lead_id'             => data_get($item, PXA_key('lead_id')),
            'geo_ip'              => data_get($item, PXA_key('geo_ip')),
            'geo_device'          => data_get($item, PXA_key('geo_device')),
            'geo_country_name'    => data_get($item, PXA_key('geo_country_name')),
            'geo_country'         => data_get($item, PXA_key('geo_country')),
            'geo_state'           => data_get($item, PXA_key('geo_state')),
            'geo_city'            => data_get($item, PXA_key('geo_city')),
            'geo_currency'        => data_get($item, PXA_key('geo_currency')),
            'geo_zipcode'         => data_get($item, PXA_key('geo_zipcode')),
            'page_id'             => data_get($item, PXA_key('page_id')),
            'page_title'          => data_get($item, PXA_key('page_title')),
            'content_name'        => data_get($item, PXA_key('content_name')),
            'product_name'        => data_get($item, PXA_key('product_name')),
            'product_id'          => data_get($item, PXA_key('product_id')),
            'offer_ids'           => data_get($item, PXA_key('offer_ids')),
            'product_value'       => data_get($item, PXA_key('product_value')),
            'predicted_ltv'       => data_get($item, PXA_key('predicted_ltv')),
            'event_name'          => data_get($item, PXA_key('event_name')),
            'event_day'           => data_get($item, PXA_key('event_day')),
            'event_day_in_month'  => data_get($item, PXA_key('event_day_in_month')),
            'event_month'         => data_get($item, PXA_key('event_month')),
            'event_time'          => data_get($item, PXA_key('event_time')),
            'event_time_interval' => data_get($item, PXA_key('event_time_interval')),
            'event_url'           => data_get($item, PXA_key('event_url')),
            'traffic_source'      => data_get($item, PXA_key('traffic_source')),
            'utm_source'          => data_get($item, PXA_key('utm_source')),
            'utm_medium'          => data_get($item, PXA_key('utm_medium')),
            'utm_campaign'        => data_get($item, PXA_key('utm_campaign')),
            'utm_id'              => data_get($item, PXA_key('utm_id')),
            'utm_term'            => data_get($item, PXA_key('utm_term')),
            'utm_content'         => data_get($item, PXA_key('utm_content')),
            'src'                 => data_get($item, PXA_key('src')),
            'sck'                 => data_get($item, PXA_key('sck')),
            'fbc'                 => data_get($item, PXA_key('fbc')),
            'fbp'                 => data_get($item, PXA_key('fbp')),
            'fb_server_response'  => data_get($item, PXA_key('fb_api_response')),
        ];
    })
    ->toArray();

    // Leads
    $leads = PXA_query([
        'post_type'      => PXA_key('lead'),
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        // 'posts_per_page' => $batch_size,
        // 'paged'          => $batch + 1,
        // 'fields'         => 'ids',
        'date_query' => $date_query,
    ], true)['data'];
    $leads = collect($leads)
        ->map(function ($item) {
            return [
                'lead_uid'             => data_get($item, 'post_title'),
                'lead_id'              => data_get($item, 'ID'),
                'name'                 => data_get($item, PXA_key('name')),
                'email'                => data_get($item, PXA_key('email')),
                'phone'                => data_get($item, PXA_key('phone')),
                'document'             => data_get($item, PXA_key('document')),
                'ip'                   => data_get($item, PXA_key('ip')),
                'device'               => data_get($item, PXA_key('device')),
                'adress_street'        => data_get($item, PXA_key('adress_street')),
                'adress_street_number' => data_get($item, PXA_key('adress_street_number')),
                'adress_complement'    => data_get($item, PXA_key('adress_complement')),
                'adress_city'          => data_get($item, PXA_key('adress_city')),
                'adress_state'         => data_get($item, PXA_key('adress_state')),
                'adress_zipcode'       => data_get($item, PXA_key('adress_zipcode')),
                'adress_country_name'  => data_get($item, PXA_key('adress_country_name')),
                'adress_country'       => data_get($item, PXA_key('adress_country')),
                'first_fbc'            => data_get($item, PXA_key('first_fbc')),
                'first_utm_source'     => data_get($item, PXA_key('first_utm_source')),
                'first_utm_medium'     => data_get($item, PXA_key('first_utm_medium')),
                'first_utm_campaign'   => data_get($item, PXA_key('first_utm_campaign')),
                'first_utm_id'         => data_get($item, PXA_key('first_utm_id')),
                'first_utm_content'    => data_get($item, PXA_key('first_utm_content')),
                'first_utm_term'       => data_get($item, PXA_key('first_utm_term')),
                'first_src'            => data_get($item, PXA_key('first_src')),
                'first_sck'            => data_get($item, PXA_key('first_sck')),
                'fbp'                  => data_get($item, PXA_key('fbp')),
                'fbc'                  => data_get($item, PXA_key('fbc')),
                'src'                  => data_get($item, PXA_key('src')),
                'sck'                  => data_get($item, PXA_key('sck')),
                'utm_source'           => data_get($item, PXA_key('utm_source')),
                'utm_medium'           => data_get($item, PXA_key('utm_medium')),
                'utm_campaign'         => data_get($item, PXA_key('utm_campaign')),
                'utm_id'               => data_get($item, PXA_key('utm_id')),
                'utm_content'          => data_get($item, PXA_key('utm_content')),
                'utm_term'             => data_get($item, PXA_key('utm_term')),
            ];
        })
        ->toArray();

    // Send Webhook
    $webhooks->each(function ($item, $key) use ($events, $leads) {
        if (in_array('event', data_get($item, 'type'))) {
            PXA_send_webhook(data_get($item, 'url'), [
                'type' => 'event',
                'data' => array_values($events)
            ]);
        }

        if (in_array('lead', data_get($item, 'type'))) {
            PXA_send_webhook(data_get($item, 'url'), [
                'type' => 'lead',
                'data' => array_values($leads)
            ]);
        }
    });
}

function PXA_send_webhook($url, $data)
{
    $response = wp_remote_post($url, [
        'method'      => 'POST',
        'timeout'     => 60,
        'redirection' => 5,
        'httpversion' => '1.1',
        'blocking'    => true,
        'headers'     => [
            'Content-Type' => 'application/json'
        ],
        'body' => json_encode($data)
    ]);

    if (is_wp_error($response)) {
        return false;
    } else {
        return $response;
    }
}

function PXA_send_events_to_server($post_status = 'pending', $batch_size = 250)
{
    if ( ! PXA_license_status()) {
        return;
    }

    $models    = [];
    $immediate = boolval(PXA_get_setting('send_event_immediate')) ?? false;

    // Contar o número total de registros do post type 'pxa_event'
    $post_count = wp_count_posts(PXA_key('event'))->{$post_status};

    // Calcular o número total de lotes
    $total_batches = ceil($post_count / $batch_size);

    if ($immediate || $post_status == 'draft') {
        $delay = 0;
    } else {
        $delay = get_time(5, 'minutes');
    }

    // Processar cada lote separadamente
    for ($batch = 0; $batch < $total_batches; $batch++) {
        // Configurar a consulta paginada
        $args = [
            'post_type'      => PXA_key('event'),
            'post_status'    => $post_status,
            'posts_per_page' => $batch_size,
            'paged'          => $batch + 1,
            'fields'         => 'ids',
            'date_query'     => [
                'relation' => 'OR',
                [
                    'column'    => 'post_date_gmt',
                    'after'     => wp_date_utc(current_time('timestamp', true) - get_time(7, 'days')),
                    'before'    => wp_date_utc(current_time('timestamp', true) - $delay),
                    'inclusive' => true,
                ], [
                    'column'    => 'post_date',
                    'after'     => wp_date('Y-m-d H:i:s', current_time('timestamp', true) - get_time(7, 'days')),
                    'before'    => wp_date('Y-m-d H:i:s', current_time('timestamp', true) - $delay),
                    'inclusive' => true,
                ]
            ],
        ];

        // Fazer a consulta para o lote atual
        $models = PXA_query($args, false)['data'];

        // Redefine chave do array
        $models = array_column($models, 'ID');

        // Nenhum evento a ser enviado
        if ( ! count($models)) {
            continue;
        }

        // Se precisar, atualiza o status para 'pending'
        if ($post_status != 'pending') {
            foreach ($models as $model_id) {
                wp_update_post([
                    'ID'          => $model_id,
                    'post_status' => 'pending',
                ]);
            }
        }

        // Se definido envio imediato, senão enfileirar envio
        // if ($immediate) {
        //     // Processa imediatamente
        //     PXA_send_event_to_server($models);
        // } else {
        // Agendamento com intervalo incremental para evitar sobrecarga
        PXA_send_event_to_server_schedule($models);
        // }
    }

    // Limpeza do cache após cada lote
    pxa_cache_flush();
}

/*
 * Enviar Evento por Servidor
 */
function PXA_send_event_to_server_schedule($models)
{
    $models = [$models];

    if ( ! as_has_scheduled_action(
        'PXA_send_event_to_server',
        $models,
        PXA_NAME
    )) {
        as_schedule_single_action(
            time(),
            'PXA_send_event_to_server',
            $models,
            PXA_NAME,
            // true
        );
    }
}

function PXA_send_event_to_server($events = [])
{
    if ( ! PXA_license_status()) {
        return;
    }

    // Global
    $data                   = [];
    $response               = [];
    $status_request         = null;
    $status_error           = false;
    $events_count           = count($events);
    $currency               = PXA_get_setting('currency');
    $delete_event_immediate = boolval(PXA_get_setting('delete_event_immediate')) ?? false;
    $pixels                 = collect(PXA_get_setting('fb_pixels', [[
        'pixel'       => PXA_get_setting('fb_pixel'),
        'token'       => PXA_get_setting('fb_token'),
        'test_status' => PXA_get_setting('fb_test_status', false),
        'test_code'   => PXA_get_setting('fb_test_code'),
    ]]));

    if ($events_count < 1) {
        return;
    }

    foreach ($events as $event_id) {
        /*
         * Event
         */
        $event = PXA_get_model($event_id);

        if (data_get($event, 'post_status') != 'pending' || blank($event)) {
            continue;
        }

        /*
         * Lead
         */
        $lead_id = PXA_get_post_meta($event_id, 'lead_id');
        $lead    = PXA_get_model($lead_id);

        $lead_unqid = PXA_get_post_meta($lead_id, 'id') ?: null;
        $lead_name  = PXA_get_post_meta($lead_id, 'name') ?: null;
        $lead_fname = str_before($lead_name, ' ') ?: null;
        $lead_lname = str_after($lead_name, ' ') ?: null;
        $lead_email = PXA_get_post_meta($lead_id, 'email') ?: null;
        $lead_phone = number_raw(PXA_get_post_meta($lead_id, 'phone')) ?: null;

        $lead_keys = [
            'ip',
            'device',
            'adress_city',
            'adress_state',
            'adress_zipcode',
            'adress_country',
        ];

        foreach ($lead_keys as $key) {
            $$key = PXA_get_post_meta($lead_id, PXA_PREFIX . $key) ?? null;
        }

        /*
         * Event
         */
        $event_unqid         = PXA_get_post_meta($event_id, 'id') ?: null;
        $event_name          = PXA_get_post_meta($event_id, 'event_name') ?: null;
        $event_time          = PXA_get_post_meta($event_id, 'post_date_gmt') ?: PXA_get_post_meta($event_id, 'event_time') ?: current_time('timestamp');
        $event_url           = PXA_get_post_meta($event_id, 'event_url') ?: null;
        $event_day           = PXA_get_post_meta($event_id, 'event_day') ?: null;
        $event_day_in_month  = PXA_get_post_meta($event_id, 'event_day_in_month') ?: null;
        $event_month         = PXA_get_post_meta($event_id, 'event_month') ?: null;
        $event_time_interval = PXA_get_post_meta($event_id, 'event_time_interval') ?: null;

        if (blank($event_name)) {
            PXA_models_delete($event_id);

            continue;
        }

        // Geolocation
        $geo_ip       = PXA_get_post_meta($event_id, 'geo_ip') ?: $ip;
        $geo_device   = PXA_get_post_meta($event_id, 'geo_device') ?: $device;
        $geo_country  = PXA_get_post_meta($event_id, 'geo_country') ?: $adress_country;
        $geo_city     = PXA_get_post_meta($event_id, 'geo_city') ?: $adress_city;
        $geo_state    = PXA_get_post_meta($event_id, 'geo_state') ?: $adress_state;
        $geo_zipcode  = PXA_get_post_meta($event_id, 'geo_zipcode') ?: $adress_zipcode;
        $geo_currency = PXA_get_post_meta($event_id, 'geo_currency') ?: $currency;

        // Content
        $page_id       = PXA_get_post_meta($event_id, 'page_id');
        $page_title    = PXA_get_post_meta($event_id, 'page_title') ?: null;
        $product_id    = PXA_get_post_meta($event_id, 'product_id') ?: null;
        $product_name  = PXA_get_post_meta($event_id, 'product_name') ?: null;
        $product_value = PXA_get_post_meta($event_id, 'product_value') ?: null;
        $predicted_ltv = PXA_get_post_meta($event_id, 'predicted_ltv') ?: null;
        $offer_ids     = PXA_get_post_meta($event_id, 'offer_ids') ?: null;

        // Parameters
        $traffic_source = PXA_get_post_meta($event_id, 'traffic_source') ?: null;
        $utm_source     = PXA_get_post_meta($event_id, 'utm_source') ?: null;
        $utm_medium     = PXA_get_post_meta($event_id, 'utm_medium') ?: null;
        $utm_campaign   = PXA_get_post_meta($event_id, 'utm_campaign') ?: null;
        $utm_id         = PXA_get_post_meta($event_id, 'utm_id') ?: null;
        $utm_term       = PXA_get_post_meta($event_id, 'utm_term') ?: null;
        $utm_content    = PXA_get_post_meta($event_id, 'utm_content') ?: null;
        $src            = PXA_get_post_meta($event_id, 'src') ?: null;
        $sck            = PXA_get_post_meta($event_id, 'sck') ?: null;

        $content_ids  = [];
        $content_name = PXA_get_post_meta($event_id, 'content_name') ?: null;

        if ($product_id) {
            $content_ids[] = $product_id;
        }
        if ($offer_ids) {
            $content_ids = array_merge($content_ids, explode(',', $offer_ids));
        }

        // Facebook
        $fbp = PXA_get_post_meta($event_id, 'fbp')
            ?? PXA_get_post_meta($lead_id, 'fbp')
            ?? null;

        $fbc = PXA_get_post_meta($event_id, 'fbc')
            ?? PXA_get_post_meta($lead_id, 'fbc')
            ?? null;

        if (filled($fbc) && ! str_contains($fbc, '.')) {
            $fbc = "fb.1.$event_time.$fbc";
        }

        $data[$event_id] = [
            'pixels'           => get_post_meta($event_id, PXA_key('fb_pixels'), true) ?: null,
            'event_id'         => $event_unqid,
            'event_name'       => $event_name,
            'event_time'       => $event_time, // time()
            'event_source_url' => $event_url ?? null,
            'action_source'    => 'website',
            'user_data'        => [
                // 'lead_id'           => $lead_unqid ?? null,
                // 'subscription_id'           => $subscription_id ?? null,

                'client_ip_address' => $geo_ip     ?? null,
                'client_user_agent' => $geo_device ?? null,
                'external_id'       => $lead_unqid ?? null,
                'fn'                => $lead_fname ? hash('sha256', $lead_fname) : null,
                'ln'                => $lead_lname ? hash('sha256', $lead_lname) : null,
                'em'                => $lead_email ? hash('sha256', $lead_email) : null,
                'ph'                => $lead_phone ? hash('sha256', $lead_phone) : null,
                'ct'                => $geo_city ? hash('sha256', $geo_city) : null,
                'st'                => $geo_state ? hash('sha256', $geo_state) : null,
                'zp'                => $geo_zipcode ? hash('sha256', $geo_zipcode) : null,
                'country'           => $geo_country ? hash('sha256', $geo_country) : null,
                'fbc'               => $fbc,
                'fbp'               => $fbp,
            ],
            'custom_data' => [
                // Lead
                'nm' => $lead_name ? hash('sha256', $lead_name) : null,

                // Product and Content
                'currency'      => $geo_currency  ?? null,
                'value'         => $product_value ?? null,
                'predicted_ltv' => $predicted_ltv ?? null,
                'content_name'  => $content_name  ?? $product_name ?? null,
                'product_name'  => $product_name  ?? null,
                'content_type'  => 'product',
                'content_ids'   => count($content_ids) ? $content_ids : null,

                // Event
                'event_day'           => $event_day           ?? null,
                'event_day_in_month'  => $event_day_in_month  ?? null,
                'event_month'         => $event_month         ?? null,
                'event_time_interval' => $event_time_interval ?? null,

                // Parameters
                'traffic_source' => $traffic_source ?? null,
                'utm_source'     => $utm_source     ?? null,
                'utm_medium'     => $utm_medium     ?? null,
                'utm_campaign'   => $utm_campaign   ?? null,
                'utm_id'         => $utm_id         ?? null,
                'utm_term'       => $utm_term       ?? null,
                'utm_content'    => $utm_content    ?? null,
                'src'            => $src            ?? null,
                'sck'            => $sck            ?? null,
            ],
        ];

        // Salvar Corpo
        carbon_set_post_meta(
            $event_id,
            PXA_key('fb_api_playload'),
            wp_json_encode(removeNullValues($data[$event_id]))
        );
    }

    if (count($data) < 1) {
        return;
    }

    $data = collect(array_values($data));

    foreach ($pixels as $pixel) {
        $fb_pixel = data_get($pixel, 'pixel');
        $fb_token = data_get($pixel, 'token');

        if (blank($fb_pixel) || blank($fb_token)) {
            continue;
        }

        $fb_test_code = null;
        if (data_get($pixel, 'test_status', false)) {
            $fb_test_code = data_get($pixel, 'test_code');
        }

        $data_pixel = $data->filter(function ($item) use ($fb_pixel) {
            return str_contains(data_get($item, 'pixels'), $fb_pixel)
                || blank(data_get($item, 'pixels'));
        });
        $data_pixel = $data_pixel->map(function ($item) {
            unset($item['pixels']);

            return $item;
        });

        if ( ! $data_pixel->count()) {
            continue;
        }

        $payload = [
            'data'            => $data_pixel->toArray(),
            'test_event_code' => $fb_test_code,
            'access_token'    => $fb_token
        ];

        $dataString = wp_json_encode(removeNullValues($payload));

        // $url = "https://graph.facebook.com/v20.0/{$fb_pixel}/events?access_token={$fb_token}";
        $ch = curl_init("https://graph.facebook.com/v20.0/{$fb_pixel}/events");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($dataString)
            ]
        );

        try {
            $response[$fb_pixel] = curl_exec($ch);
            $response[$fb_pixel] = json_decode($response[$fb_pixel], true);
            $status_request      = $response[$fb_pixel] !== false && isset($response[$fb_pixel]['events_received']);

            if ( ! $status_request) {
                $status_error        = true;
                $response[$fb_pixel] = filled(curl_error($ch)) ? curl_error($ch) : $response[$fb_pixel];
            }

            foreach ($events as $event_id) {
                if ($status_request) {
                    if ( ! $delete_event_immediate) {
                        carbon_set_post_meta(
                            $event_id,
                            PXA_key('fb_api_response'),
                            json_encode($response)
                        );

                        wp_update_post([
                            'ID'          => $event_id,
                            'post_status' => 'publish',
                        ]);
                    }
                } elseif ($events_count == 1) {
                    carbon_set_post_meta(
                        $event_id,
                        PXA_key('fb_api_response'),
                        json_encode($response)
                    );

                    wp_update_post([
                        'ID'          => $event_id,
                        'post_status' => 'draft',
                    ]);
                }
            }

            if ( ! $status_request && $events_count > 1) {
                // Divide o lote em 5 partes e reagenda essas partes
                $events_chunks = array_chunk($events, ceil($events_count / 5));

                foreach ($events_chunks as $chunk) {
                    PXA_send_event_to_server_schedule($chunk);
                }
            }
        } catch (Expection $e) {
            // print_r($e->message());
        } finally {
            // Garante que o handle curl seja fechado
            curl_close($ch);
        }
    }

    /*
     * Agendar Deletar Evento
     */
    if ( ! $status_error && $delete_event_immediate) {
        PXA_models_delete($events);
    }

    /*
     * SDK Facebook
     */
    // "facebook/php-business-sdk": "^19.0"
    //     foreach ($pixels as $pixel) {
    //         $fb_pixel = data_get($pixel, 'pixel');
    //         $fb_token = data_get($pixel, 'token');
    //
    //         if (blank($fb_pixel) || blank($fb_token)) {
    //             continue;
    //         }
    //
    //         $fb_test_code = null;
    //         if (data_get($pixel, 'test_status', false)) {
    //             $fb_test_code = data_get($pixel, 'test_code');
    //         }
    //
    //         // Initialize
    //         FacebookAds\Api::init(null, null, $fb_token);
    //         $api = FacebookAds\Api::instance();
    //
    //         // Create Server Side Event Object
    //         $user_data = (new FacebookAds\Object\ServerSide\UserData());
    //         $user_data
    //             ->setEmail($lead_email)
    //             ->setPhone($lead_phone)
    //             ->setFirstName($lead_fname)
    //             ->setLastName($lead_lname)
    //             ->setExternalId($lead_unqid)
    //             ->setCity($geo_city)
    //             ->setState($geo_state)
    //             ->setZipCode($geo_zipcode)
    //             ->setCountryCode($geo_country)
    //             ->setClientIpAddress($geo_ip)
    //             ->setClientUserAgent($geo_device)
    //             ->setFbc($fbc)
    //             ->setFbp($fbp);
    //
    //         $custom_content = (new FacebookAds\Object\ServerSide\Content());
    //         $custom_content
    //             ->setProductId($product_id)
    //             ->setTitle($product_name)
    //             ->setItemPrice($product_value);
    //
    //         $custom_contents = [];
    //         array_push($custom_contents, $custom_content);
    //
    //         $custom_data = (new FacebookAds\Object\ServerSide\CustomData());
    //         $custom_data
    //             ->setValue($product_value)
    //             ->setCurrency($geo_currency)
    //             ->setContentName($content_name ?? $product_name)
    //             ->setContentIds($content_ids)
    //             ->setContentType('product')
    //             // ->setPredictedLtv()
    //             ->setCustomProperties([
    //                 // Lead
    //                 'nm' => $lead_name ? hash('sha256', $lead_name) : null,
    //
    //                 // Event
    //                 'event_day'           => $event_day           ?? null,
    //                 'event_day_in_month'  => $event_day_in_month  ?? null,
    //                 'event_month'         => $event_month         ?? null,
    //                 'event_time_interval' => $event_time_interval ?? null,
    //
    //                 // Parameters
    //                 'traffic_source' => $traffic_source ?? null,
    //                 'utm_source'     => $utm_source     ?? null,
    //                 'utm_medium'     => $utm_medium     ?? null,
    //                 'utm_campaign'   => $utm_campaign   ?? null,
    //                 'utm_id'         => $utm_id         ?? null,
    //                 'utm_term'       => $utm_term       ?? null,
    //                 'utm_content'    => $utm_content    ?? null,
    //                 'src'            => $src            ?? null,
    //                 'sck'            => $sck            ?? null,
    //             ])
    //             ->setContents($custom_contents);
    //
    //         $event = (new FacebookAds\Object\ServerSide\Event());
    //         $event
    //             ->setEventId($event_unqid)
    //             ->setEventName($event_name)
    //             ->setEventTime($event_time)
    //             ->setEventSourceUrl($event_url ?? null)
    //             ->setActionSource(FacebookAds\Object\ServerSide\ActionSource::WEBSITE)
    //             ->setUserData($user_data)
    //             ->setCustomData($custom_data);
    //
    //         $events = [];
    //         array_push($events, $event);
    //
    //         // Create event request
    //         $request = (new FacebookAds\Object\ServerSide\EventRequest($fb_pixel));
    //         $request->setEvents($events);
    //         $request->setTestEventCode($fb_test_code);
    //
    //         try {
    //             $response = $request->execute();
    //
    //             if ($response !== false && str_contains($response, 'events_received')) {
    //                 wp_update_post([
    //                     'ID'          => $event_id,
    //                     'post_status' => 'publish',
    //                 ]);
    //             } else {
    //                 wp_update_post([
    //                     'ID'          => $event_id,
    //                     'post_status' => 'draft',
    //                 ]);
    //             }
    //
    //             carbon_set_post_meta($event_id, PXA_key('fb_api_playload'), json_encode($request->getEvents()));
    //             carbon_set_post_meta($event_id, PXA_key('fb_api_response'), json_encode($response));
    //         } catch (Expection $e) {
    //             // print_r($e->message());
    //         }
    //
    //         // Close handle
    //         curl_close($ch);
    //     }

    return;
    // Error Log
    // https://developers.facebook.com/docs/graph-api/guides/error-handling/
}
