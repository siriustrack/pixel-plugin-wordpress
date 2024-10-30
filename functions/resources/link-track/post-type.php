<?php

add_action('init', function () {
    $labels = [
        'name'               => _x('Link Rastreado', 'Post Type General Name', 'pixel-x-app'),
        'singular_name'      => _x('Link Rastreado', 'Post Type Singular Name', 'pixel-x-app'),
        'menu_name'          => __('Links Rastreados', 'pixel-x-app'),
        'name_admin_bar'     => __('Link Rastreado', 'pixel-x-app'),
        'all_items'          => __('Links Rastreados', 'pixel-x-app'),
        'add_new_item'       => __('Novo Link Rastreado', 'pixel-x-app'),
        'add_new'            => __('Novo Link Rastreado', 'pixel-x-app'),
        'new_item'           => __('Novo Link Rastreado', 'pixel-x-app'),
        'edit_item'          => __('Editar Link Rastreado', 'pixel-x-app'),
        'update_item'        => __('Atualizar Link Rastreado', 'pixel-x-app'),
        'view_item'          => __('Ver Link Rastreado', 'pixel-x-app'),
        'view_items'         => __('Ver Links', 'pixel-x-app'),
        'search_items'       => __('Buscar Link Rastreado', 'pixel-x-app'),
        'not_found'          => __('Não encontrado', 'pixel-x-app'),
        'not_found_in_trash' => __('Não encontrado na lixeira', 'pixel-x-app'),
    ];
    $args = [
        'label'    => __('Link Rastreado', 'pixel-x-app'),
        'labels'   => $labels,
        'supports' => ['title', 'slug'],
        'rewrite'  => [
            'slug'       => 'lt',
            'with_front' => true,
            // 'pages'      => false,
            // 'feeds'      => false,
        ],
        'map_meta_cap'        => true,
        'capability_type'     => 'page',
        'show_in_menu'        => PXA_key('dashboard'),
        'menu_position'       => 10,
        'public'              => true,
        'show_ui'             => true,
        'query_var'           => true,
        'show_in_admin_bar'   => false,
        'publicly_queryable'  => true,
        'has_archive'         => false,
        'can_export'          => true,
        'exclude_from_search' => true,
        'hierarchical'        => false,
        'show_in_rest'        => false,
    ];

    register_post_type(PXA_key('link_tracked'), $args);

    flush_rewrite_rules();
}, 0);

/**
 * Settings
 **/
add_filter('single_template', 'pxa_link_tracked_template');
add_filter('template_include', 'pxa_link_tracked_template');

function pxa_link_tracked_template($original)
{
    global $post, $wp;

    if (isset($post) && $post->post_type == PXA_key('link_tracked')) {
        return dirname(__FILE__) . '/template.php';
    }

    return $original;
}

add_filter('request', function ($query) {
    // Verifica se é a página do post type 'link_tracked'
    if (is_singular('link_tracked')) {
        // Verifica se o parâmetro 'name' existe
        if (isset($_GET['name'])) {
            // Remove o parâmetro 'name' da query
            unset($_GET['name']);
            // Adiciona o parâmetro 'lead_name' com o mesmo valor
            $_GET['lead_name'] = $_REQUEST['name'];
        }
    }

    return $query;
});

// add_filter('query_vars', function ($query_vars) {
//     if (isset($query_vars['name'])) {
//         $query_vars['lead_name'] = $query_vars['name'];
//         unset($query_vars['name']);
//     }
//
//     return $query_vars;
// });

/**
 * Rows
 **/
add_filter('post_row_actions', function ($old_actions, $post) {
    global $current_screen;

    if ($post->post_type == PXA_key('link_tracked')) {
        $actions['edit'] = data_get($old_actions, 'edit');
        $actions['view'] = data_get($old_actions, 'view');

        if ($post->post_status != 'trash') {
            /**
             * Duplicate
             **/
            $actions['duplicate'] = sprintf(
                '<a href="%s">%s</a>',
                esc_url(wp_nonce_url(admin_url('admin-post.php?action=duplicate_link_tracked&id=' . $post->ID), 'duplicate_link_tracked')),
                __('Duplicar', 'pixel-x-app')
            );

            /**
             * Reset
             **/
            $actions['reset'] = sprintf(
                '<a href="%s">%s</a>',
                esc_url(wp_nonce_url(admin_url('admin-post.php?action=link_tracked_reset&id=' . $post->ID), 'link_tracked_reset')),
                __('Zerar Acessos', 'pixel-x-app')
            );
        }

        $actions['trash']   = data_get($old_actions, 'trash');
        $actions['untrash'] = data_get($old_actions, 'untrash');
        $actions['delete']  = data_get($old_actions, 'delete');
    }

    return isset($actions) ? removeNullValues($actions) : $old_actions;
}, 10, 2);

