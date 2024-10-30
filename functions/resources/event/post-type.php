<?php

add_action('init', function () {
    $labels = [
        'name'               => _x('Evento', 'Post Type General Name', 'pixel-x-app'),
        'singular_name'      => _x('Evento', 'Post Type Singular Name', 'pixel-x-app'),
        'menu_name'          => __('Eventos', 'pixel-x-app'),
        'name_admin_bar'     => __('Evento', 'pixel-x-app'),
        'all_items'          => __('Eventos', 'pixel-x-app'),
        'add_new_item'       => __('Novo Evento', 'pixel-x-app'),
        'add_new'            => __('Novo Evento', 'pixel-x-app'),
        'new_item'           => __('Novo Evento', 'pixel-x-app'),
        'edit_item'          => __('Visualização Evento', 'pixel-x-app'),
        'update_item'        => __('Atualizar Evento', 'pixel-x-app'),
        'view_item'          => __('Ver Evento', 'pixel-x-app'),
        'view_items'         => __('Ver Eventos', 'pixel-x-app'),
        'search_items'       => __('Buscar Evento', 'pixel-x-app'),
        'not_found'          => __('Não encontrado', 'pixel-x-app'),
        'not_found_in_trash' => __('Não encontrado na lixeira', 'pixel-x-app'),
    ];
    $args = [
        'label' => __('Evento', 'pixel-x-app'),
        // 'description' => __('Você pode criar um Evento único que irá redirecionar para os Eventos dos grupos do WhatsApp, Telegram ou outros.', 'pixel-x-app'),
        'labels'   => $labels,
        'supports' => false,
        // 'rewrite'  => [
        //     'slug'       => 'lg',
        //     'with_front' => true,
        //     'pages'      => false,
        //     'feeds'      => false,
        // ],
        'capabilities' => [
            'create_posts' => false
        ],
        'map_meta_cap'        => true,
        'capability_type'     => 'page',
        'public'              => false,
        'show_in_menu'        => PXA_key('dashboard'),
        'menu_position'       => 3,
        'show_ui'             => true,
        'show_in_admin_bar'   => false,
        'can_export'          => true,
        'exclude_from_search' => true,
        'hierarchical'        => false,
        'show_in_rest'        => false,
    ];
    register_post_type(PXA_key('event'), $args);
    flush_rewrite_rules();
}, 0);

/**
 * Rows
 **/
add_filter('post_row_actions', function ($old_actions, $post) {
    global $current_screen;

    if ($post->post_type == PXA_key('event')) {
        $actions['edit'] = data_get($old_actions, 'edit');

        if ($post->post_status != 'trash') {
            /**
             * Ver Jornada
             **/
            $actions['edit'] = '<a href="' . esc_url(get_edit_post_link($post->ID)) . '">' . esc_html__('View') . '</a>';
        }

        $actions['trash']   = data_get($old_actions, 'trash');
        $actions['untrash'] = data_get($old_actions, 'untrash');
        $actions['delete']  = data_get($old_actions, 'delete');
    }

    return isset($actions) ? removeNullValues($actions) : $old_actions;
}, 10, 2);

/**
 * Remover Menu Lateral
 **/
add_action('admin_menu', function () {
    remove_meta_box('submitdiv', PXA_key('event'), 'side');
});

add_filter('display_post_states', function ($states, $post) {
    if ($post->post_type === 'pxa_event') {
        $post_status = get_post_status($post->ID);

        if ($post_status === 'draft') {
            return ['Erro'];
        } elseif ($post_status === 'pending') {
            return ['Em Processamento'];
        } elseif ($post_status === 'publish') {
            return ['Enviado'];
        }
    }

    return $states;
}, 10, 2);

/**
 * Columns
 **/
function PXA_event_columns_register($columns)
{
    // $date = $columns['date'];
    unset($columns['date']);
    unset($columns['title']);

    $columns['title']      = __('ID', 'pixel-x-app');
    $columns['event_name'] = __('Evento', 'pixel-x-app');
    $columns['page']       = __('Página', 'pixel-x-app');
    $columns['lead']       = __('Lead', 'pixel-x-app');
    $columns['event_time'] = __('Data do Evento', 'pixel-x-app');

    return $columns;
}

add_filter('manage_edit-' . PXA_PREFIX . 'event_columns', 'PXA_event_columns_register');
add_filter('manage_edit-' . PXA_PREFIX . 'event_sortable_columns', 'PXA_event_columns_register');

add_action('manage_' . PXA_PREFIX . 'event_posts_custom_column', function ($column_name, $model_id) {
    $model = PXA_get_model($model_id);

    switch ($column_name) {
        case 'title':
            echo data_get($model, PXA_key('id'));

            break;
        case 'lead':
            $lead_id = data_get($model, PXA_key('lead_id'));
            echo $lead_id
                ? get_the_title($lead_id)
                : __('Lead não identificado', 'pixel-x-app');

            break;
        case 'event_name':
            echo data_get($model, PXA_key('event_name'));

            if ($content_name = data_get($model, PXA_key('content_name'))) {
                echo ' - ' . $content_name;
            }

            break;
        case 'page':
            // $page_id = data_get($model, PXA_key('page_id'));
            // echo $page_id ? get_the_title($page_id) : data_get($model, PXA_key('traffic_source')) ?? __('Página não identificada', 'pixel-x-app');
            echo data_get($model, PXA_key('page_title'))
                ?? data_get($model, PXA_key('traffic_source'))
                ?? __('Página não identificada', 'pixel-x-app');

            break;
        case 'event_time':
            echo wp_date(
                get_option('date_format') . ' ' . get_option('time_format'),
                data_get($model, PXA_key('event_time'))
            );

            break;
    };
}, 10, 2);

/**
 * Search
 **/
add_filter('pre_get_posts', function ($query) {
    if ( ! is_admin() || data_get($query->query, 'post_type') != PXA_key('event')) {
        return;
    }

    $custom_fields = [
        PXA__key('lead_id'),
        PXA__key('event_name'),
    ];

    $searchterm = $query->query_vars['s'];

    $query->query_vars['s'] = '';

    if ($searchterm != '') {
        $meta_query = ['relation' => 'OR'];

        $lead = PXA_get_model($searchterm, 'lead');

        foreach ($custom_fields as $field) {
            array_push($meta_query, [
                'key'     => $field,
                'value'   => data_get($lead, 'ID', $searchterm),
                'compare' => 'LIKE',
            ]);
        }

        $query->set('meta_query', $meta_query);
    }
});

/**
 * Export
 **/
add_action('current_screen', function () {
    global $current_screen;

    if ('edit-' . PXA_key('event') === $current_screen->id && data_get($_GET, 'post_status') != 'trash') {
        add_action('restrict_manage_posts', function () {
            echo PXA_component_button([
                'title'    => __('Exportar em CSV', 'pixel-x-app'),
                'link'     => admin_url('admin.php?pxa_command=' . PXA_PREFIX . 'export_events'),
                'class'    => 'button-secondary',
                'style'    => 'float: right;',
                'target'   => '_blank',
                'download' => 'events.csv',
                'icon'     => 'download',
            ]);
        });
    }
});
