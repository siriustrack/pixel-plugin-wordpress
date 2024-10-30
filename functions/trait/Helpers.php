<?php

if ( ! function_exists('PXA_get_image')) {
    function PXA_get_image($value, $format = 'png')
    {
        return PXA_URL . 'assets/images/' . $value . '.' . $format;
    }
}

if ( ! function_exists('PXA_asset')) {
    function PXA_asset($value)
    {
        return PXA_URL . 'assets/js/pxa-' . $value . '?version=' . PXA_VERSION;
    }
}

if ( ! function_exists('PXA_asset_lib')) {
    function PXA_asset_lib($value)
    {
        return PXA_URL . 'assets/libs/' . $value . '?version=' . PXA_VERSION;
    }
}

if ( ! function_exists('PXA_key')) {
    function PXA_key($key)
    {
        return PXA_PREFIX . $key;
    }
}

if ( ! function_exists('PXA__key')) {
    function PXA__key($key)
    {
        return '_' . PXA_key($key);
    }
}

if ( ! function_exists('money_format')) {
    function money_format($value)
    {
        if (str_contains($value, '.') && str_contains($value, ',')) {
            $value = preg_replace('/[^\d,]/', '', $value);
            $value = str_replace(',', '.', $value);
        }

        return number_format((float) $value, 2, '.', '');
    }
}

if ( ! function_exists('pxa_options')) {
    function pxa_options()
    {
        return pxa_cache_remember(
            'options',
            function () {
                global $wpdb;
                $options     = $wpdb->get_results("SELECT option_name, option_value FROM $wpdb->options WHERE option_name LIKE '_pxa_%'", ARRAY_A);
                $pxa_options = [];

                if ($options) {
                    foreach ($options as $option) {
                        $pxa_options[ $option['option_name'] ] = $option['option_value'];
                    }
                }

                return $pxa_options;
            },
            get_time(10, 'minutes')
        );
    }
}

if ( ! function_exists('PXA_get_setting')) {
    function PXA_get_setting($key, $default = null)
    {
        $pxa_options = pxa_options();

        if ($values = data_get($pxa_options, PXA__key($key))) {
            return $values;
        }

        if ($values = carbon_get_theme_option(PXA_key($key))) {
            return $values;
        }

        return $default;
    }
}

if ( ! function_exists('PXA_get_post_meta')) {
    function PXA_get_post_meta($model_id, $key = null, $default = null)
    {
        // $options = pxa_cache_remember(
        //     implode('_', ['get_post_meta', $model_id]),
        //     fn () => get_post_meta($model_id)
        // );
        $options = get_post_meta($model_id);

        if (filled($key)) {
            if ($value = data_get($options, PXA__key($key))) {
                return reset($value);
            }

            if ($value = carbon_get_post_meta($model_id, PXA_key($key))) {
                return $value;
            }

            return $default;
        }

        foreach ($options as $key => $option) {
            if (str_starts_with($key, '_')) {
                $options[substr($key, 1)] = $option[0];
                unset($options[$key]);
            }
        }

        return $options;
    }
}

if ( ! function_exists('PXA_get_custom_token')) {
    function PXA_get_custom_token()
    {
        $custom_token = PXA_get_setting('custom_token');

        if (blank($custom_token)) {
            $custom_token = wp_generate_uuid4();
            update_option(PXA__key('custom_token'), $custom_token);
        }

        return $custom_token;
    }
}

if ( ! function_exists('PXA_pages')) {
    function PXA_pages()
    {
        $post_types = [
            PXA_key('lead'),
            PXA_key('event'),
            PXA_key('conversion'),
            PXA_key('integration'),
            PXA_key('link_tracked'),
        ];

        return in_array(data_get($_GET, 'page'), [
            PXA_key('dashboard'),
            PXA_key('license'),
            PXA_key('settings'),
            PXA_key('documentation'),
            PXA_key('integrations'),
        ])
        || in_array(data_get($_GET, 'post_type'), $post_types)
        || in_array(get_post_type(get_the_ID()), $post_types);
    }
}