add_filter('post_submitbox_misc_actions', function ($post) {
    global $post_type;

    if ($post_type == PXA_key('link_tracked')) {
        echo '<div class="misc-pub-section">';
        echo PXA_component_button([
            'title' => __('Duplicar', 'pixel-x-app'),
            'link'  => esc_url(wp_nonce_url(admin_url('admin-post.php?action=duplicate_link_tracked&id=' . $post->ID), 'duplicate_link_tracked')),
            'class' => 'button-secondary',
        ]);
        echo '</div>';
    }

    return $post;
});

/**
 * Columns
 **/
function PXA_link_tracked_columns_register($columns)
{
    unset($columns['parent']);
    unset($columns['comments']);
    unset($columns['date']);

    $columns['link']         = __('Link Rastreado', 'pixel-x-app');
    $columns['event']        = __('Evento', 'pixel-x-app');
    $columns['stats_access'] = __('Acessos', 'pixel-x-app');
    $columns['last_access']  = __('Último Acesso', 'pixel-x-app');
    // $columns['target']       = __('Link de Destino', 'pixel-x-app');

    return $columns;
}

add_filter('manage_edit-' . PXA_PREFIX . 'link_tracked_columns', 'PXA_link_tracked_columns_register');
add_filter('manage_edit-' . PXA_PREFIX . 'link_tracked_sortable_columns', 'PXA_link_tracked_columns_register');

add_action('manage_' . PXA_PREFIX . 'link_tracked_posts_custom_column', function ($column_name, $model_id) {
    $model = PXA_get_model($model_id);

    if ($event = data_get($model, PXA_key('event'))) {
        $event_label = EventEnum::from($event)->label();
    }

    switch ($column_name) {
        case 'link':
            $link = get_permalink($model_id);
            echo pxa_input_html($link, true);
            echo '<center>' . __('Use esse link para rastrear os acessos.', 'pixel-x-app') . '</center>';

            break;
        case 'target':
            $target = data_get($model, PXA_key('target'));
            echo pxa_input_html($target);

            break;
        case 'event':
            echo $event_label;

            break;
        case 'stats_access':
            echo data_get($model, PXA_key('stats_access'));

            break;
        case 'last_access':
            echo data_get($model, PXA_key('last_access'));
            echo '<br>';
            $lead = esc_url(admin_url('edit.php?s=' . data_get($model, PXA_key('last_lead')) . '&post_status=all&post_type=pxa_lead'));
            echo "<a href='{$lead}' target='_blank'>" . data_get($model, PXA_key('last_lead')) . '</a>';

            break;
    };
}, 10, 2);

/**
 * Order
 **/
add_action('pre_get_posts', function ($query) {
    global $pagenow;

    if (
        $query->is_admin
        && $query->get('post_type') == PXA_key('link_tracked')
        && ! isset($_GET['orderby'])
    ) {
        $query->set('orderby', 'title');
        $query->set('order', 'ASC');
    }

    return $query;
});

/**
 * Duplicate
 **/
add_action('admin_init', function () {
    if (data_get($_GET, 'action') === 'duplicate_link_tracked' && isset($_GET['id'])) {
        $model_id = absint($_GET['id']);
        check_admin_referer('duplicate_link_tracked');

        $model = get_post($model_id);

        if ( ! empty($model)) {
            $duplicate = [
                'post_title'  => __('Cópia de: ', 'pixel-x-app') . $model->post_title,
                'post_status' => 'pending',
                'post_type'   => $model->post_type,
            ];

            $new_model = wp_insert_post($duplicate);

            if ( ! is_wp_error($new_model)) {
                $metadata = get_post_meta($model_id);
                foreach ($metadata as $key => $value) {
                    update_post_meta($new_model, $key, $value[0]);
                }

                carbon_set_post_meta($new_model, PXA_key('stats_access'), 0);
                carbon_set_post_meta($new_model, PXA_key('last_lead'), '');
                carbon_set_post_meta($new_model, PXA_key('last_access'), '');

                wp_redirect(admin_url('post.php?action=edit&post=' . $new_model));
                exit();
            }
        }
    }

    if (data_get($_GET, 'action') === 'link_tracked_reset' && isset($_GET['id'])) {
        $model_id = absint($_GET['id']);
        carbon_set_post_meta($model_id, PXA_key('stats_access'), 0);
        carbon_set_post_meta($model_id, PXA_key('last_lead'), '');
        carbon_set_post_meta($model_id, PXA_key('last_access'), '');

        wp_redirect(admin_url('edit.php?post_type=pxa_link_tracked'));
        exit();
    }
});
//
// /**
//  * Export
//  **/
// add_action('current_screen', function () {
//     global $current_screen;
//
//     if ('edit-' . PXA_key('conversion') === $current_screen->id && data_get($_GET, 'post_status') != 'trash') {
//         add_action('restrict_manage_posts', function () {
//             echo PXA_component_button([
//                 'title'    => __('Exportar Links', 'pixel-x-app'),
//                 'link'     => admin_url('admin.php?pxa_command=' . PXA_PREFIX . 'export_conversions'),
//                 'class'    => 'button-secondary',
//                 'style'    => 'float: right;',
//                 'target'   => '_blank',
//                 'download' => 'conversions.json',
//                 'icon'     => 'download',
//             ]);
//         });
//     }
// });
