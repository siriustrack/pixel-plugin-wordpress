<?php

add_action('init', function () {
    $labels = [
        'name'               => _x('Lead', 'Post Type General Name', 'pixel-x-app'),
        'singular_name'      => _x('Lead', 'Post Type Singular Name', 'pixel-x-app'),
        'menu_name'          => __('Leads', 'pixel-x-app'),
        'name_admin_bar'     => __('Lead', 'pixel-x-app'),
        'all_items'          => __('Leads', 'pixel-x-app'),
        'add_new_item'       => __('Novo Lead', 'pixel-x-app'),
        'add_new'            => __('Novo Lead', 'pixel-x-app'),
        'new_item'           => __('Novo Lead', 'pixel-x-app'),
        'edit_item'          => __('Editar Lead', 'pixel-x-app'),
        'update_item'        => __('Atualizar Lead', 'pixel-x-app'),
        'view_item'          => __('Ver Lead', 'pixel-x-app'),
        'view_items'         => __('Ver Leads', 'pixel-x-app'),
        'search_items'       => __('Buscar Lead', 'pixel-x-app'),
        'not_found'          => __('Não encontrado', 'pixel-x-app'),
        'not_found_in_trash' => __('Não encontrado na lixeira', 'pixel-x-app'),
    ];
    $args = [
        'label' => __('Lead', 'pixel-x-app'),
        // 'description' => __('Você pode criar um Lead único que irá redirecionar para os Leads dos grupos do WhatsApp, Telegram ou outros.', 'pixel-x-app'),
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
    register_post_type(PXA_key('lead'), $args);
    flush_rewrite_rules();
}, 0);

/**
 * Rows
 **/
add_filter('post_row_actions', function ($old_actions, $post) {
    global $current_screen;

    if ($post->post_type == PXA_key('lead')) {
        $actions['edit'] = data_get($old_actions, 'edit');

        if ($post->post_status != 'trash') {
            /**
             * Ver Jornada
             **/
            $actions['journey'] = sprintf(
                '<a href="%s" target="_blank">%s</a>',
                esc_url(admin_url('edit.php?s=' . $post->post_title . '&post_status=all&post_type=pxa_event&orderby=' . urlencode(__('Data do Evento', 'pixel-x-app')) . '&order=asc')),
                __('Ver Jornada', 'pixel-x-app')
            );
        }

        $actions['trash']   = data_get($old_actions, 'trash');
        $actions['untrash'] = data_get($old_actions, 'untrash');
        $actions['delete']  = data_get($old_actions, 'delete');
    }

    return isset($actions) ? removeNullValues($actions) : $old_actions;
}, 10, 2);

/**
 * Columns
 **/
function pxa_lead_orderby($query)
{
    if ( ! is_admin() || ! $query->is_main_query()) {
        return;
    }

    if ($query->get('post_type') == 'pxa_lead' && filled($query->get('orderby'))) {
        $metadata = PXA__key($query->get('orderby'));
        $query->set('meta_query', [
            'relation' => 'OR',
            [
                'key'     => $metadata,
                'compare' => 'EXISTS',
            ],
            [
                'key'     => $metadata,
                'compare' => 'NOT EXISTS',
            ],
        ]);
        $query->set('orderby', 'meta_value');
    }
}
add_action('pre_get_posts', 'pxa_lead_orderby');

add_filter('manage_edit-' . PXA_PREFIX . 'lead_columns', function ($columns) {
    // $date = $columns['date'];
    unset($columns['date']);

    $columns['title']   = __('ID', 'pixel-x-app');
    $columns['name']    = __('Nome', 'pixel-x-app');
    $columns['email']   = __('Email', 'pixel-x-app');
    $columns['phone']   = __('Telefone', 'pixel-x-app');
    $columns['last_ip'] = __('Último IP', 'pixel-x-app');
    $columns['events']  = __('Eventos', 'pixel-x-app');
    // $columns['date']  = $date;

    return $columns;
});

add_filter('manage_edit-' . PXA_key('lead') . '_sortable_columns', function ($columns) {
    $columns['name']   = 'name';
    $columns['email']  = 'email';
    $columns['phone']  = 'phone';
    $columns['events'] = 'events';

    return $columns;
});

add_action('manage_' . PXA_PREFIX . 'lead_posts_custom_column', function ($colname, $model_id) {
    $model = PXA_get_model($model_id);

    switch ($colname) {
        case 'id':
            echo data_get($model, PXA_key('id'));

            break;
        case 'name':
            echo data_get($model, PXA_key('name'));

            break;
        case 'email':
            echo data_get($model, PXA_key('email'));

            break;
        case 'phone':
            echo data_get($model, PXA_key('phone'));

            break;
        case 'last_ip':
            echo data_get($model, PXA_key('ip'));

            break;
        case 'events':
            // echo PXA_query([
            //     'post_type'      => PXA_key('event'),
            //     'post_status'    => 'any',
            //     'posts_per_page' => -1,
            //     'meta_query'     => [
            //         [
            //             'key'     => PXA__key('lead_id'),
            //             'value'   => $model_id,
            //             'compare' => '='
            //         ]
            //     ],
            // ], false)['total'];

            $events_count = PXA_events_group_by_lead();
            echo count(data_get($events_count, $model_id, []));

            break;
    };
}, 10, 2);

/**
 * Search
 **/
add_filter('pre_get_posts', function ($query) {
    if ( ! is_admin() || data_get($query->query, 'post_type') != PXA_key('lead')) {
        return;
    }

    $custom_fields = [
        PXA__key('id'),
        PXA__key('name'),
        PXA__key('email'),
        PXA__key('phone'),
    ];

    $searchterm = $query->query_vars['s'];

    $query->query_vars['s'] = '';

    if ($searchterm != '') {
        $meta_query = ['relation' => 'OR'];

        foreach ($custom_fields as $field) {
            array_push($meta_query, [
                'key'     => $field,
                'value'   => $searchterm,
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

    if ('edit-' . PXA_key('lead') === $current_screen->id && data_get($_GET, 'post_status') != 'trash') {
        add_action('restrict_manage_posts', function () {
            echo PXA_component_button([
                'title'    => __('Exportar em CSV', 'pixel-x-app'),
                'link'     => admin_url('admin.php?pxa_command=' . PXA_key('export_leads')),
                'class'    => 'button-secondary',
                'style'    => 'float: right;',
                'target'   => '_blank',
                'download' => 'leads.csv',
                'icon'     => 'download',
            ]);
        });
    }
});

/**
 * Delete
 **/
add_action('wp_trash_post', 'PAX_lead_delete', 10, 1);
add_action('before_delete_post', 'PAX_lead_delete');

function PAX_lead_delete($model_id)
{
    // Verifique se o post sendo excluído é do tipo 'pxa_lead'
    $post_type = get_post_type($model_id);

    if ($post_type === PXA_key('lead')) {
        $events_count   = PXA_events_group_by_lead();
        $related_events = data_get($events_count, $model_id, collect());

        // Obtenha todos os eventos com o metadata 'pxa_lead' igual ao ID do post sendo excluído
        // $events         = PXA_get_models('event');
        // $related_events = $events->where(PXA_key('lead_id'), $model_id);
        if ($related_events->count() > 0) {
            // Exclua cada evento relacionado
            foreach ($related_events as $event) {
                wp_delete_post(data_get($event, 'ID'), true);
            }
        }
    }
}