function PXA_get_models($post_type = 'page', $status = 'any', $query = [], $metadata = true)
{
    return pxa_cache_remember(
        implode('_', ['get_model', $post_type, $status, hash('sha256', serialize($query)), $metadata]),
        function () use ($post_type, $status, $query, $metadata) {
            if ( ! in_array($post_type, ['post', 'page', 'any'])) {
                $post_type = PXA_key($post_type);
            }

            if ($post_type === 'any') {
                $post_type = [
                    'post',
                    'page',
                    'opf_funnel',
                    'e-landing-page',
                    'wffn_landing',
                    'wffn_optin',
                    'wffn_oty',
                ];
            }

            $args = [
                'post_type'      => $post_type,
                'post_status'    => $status,
                'posts_per_page' => 250,
                'meta_query'     => $query,
            ];

            $results = [];
            $page    = 1;

            do {
                $args['paged'] = $page;

                $models = PXA_query($args, $metadata);

                $results = array_merge($results, $models['data']);

                $page++;
            } while ($models['pages'] >= $page);

            return collect($results);
        }
    );
}

function PXA_get_model_list($post_type = 'page', $status = 'publish', $query = [])
{
    $models = PXA_get_models($post_type, $status, $query, false);

    $result = [];

    foreach ($models as $model) {
        $model_id    = data_get($model, 'ID');
        $model_title = data_get($model, 'post_title');
        $mode_type   = data_get($model, 'post_type');

        if ($mode_type == 'opf_funnel') {
            $result[$model_id] = '[OPF] ' . $model_title;
        } elseif ($mode_type == 'e-landing-page') {
            $result[$model_id] = '[ELP] ' . $model_title;
        } elseif (in_array($mode_type, [
            'wffn_landing',
            'wffn_optin',
            'wffn_oty',
        ])) {
            $result[$model_id] = '[WFF] ' . $model_title;
        } else {
            $result[$model_id] = $model_title;
        }
    }

    return $result;
}

function PXA_query($args, $metadata = true)
{
    $query  = new WP_Query($args);
    $result = [];

    foreach ($query->posts as $model) {
        if (is_numeric($model)) {
            $model = [
                'ID' => $model
            ];
        }

        $model_id = data_get($model, 'ID');

        if ($metadata) {
            $result[$model_id] = array_merge((array) $model, PXA_get_post_meta($model_id));
        } elseif (is_int($model)) {
            $result[] = $model;
        } else {
            $result[$model_id] = (array) $model;
        }
    }

    return [
        'data'  => $result,
        'total' => $query->found_posts,
        'pages' => (data_get($args, 'posts_per_page')) ? ceil($query->found_posts / $args['posts_per_page']) : 0,
    ];
}

function PXA_get_page_by_title($title, $post_type)
{
    $args = [
        'post_type'              => $post_type,
        'post_title'             => $title,
        'posts_per_page'         => 1,
        'no_found_rows'          => true,
        'ignore_sticky_posts'    => true,
        'update_post_term_cache' => false,
        'update_post_meta_cache' => false,
    ];

    $query = PXA_query($args, true);

    if ( ! empty($query->post)) {
        return $query->post;
    } else {
        return null;
    }
}

function PXA_get_model($id, $post_type = 'page', $query = [])
{
    // if (is_null($id)) {
    //     return null;
    // }

    if (is_numeric($id)) {
        $model = get_post($id);

        if ($model) {
            return array_merge(
                (array) $model,
                PXA_get_post_meta($model->ID)
            );
        }
    }

    if ( ! in_array($post_type, ['post', 'page'])) {
        $post_type = PXA_key($post_type);
    }

    if ($model = PXA_get_page_by_title($id, $post_type)) {
        return (array) $model;
    }

    if (blank($query)) {
        $query = [
            'relation' => 'OR',
            [
                'key'     => PXA_key('id'),
                'value'   => $id,
                'compare' => '='
            ], [
                'key'     => PXA_key('email'),
                'value'   => $id,
                'compare' => '='
            ], [
                'key'     => PXA_key('phone'),
                'value'   => $id,
                'compare' => 'LIKE'
            ], [
                'key'     => PXA_key('document'),
                'value'   => $id,
                'compare' => '='
            ], [
                'key'     => PXA_key('ip'),
                'value'   => $id,
                'compare' => '='
            ], [
                'key'     => PXA_key('fbp'),
                'value'   => $id,
                'compare' => '='
            ], [
                'key'     => PXA_key('fbc'),
                'value'   => $id,
                'compare' => '='
            ]
        ];
    }

    // Post não encontrado pelo ID primário, agora vamos buscar pelos metadados.
    $args = [
        'post_type'      => $post_type,
        'posts_per_page' => 1,
        'post_status'    => 'any',
        'meta_query'     => $query
        // 'search_columns' => [  'post_name', 'post_title' ],
    ];

    $query = PXA_query($args);

    if ($query['total']) {
        return array_shift($query['data']);
    }

    return null;
}

