<?php

/**
 * Plugin Activation
 */
register_activation_hook(PXA_FILE, function () {
    // Check License
    // PXA_check_license();

    // Limpa Regras de Links Permanentes
    flush_rewrite_rules();
    flush_rewrite_rules(true);

    // Atualizar Pixels
    PXA_update_pixels();
});

/**
 * Plugin Activated
 */
add_action('activated_plugin', function ($plugin) {
    if ($plugin == PXA_SLUG) {
        // Redirect After Active
        if (PXA_license_status()) {
            exit(wp_redirect(PXA_PAGE_DASHBOARD));
        } else {
            exit(wp_redirect(PXA_PAGE_LICENSE));
        }
    }
});

/**
 * Plugin Update
 */
add_action('upgrader_process_complete', function ($upgrader_object, $options) {
    if ($options['action'] == 'update' && $options['type'] == 'plugin' && isset($options['plugins'])) {
        foreach ($options['plugins'] as $plugin) {
            if ($plugin == PXA_SLUG) {
                // Atualizar Pixels
                PXA_update_pixels();

                // Remover Agendamentos
                PXA_queues_cleaner(true);

                // Limpa Regras de Links Permanentes
                flush_rewrite_rules();
                flush_rewrite_rules(true);

                // Check License
                PXA_check_license();
            }
        }
    }
}, 10, 2);

/**
 * Plugin Deactivation
 */
register_deactivation_hook(PXA_FILE, function () {
    // Remove License
    AMManager()->license_remove();

    // Remove Post Type
    unregister_post_type(PXA_PREFIX . 'lead');
    unregister_post_type(PXA_PREFIX . 'event');
    unregister_post_type(PXA_PREFIX . 'conversion');

    // Remover Agendamentos
    PXA_queues_cleaner(true);

    // Limpa Regras de Links Permanentes
    flush_rewrite_rules();
    flush_rewrite_rules(true);
});

/**
 * Plugin Activa Carbon Fields
 */
add_action('after_setup_theme', function () {
    \Carbon_Fields\Carbon_Fields::boot();
});

/*
 * Add Link Plugins List
 */
add_filter('plugin_action_links_' . PXA_SLUG, function ($links) {
    if (PXA_license_status()) {
        $settings = "<a href='" . PXA_PAGE_SETTINGS . "'>" . __('Configurações', 'pixel-x-app') . '</a>';
    } else {
        $settings = "<a href='" . PXA_PAGE_LICENSE . "'>" . __('Ativar Licença', 'pixel-x-app') . '</a>';
    }

    $tutorial = "<a href='" . PXA_PAGE_DOCUMENTATION . "'>" . __('Documentação', 'pixel-x-app') . '</a>';
    $update   = "<a href='" . PXA_PAGE_CHECK_UPDATE . "'>" . __('Verificar Atualização', 'pixel-x-app') . '</a>';

    array_push(
        $links,
        $settings,
        $tutorial,
        $update,
    );

    return $links;
});

/**
 * Add Body Class in Plugin Pages
 **/
add_filter('admin_body_class', function () {
    if (PXA_pages()) {
        return PXA_DOMAIN . '-body';
    }
});

/**
 * Clear Cache
 **/
function pxa_clear_cache_on_post_save($post_id)
{
    // Certifique-se de que não estamos em uma solicitação de AJAX e que o usuário atual tem permissões para editar o post
    if (
        ! defined('DOING_AJAX')
        && current_user_can('edit_post', $post_id)
        // && data_get($_GET, 'action') != 'trash'
    ) {
        // Limpar o cache
        pxa_cache_flush();
    }
}

// Adicione o gancho para adição
// add_action('wp_insert_post', 'pxa_clear_cache_on_post_save');

// Adicione o gancho para salvar
add_action('save_post', 'pxa_clear_cache_on_post_save');

// Adicione o gancho para publicar
add_action('publish_post', 'pxa_clear_cache_on_post_save');
