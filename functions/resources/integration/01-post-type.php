<?php

add_action('init', function () {
    $labels = [
        'name'               => _x('Integração', 'Post Type General Name', 'pixel-x-app'),
        'singular_name'      => _x('Integração', 'Post Type Singular Name', 'pixel-x-app'),
        'menu_name'          => __('Integrações', 'pixel-x-app'),
        'name_admin_bar'     => __('Integração', 'pixel-x-app'),
        'all_items'          => __('Integrações', 'pixel-x-app'),
        'add_new_item'       => __('Nova Integração', 'pixel-x-app'),
        'add_new'            => __('Nova Integração', 'pixel-x-app'),
        'new_item'           => __('Nova Integração', 'pixel-x-app'),
        'edit_item'          => __('Editar Integração', 'pixel-x-app'),
        'update_item'        => __('Atualizar Integração', 'pixel-x-app'),
        'view_item'          => __('Ver Integração', 'pixel-x-app'),
        'view_items'         => __('Ver Integrações', 'pixel-x-app'),
        'search_items'       => __('Buscar Integração', 'pixel-x-app'),
        'not_found'          => __('Não encontrado', 'pixel-x-app'),
        'not_found_in_trash' => __('Não encontrado na lixeira', 'pixel-x-app'),
    ];
    $args = [
        'label' => __('Integração', 'pixel-x-app'),
        // 'description' => __('Você pode criar um Integração único que irá redirecionar para os Integrações dos grupos do WhatsApp, Telegram ou outros.', 'pixel-x-app'),
        'labels'   => $labels,
        'supports' => ['title'],
        // 'rewrite'  => [
        //     'slug'       => 'lg',
        //     'with_front' => true,
        //     'pages'      => false,
        //     'feeds'      => false,
        // ],
        // 'capabilities' => [
        //     'create_posts' => false
        // ],
        'map_meta_cap'        => true,
        'capability_type'     => 'page',
        'show_in_menu'        => PXA_key('dashboard'),
        'menu_position'       => 10,
        'public'              => false,
        'show_ui'             => true,
        'show_in_admin_bar'   => false,
        'publicly_queryable'  => false,
        'has_archive'         => false,
        'can_export'          => true,
        'exclude_from_search' => true,
        'hierarchical'        => false,
        'show_in_rest'        => false,
    ];
    register_post_type(PXA_key('integration'), $args);
    flush_rewrite_rules();
}, 0);

/**
 * Rows
 **/
add_filter('post_row_actions', function ($old_actions, $post) {
    global $current_screen;

    if ($post->post_type == PXA_key('integration')) {
        $actions['edit']    = data_get($old_actions, 'edit');
        $actions['trash']   = data_get($old_actions, 'trash');
        $actions['untrash'] = data_get($old_actions, 'untrash');
        $actions['delete']  = data_get($old_actions, 'delete');
    }

    return isset($actions) ? removeNullValues($actions) : $old_actions;
}, 10, 2);

/**
 * Columns
 **/
function PXA_integration_columns_register($columns)
{
    unset($columns['date']);

    $columns['title'] = __('Integração', 'pixel-x-app');
    $columns['type']  = __('Tipo', 'pixel-x-app');
    $columns['url']   = __('URL', 'pixel-x-app');

    return $columns;
}
add_filter('manage_edit-' . PXA_PREFIX . 'integration_columns', 'PXA_integration_columns_register');
add_filter('manage_edit-' . PXA_PREFIX . 'integration_sortable_columns', 'PXA_integration_columns_register');

add_action('manage_' . PXA_PREFIX . 'integration_posts_custom_column', function ($column_name, $model_id) {
    $model = PXA_get_model($model_id);

    if ($type = data_get($model, PXA_key('type'))) {
        $type_name = IntegrationTypeEnum::from($type)->label();
    }

    switch ($column_name) {
        case 'type':
            echo $type_name ?? __('Indefinido', 'pixel-x-app');

            break;
        case 'url':
            if ($id = data_get($model, PXA_key('id'))) {
                $url = get_rest_url(null, PXA_DOMAIN . '/v1/integration') . '?pid=' . $id;
                echo pxa_input_html($url, true);
            } else {
                echo __('Indefinido', 'pixel-x-app');
            }

            break;
    };
}, 10, 2);

/**
 * Filter
 **/
add_action('restrict_manage_posts', function () {
    if (data_get($_GET, 'post_type') != PXA_key('integration')) {
        return;
    }

    $meta_key    = PXA__key('type');
    $meta_values = IntegrationTypeEnum::toSelect();

    PXA_filter_select(__('Tipo', 'pixel-x-app'), $meta_key, $meta_values);
});

// Adiciona o filtro ao hook parse_query
add_filter('parse_query', function ($query) {
    if (data_get($_GET, 'post_type') != PXA_key('integration')) {
        return;
    }

    global $pagenow;

    $meta_keys  = [PXA__key('type')];
    $meta_query = ['relation' => 'AND'];

    if (is_admin() && $pagenow == 'edit.php') {
        foreach ($meta_keys as $meta_key) {
            if (data_get($_GET, $meta_key)) {
                $meta_query[] = [
                    'key'     => $meta_key,
                    'value'   => sanitize_text_field($_GET[$meta_key]),
                    'compare' => '=',
                ];
            }
        }

        $query->query_vars['meta_query'] = $meta_query;
    }
});