if ( ! function_exists('PXA_events_group_by_lead')) {
    function PXA_events_group_by_lead()
    {
        // return pxa_cache_remember(
        //     'events_group_by_lead',
        //     function () {
        $events = PXA_query([
            'post_type'      => PXA_key('event'),
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ], true)['data'];

        // $events = PXA_get_models('event', '', [
        //     'fields'=>'ids',
        // ]);

        return collect($events)
            ->groupBy(function ($item) {
                return data_get($item, PXA_key('lead_id'), '');
            });
        //     },
        //     60
        // );
    }
}

function PXA_update_post_status($post_type, $original_status, $new_status)
{
    global $wpdb;

    $post_type = PXA_key($post_type);

    // Construa a query SQL para atualizar os posts
    $sql = "UPDATE {$wpdb->posts} SET post_status = %s WHERE post_status = %s AND post_type = %s";
    $sql = $wpdb->prepare($sql, $new_status, $original_status, $post_type);

    // Prepare e execute a query
    return $wpdb->query(trim($sql));
}

function PXA_update_models_status($post_ids, $original_status, $new_status)
{
    global $wpdb;

    // Se $post_ids não for um array, transformá-lo em um array
    if ( ! is_array($post_ids)) {
        $post_ids = [$post_ids];
    }

    // Sanitizar os IDs para evitar SQL injection
    $post_ids = array_map('intval', $post_ids);

    // Construir a query SQL para atualizar os posts
    $placeholders = implode(',', array_fill(0, count($post_ids), '%d'));
    $sql          = "UPDATE {$wpdb->posts} SET post_status = %s WHERE post_status = %s AND ID IN ($placeholders)";

    // Preparar os valores para a query
    $query_args = array_merge([$new_status, $original_status], $post_ids);

    // Prepare e execute a query
    $prepared_sql = $wpdb->prepare($sql, ...$query_args);

    return $wpdb->query(trim($prepared_sql));
}

function PXA_delete_olds_wp($post_type, $total = 500, $days_before = null, $status = 'publish')
{
    $args = [
        'post_type'      => PXA_key($post_type),
        'posts_per_page' => $total,
        'order'          => 'DESC',
        'orderby'        => 'date',
        'post_status'    => $status,
        'fields'         => 'ids',
    ];

    if ($days_before > 0) {
        $args['date_query'] = [
            [
                'before' => date('Y-m-d H:i:s', current_time('timestamp') - get_time($days_before, 'days')),
            ],
        ];
    }

    $models        = PXA_query($args);
    $ids_to_delete = [];

    foreach (data_get($models, 'data') as $model) {
        $model_id = data_get($model, 'ID');

        if ($post_type != 'lead') {
            $ids_to_delete[] = $model_id;
        } elseif (
            blank(data_get($model, PXA_key('name')))
            && blank(data_get($model, PXA_key('email')))
            && blank(data_get($model, PXA_key('phone')))
        ) {
            $ids_to_delete[] = $model_id;
        }

        // Se o tamanho do lote for 50, envia os IDs para a função e limpa o array
        if (count($ids_to_delete) === 50) {
            PXA_models_delete_schedule($ids_to_delete);
            $ids_to_delete = [];
        }
    }

    // Envia os IDs restantes, caso existam
    if ( ! empty($ids_to_delete)) {
        PXA_models_delete_schedule($ids_to_delete);
    }
}

function PXA_delete_olds($post_type, $total = 500, $days_before = null, $status = 'publish')
{
    global $wpdb;

    $post_type = PXA_key($post_type);
    $limit     = ($total > 0) ? 'LIMIT ' . $total : '';
    $date      = $days_before ? "AND post_date <= DATE_SUB(NOW(), INTERVAL $days_before DAY)" : '';
    $status    = "AND post_status = '$status'";

    if ($post_type == PXA_key('lead')) {
        $post_type = "'" . implode("','", [PXA_key('lead'), PXA_key('event')]) . "'";
    } else {
        $post_type = "'$post_type'";
    }

    $sql = "DELETE FROM $wpdb->posts WHERE post_type IN ($post_type) $status $date $limit";

    $wpdb->query(trim($sql));
}

