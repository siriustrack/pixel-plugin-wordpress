<?php

add_action('init', function () {
    $labels = [
        'name'               => _x('Conversão', 'Post Type General Name', 'pixel-x-app'),
        'singular_name'      => _x('Conversão', 'Post Type Singular Name', 'pixel-x-app'),
        'menu_name'          => __('Conversões', 'pixel-x-app'),
        'name_admin_bar'     => __('Conversão', 'pixel-x-app'),
        'all_items'          => __('Conversões', 'pixel-x-app'),
        'add_new_item'       => __('Nova Conversão', 'pixel-x-app'),
        'add_new'            => __('Nova Conversão', 'pixel-x-app'),
        'new_item'           => __('Nova Conversão', 'pixel-x-app'),
        'edit_item'          => __('Editar Conversão', 'pixel-x-app'),
        'update_item'        => __('Atualizar Conversão', 'pixel-x-app'),
        'view_item'          => __('Ver Conversão', 'pixel-x-app'),
        'view_items'         => __('Ver Conversões', 'pixel-x-app'),
        'search_items'       => __('Buscar Conversão', 'pixel-x-app'),
        'not_found'          => __('Não encontrado', 'pixel-x-app'),
        'not_found_in_trash' => __('Não encontrado na lixeira', 'pixel-x-app'),
    ];
    $args = [
        'label' => __('Conversão', 'pixel-x-app'),
        // 'description' => __('Você pode criar um Conversão único que irá redirecionar para os Conversões dos grupos do WhatsApp, Telegram ou outros.', 'pixel-x-app'),
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
        'menu_position'       => 4,
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
    register_post_type(PXA_key('conversion'), $args);
    flush_rewrite_rules();
}, 0);

/**
 * Rows
 **/
add_filter('post_row_actions', function ($old_actions, $post) {
    global $current_screen;

    if ($post->post_type == PXA_key('conversion')) {
        $actions['edit'] = data_get($old_actions, 'edit');

        if ($post->post_status != 'trash') {
            /**
             * Duplicate
             **/
            $actions['duplicate'] = sprintf(
                '<a href="%s">%s</a>',
                esc_url(wp_nonce_url(admin_url('admin-post.php?action=duplicate_conversion&id=' . $post->ID), 'duplicate_conversion')),
                __('Duplicar', 'pixel-x-app')
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

    if ($post_type == PXA_key('conversion')) {
        echo '<div class="misc-pub-section">';
        echo PXA_component_button([
            'title' => __('Duplicar', 'pixel-x-app'),
            'link'  => esc_url(wp_nonce_url(admin_url('admin-post.php?action=duplicate_conversion&id=' . $post->ID), 'duplicate_conversion')),
            'class' => 'button-secondary',
        ]);
        echo '</div>';
    }

    return $post;
});

/**
 * Columns
 **/
function PXA_conversion_columns_register($columns)
{
    unset($columns['date']);

    $columns['title']        = __('Conversão', 'pixel-x-app');
    $columns['display_on']   = __('Carregar em', 'pixel-x-app');
    $columns['trigger']      = __('Gatilho', 'pixel-x-app');
    $columns['trigger_data'] = __('Recurso', 'pixel-x-app');
    $columns['event']        = __('Evento', 'pixel-x-app');

    return $columns;
}

add_filter('manage_edit-' . PXA_PREFIX . 'conversion_columns', 'PXA_conversion_columns_register');
add_filter('manage_edit-' . PXA_PREFIX . 'conversion_sortable_columns', 'PXA_conversion_columns_register');

add_action('manage_' . PXA_PREFIX . 'conversion_posts_custom_column', function ($column_name, $model_id) {
    $model = PXA_get_model($model_id);

    if ($conversion = data_get($model, PXA_key('display_on'))) {
        $conversion_label = ConversionDisplayEnum::from($conversion)->label();
    }

    if ($trigger = data_get($model, PXA_key('trigger'))) {
        $trigger_label = TriggerEnum::from($trigger)->label();
    }

    if ($event = data_get($model, PXA_key('event'))) {
        $event_label = EventEnum::from($event)->label();
    }

    switch ($column_name) {
        case 'display_on':
            echo $conversion_label;

            break;
        case 'trigger':
            echo $trigger_label;

            break;
        case 'event':
            echo $event_label;

            break;
        case 'trigger_data':
            if (in_array(
                $trigger,
                [TriggerEnum::PAGE_TIME, TriggerEnum::VIDEO_TIME]
            )) {
                echo sprintf(
                    __('Esperar por %s segundos', 'pixel-x-app'),
                    data_get($model, PXA_key('time'))
                );
            } elseif (in_array(
                $trigger,
                [TriggerEnum::FORM_SUBMIT, TriggerEnum::CLICK, TriggerEnum::MOUSE_OVER, TriggerEnum::VIEW_ELEMENT]
            )) {
                $class = data_get($model, PXA_key('class'));
                echo __('Classe CSS: ', 'pixel-x-app');
                echo sprintf('<code data-clipboard="%s">.%s</code>', $class, $class);
            } elseif ($trigger == TriggerEnum::SCROLL) {
                echo sprintf(
                    __('Rolar %s%% da Página', 'pixel-x-app'),
                    data_get($model, PXA_key('scroll'))
                );
            }

            break;
    };
}, 10, 2);

/**
 * Filter
 **/
add_action('restrict_manage_posts', function () {
    if (data_get($_GET, 'post_type') != PXA_key('conversion')) {
        return;
    }

    $meta_key    = PXA__key('trigger');
    $meta_values = TriggerEnum::toSelect();

    PXA_filter_select(__('Gatilho', 'pixel-x-app'), $meta_key, $meta_values);

    $meta_key    = PXA__key('event');
    $meta_values = EventEnum::toSelect();

    PXA_filter_select(__('Evento', 'pixel-x-app'), $meta_key, $meta_values);
});

// Adiciona o filtro ao hook parse_query
add_filter('parse_query', function ($query) {
    if (data_get($_GET, 'post_type') != PXA_key('conversion')) {
        return;
    }

    global $pagenow;

    $meta_keys  = [PXA__key('trigger'), PXA__key('event')];
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

/**
 * Order
 **/
add_action('pre_get_posts', function ($query) {
    global $pagenow;

    if (
        $query->is_admin
        && $query->get('post_type') == PXA_key('conversion')
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
add_action('admin_post_duplicate_conversion', function () {
    if (data_get($_GET, 'action') === 'duplicate_conversion' && isset($_GET['id'])) {
        $model_id = absint($_GET['id']);
        check_admin_referer('duplicate_conversion');

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
                    if (str_contains($key, 'class')) {
                        update_post_meta($new_model, $key, str_random(20));
                    } else {
                        update_post_meta($new_model, $key, $value[0]);
                    }
                }

                wp_redirect(admin_url('post.php?action=edit&post=' . $new_model));
                exit();
            }
        }
    }
});

/**
 * Export
 **/
add_action('current_screen', function () {
    global $current_screen;

    if ('edit-' . PXA_key('conversion') === $current_screen->id && data_get($_GET, 'post_status') != 'trash') {
        add_action('restrict_manage_posts', function () {
            echo PXA_component_button([
                'title'    => __('Exportar Conversões', 'pixel-x-app'),
                'link'     => admin_url('admin.php?pxa_command=' . PXA_PREFIX . 'export_conversions'),
                'class'    => 'button-secondary',
                'style'    => 'float: right;',
                'target'   => '_blank',
                'download' => 'conversions.json',
                'icon'     => 'download',
            ]);
        });
    }
});