function pxa_delete_orphaned()
{
    global $wpdb;

    $sql = 'DELETE pm FROM wp_postmeta pm LEFT JOIN wp_posts p ON p.ID = pm.post_id WHERE p.ID IS NULL;';

    $wpdb->query(trim($sql));
}

if ( ! function_exists('minifierJS')) {
    function minifierJS($content)
    {
        $minifier = new \MatthiasMullie\Minify\JS();
        $minifier->add($content);

        return $minifier->minify();
    }
}

if ( ! function_exists('minifierCSS')) {
    function minifierCSS($content)
    {
        $minifier = new \MatthiasMullie\Minify\CSS();
        $minifier->add($content);

        return $minifier->minify();
    }
}

add_filter('upload_mimes', function ($types) {
    $types['json'] = 'application/json';

    return $types;
});

//
if ( ! function_exists('PXA_is_bot')) {
    function PXA_is_bot()
    {
        // https://github.com/monperrus/crawler-user-agents/blob/master/crawler-user-agents.json
        return array_contains(
            $_SERVER['HTTP_USER_AGENT'],
            [
                'bot',
                'Bot',
                'HTTrack',
                'findlink',
                'facebookexternalhit',
                'WP Rocket',
                'Preload'
            ]
        );
    }
}

if ( ! function_exists('PXA_export_leads')) {
    function PXA_export_leads()
    {
        // Obtém todos os registros do post type
        $models = PXA_get_models('lead');

        // Cria um novo arquivo CSV
        $file = fopen('php://output', 'w');

        // Escreve os cabeçalhos do arquivo CSV
        fputcsv($file, [
            __('ID', 'pixel-x-app'),
            __('Nome', 'pixel-x-app'),
            __('Email', 'pixel-x-app'),
            __('Telefone', 'pixel-x-app'),
            __('IP', 'pixel-x-app'),
            __('Dispositivo', 'pixel-x-app'),
            __('Endereço', 'pixel-x-app'),
            __('Número', 'pixel-x-app'),
            __('Complemento', 'pixel-x-app'),
            __('Cidade', 'pixel-x-app'),
            __('Estado', 'pixel-x-app'),
            __('Código Postal', 'pixel-x-app'),
            __('País', 'pixel-x-app'),
            __('Código do País', 'pixel-x-app'),
            __('Primeiro FBC', 'pixel-x-app'),
            __('Primeiro UTM Source', 'pixel-x-app'),
            __('Primeiro UTM Medium', 'pixel-x-app'),
            __('Primeiro UTM Campaign', 'pixel-x-app'),
            __('Primeiro UTM Id', 'pixel-x-app'),
            __('Primeiro UTM Content', 'pixel-x-app'),
            __('Primeiro UTM Term', 'pixel-x-app'),
            __('Primeiro SRC', 'pixel-x-app'),
            __('Primeiro SCK', 'pixel-x-app'),
            __('FBC', 'pixel-x-app'),
            __('FBP', 'pixel-x-app'),
            __('UTM Source', 'pixel-x-app'),
            __('UTM Medium', 'pixel-x-app'),
            __('UTM Campaign', 'pixel-x-app'),
            __('UTM Id', 'pixel-x-app'),
            __('UTM Content', 'pixel-x-app'),
            __('UTM Term', 'pixel-x-app'),
            __('SRC', 'pixel-x-app'),
            __('SCK', 'pixel-x-app'),
        ]);

        // Escreve os dados dos posts no arquivo CSV
        foreach ($models as $model) {
            fputcsv($file, [
                data_get($model, PXA_key('id'), ''),
                data_get($model, PXA_key('name'), ''),
                data_get($model, PXA_key('email'), ''),
                data_get($model, PXA_key('phone'), ''),
                data_get($model, PXA_key('ip'), ''),
                data_get($model, PXA_key('device'), ''),
                data_get($model, PXA_key('adress_street'), ''),
                data_get($model, PXA_key('adress_street_number'), ''),
                data_get($model, PXA_key('adress_complement'), ''),
                data_get($model, PXA_key('adress_city'), ''),
                data_get($model, PXA_key('adress_state'), ''),
                data_get($model, PXA_key('adress_zipcode'), ''),
                data_get($model, PXA_key('adress_country_name'), ''),
                data_get($model, PXA_key('adress_country'), ''),
                data_get($model, PXA_key('first_fbc'), ''),
                data_get($model, PXA_key('first_utm_source'), ''),
                data_get($model, PXA_key('first_utm_medium'), ''),
                data_get($model, PXA_key('first_utm_campaign'), ''),
                data_get($model, PXA_key('first_utm_id'), ''),
                data_get($model, PXA_key('first_utm_content'), ''),
                data_get($model, PXA_key('first_utm_term'), ''),
                data_get($model, PXA_key('first_src'), ''),
                data_get($model, PXA_key('first_sck'), ''),
                data_get($model, PXA_key('fbc'), ''),
                data_get($model, PXA_key('fbp'), ''),
                data_get($model, PXA_key('utm_source'), ''),
                data_get($model, PXA_key('utm_medium'), ''),
                data_get($model, PXA_key('utm_campaign'), ''),
                data_get($model, PXA_key('utm_id'), ''),
                data_get($model, PXA_key('utm_content'), ''),
                data_get($model, PXA_key('utm_term'), ''),
                data_get($model, PXA_key('utm_src'), ''),
                data_get($model, PXA_key('utm_sck'), ''),
            ]);
        }

        // Fecha o arquivo CSV
        fclose($file);
        exit;
    }
}

if ( ! function_exists('PXA_export_events')) {
    function PXA_export_events()
    {
        // Obtém todos os registros do post type
        $models = PXA_get_models('event');

        // Cria um novo arquivo CSV
        $file = fopen('php://output', 'w');

        // Escreve os cabeçalhos do arquivo CSV
        fputcsv($file, [
            __('ID', 'pixel-x-app'),
            __('IP', 'pixel-x-app'),
            __('Dispositivo', 'pixel-x-app'),
            __('País', 'pixel-x-app'),
            __('Código do País', 'pixel-x-app'),
            __('Estado', 'pixel-x-app'),
            __('Cidade', 'pixel-x-app'),
            __('Moeda', 'pixel-x-app'),
            __('Código Postal', 'pixel-x-app'),
            __('Nome do Produto', 'pixel-x-app'),
            __('ID do Produto', 'pixel-x-app'),
            __('Valor do Produto', 'pixel-x-app'),
            __('IDs das Ofertas', 'pixel-x-app'),
            __('Título da Página', 'pixel-x-app'),
            __('Nome do Conteúdo', 'pixel-x-app'),
            __('Página', 'pixel-x-app'),
            __('Nome do Evento', 'pixel-x-app'),
            __('Dia da Semana', 'pixel-x-app'),
            __('Dia do Mês', 'pixel-x-app'),
            __('Mês', 'pixel-x-app'),
            __('Intervalo de Hora', 'pixel-x-app'),
            __('Data e Hora', 'pixel-x-app'),
            __('URL do Evento', 'pixel-x-app'),
            __('Fonte de Tráfego', 'pixel-x-app'),
            __('utm_source', 'pixel-x-app'),
            __('utm_medium', 'pixel-x-app'),
            __('utm_campaign', 'pixel-x-app'),
            __('utm_id', 'pixel-x-app'),
            __('utm_term', 'pixel-x-app'),
            __('utm_content', 'pixel-x-app'),
            __('src', 'pixel-x-app'),
            __('sck', 'pixel-x-app'),
            __('FBC', 'pixel-x-app'),
            __('FBP', 'pixel-x-app'),
        ]);

        // Escreve os dados dos posts no arquivo CSV
        foreach ($models as $model) {
            fputcsv($file, [
                data_get($model, PXA_key('id'), ''),
                data_get($model, PXA_key('geo_ip'), ''),
                data_get($model, PXA_key('geo_device'), ''),
                data_get($model, PXA_key('geo_country_name'), ''),
                data_get($model, PXA_key('geo_country'), ''),
                data_get($model, PXA_key('geo_state'), ''),
                data_get($model, PXA_key('geo_city'), ''),
                data_get($model, PXA_key('geo_currency'), ''),
                data_get($model, PXA_key('geo_zipcode'), ''),
                data_get($model, PXA_key('product_name'), ''),
                data_get($model, PXA_key('product_id'), ''),
                data_get($model, PXA_key('product_value'), ''),
                data_get($model, PXA_key('offer_ids'), ''),
                data_get($model, PXA_key('page_title'), ''),
                data_get($model, PXA_key('content_name'), ''),
                data_get($model, PXA_key('page_id'), ''),
                data_get($model, PXA_key('event_name'), ''),
                data_get($model, PXA_key('event_day'), ''),
                data_get($model, PXA_key('event_day_in_month'), ''),
                data_get($model, PXA_key('event_month'), ''),
                data_get($model, PXA_key('event_time_interval'), ''),
                data_get($model, PXA_key('event_time'), ''),
                data_get($model, PXA_key('event_url'), ''),
                data_get($model, PXA_key('traffic_source'), ''),
                data_get($model, PXA_key('utm_source'), ''),
                data_get($model, PXA_key('utm_medium'), ''),
                data_get($model, PXA_key('utm_campaign'), ''),
                data_get($model, PXA_key('utm_id'), ''),
                data_get($model, PXA_key('utm_term'), ''),
                data_get($model, PXA_key('utm_content'), ''),
                data_get($model, PXA_key('src'), ''),
                data_get($model, PXA_key('sck'), ''),
                data_get($model, PXA_key('fbc'), ''),
                data_get($model, PXA_key('fbp'), ''),
            ]);
        }

        // Fecha o arquivo CSV
        fclose($file);
        exit;
    }
}

if ( ! function_exists('PXA_export_conversions')) {
    function PXA_export_conversions()
    {
        // Obtém todos os registros do post type
        $models = PXA_get_models('conversion');

        // Cria um novo arquivo CSV
        $file = fopen('php://output', 'w');

        $exports = [];

        // Escreve os dados dos posts no arquivo CSV
        foreach ($models as $model) {
            $exports[] = [
                'title'        => $model['post_title'],
                'display_on'   => data_get($model, PXA_key('display_on'), ''),
                'trigger'      => data_get($model, PXA_key('trigger'), ''),
                'time'         => data_get($model, PXA_key('time'), ''),
                'class'        => data_get($model, PXA_key('class'), ''),
                'scroll'       => data_get($model, PXA_key('scroll'), ''),
                'event'        => data_get($model, PXA_key('event'), ''),
                'content_name' => data_get($model, PXA_key('content_name'), ''),
                'event_custom' => data_get($model, PXA_key('event_custom'), ''),
                // 'gg_ads_label_convertion' => data_get($model, PXA_key('gg_ads_label_convertion'), ''),
                'product_name'  => data_get($model, PXA_key('product_name'), ''),
                'product_id'    => data_get($model, PXA_key('product_id'), ''),
                'product_value' => data_get($model, PXA_key('product_value'), ''),
                'offer_ids'     => data_get($model, PXA_key('offer_ids'), ''),
            ];
        }

        echo json_encode($exports);

        // Fecha o arquivo json
        fclose($file);
        exit;
    }
}

if ( ! function_exists('pxa_cache_remember')) {
    function pxa_cache_remember($key, Closure $callback, $expiration = 300)
    {
        $cache_name = PXA_key('cache_' . $key);

        // Primeiro, tente obter dados do Cache de Objeto
        if (function_exists('wp_cache_get')) {
            $data = wp_cache_get($cache_name, 'pxa');

            if ($data !== false) {
                return $data;
            }
        }

        // Depois, tente obter dados do cache em arquivo
        $data = pxa_cache_remember_file($cache_name, null, $expiration);
        if ( ! is_null($data)) {
            // Se obtiver dados do cache em arquivo, armazene no Cache de Objeto
            if (function_exists('wp_cache_set')) {
                wp_cache_set($cache_name, $data, 'pxa', $expiration);
            }

            return $data;
        }

        // Se nenhum cache foi encontrado, gere os dados com o callback
        $data = $callback();

        // Armazene os dados no Cache de Objeto
        if (function_exists('wp_cache_set')) {
            wp_cache_set($cache_name, $data, 'pxa', $expiration);
        }

        // Salve no cache em arquivo
        pxa_cache_remember_file($cache_name, $data, $expiration);

        return $data;
    }
}

if ( ! function_exists('pxa_cache_remember_file')) {
    function pxa_cache_remember_file($key, $data = null, $expiration = 300)
    {
        $cache_path = WP_CONTENT_DIR . '/cache/';

        if ( ! file_exists($cache_path)) {
            mkdir($cache_path, 0755, true);
        }

        $cache_file = $cache_path . $key . '.cache';

        // Se os dados são nulos, apenas tente obter do cache em arquivo
        if (is_null($data)) {
            if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $expiration) {
                return unserialize(file_get_contents($cache_file));
            }
        } else {
            // Se temos dados para armazenar, salve no cache em arquivo
            file_put_contents($cache_file, serialize($data));

            return $data;
        }

        return $data;
    }
}

if ( ! function_exists('pxa_cache_flush')) {
    function pxa_cache_flush()
    {
        global $wpdb;

        // Limpar transients
        $transients = $wpdb->get_col("SELECT option_name FROM $wpdb->options WHERE option_name LIKE '_transient_pxa_cache_%' OR option_name LIKE '_transient_timeout_pxa_cache_%'");

        foreach ($transients as $transient) {
            delete_transient(str_replace('_transient_', '', $transient));
        }

        // Limpar caches
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'pxa_cache_%'");

        // Limpar caches de object cache, se estiver em uso
        wp_cache_flush();

        // Limpa cache de arquivos
        $cache_path = WP_CONTENT_DIR . '/cache/';

        if (file_exists($cache_path)) {
            // Abrir diretório de cache
            $dir = opendir($cache_path);
            if ($dir) {
                // Iterar pelos arquivos
                while (($file = readdir($dir)) !== false) {
                    if ($file === '.' || $file === '..') {
                        continue;
                    }

                    // Verificar se o arquivo corresponde ao padrão
                    if (str_contains($file, PXA_key('cache'))) {
                        // Deletar arquivo
                        unlink($cache_path . $file);
                    }
                }

                closedir($dir);
            }
        }
    }
}

/*
 * Edit - Menu Lateral
 * Ocultar Visibilidade e Data
 */
add_action('admin_head', function () {
    global $post;

    if (in_array($post?->post_type, [
        PXA_key('conversion'),
        PXA_key('integration'),
    ])) {
        echo '<style>
            .misc-pub-visibility, .misc-pub-curtime { display: none; }
        </style>';
    }

    if (in_array($post?->post_type, [
        PXA_key('event'),
    ])) {
        echo '<style>
            #misc-publishing-actions, #minor-publishing-actions {
                display: none;
            }
        </style>';
    }
});

if ( ! function_exists('pxa_input_html')) {
    function pxa_input_html($value, $clipboard = null, $icon = true)
    {
        if ($clipboard) {
            $clipboard = 'data-clipboard="' . $value . '"';
        }

        $clipboard .= " data-clipboard-icon='{$icon}'";

        return "<div {$clipboard}><input type='text' class='cf-text__input' readonly value='{$value}'></div>";
    }
}

if ( ! function_exists('wp_date_utc')) {
    //     function wp_date_utc($timestamp, $format = 'Y-m-d H:i:s')
    //     {
    //         if (is_string($timestamp)) {
    //             $timestamp = strtotime($timestamp);
    //         }
    //
    //         return wp_date($format, $timestamp, new DateTimeZone('UTC'));
    //     }

    function wp_date_utc($date, $format = 'Y-m-d H:i:s')
    {
        // Verifica se a data é um timestamp
        if (is_numeric($date)) {
            // Cria um objeto DateTime a partir do timestamp (já em UTC)
            $datetime = new DateTime('@' . $date);
        } else {
            // Obtém o fuso horário configurado no WordPress
            $timezone_string = get_option('timezone_string');
            // Cria um objeto DateTime a partir da string e define o timezone
            $datetime = new DateTime($date, new DateTimeZone($timezone_string));
            // Converte para UTC
            $datetime->setTimezone(new DateTimeZone('UTC'));
        }

        // Formata a data de acordo com o formato especificado
        return $datetime->format($format);
    }
}

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;

if ( ! function_exists('phone_nacional')) {
    function phone_nacional($phoneNumber)
    {
        $phoneNumber = str_start($phoneNumber, '+');
        $phoneUtil   = PhoneNumberUtil::getInstance();

        try {
            $numberProto    = $phoneUtil->parse($phoneNumber);
            $countryCode    = $numberProto->getCountryCode();
            $nationalNumber = $numberProto->getNationalNumber();

            return $nationalNumber;
        } catch (NumberParseException $e) {
            return $phoneNumber;
        }
    }
}
