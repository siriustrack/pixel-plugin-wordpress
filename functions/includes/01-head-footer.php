<?php

add_action('admin_init', function () {
    wp_enqueue_style('tom-select', PXA_URL . 'assets/libs/tom-select/tom-select.css', [], PXA_VERSION);
    wp_enqueue_script('tom-select', PXA_URL . 'assets/libs/tom-select/tom-select.js', [], PXA_VERSION);
    wp_enqueue_script('chart', PXA_URL . 'assets/libs/chart/chart.js', [], PXA_VERSION);
    wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', [], PXA_VERSION);
    wp_enqueue_style('gridjs', 'https://unpkg.com/tabulator-tables/dist/css/tabulator_bootstrap3.min.css', [], PXA_VERSION);
    wp_enqueue_script('gridjs', 'https://unpkg.com/tabulator-tables/dist/js/tabulator.min.js', [], PXA_VERSION);
    wp_enqueue_script('md-clipboard', PXA_URL . '/assets/libs/clipboard/clipboard.js', [], PXA_VERSION);
    wp_add_inline_script('md-clipboard', 'document.addEventListener("DOMContentLoaded", async () => { 
        ClipboardModule({
            toast: {
                message: "' . __('Conteúdo Copiado com Sucesso!', 'pixel-x-app') . '",
                background: "linear-gradient(45deg, rgba(0,30,79,1) 40%, rgba(0,64,191,1) 90%)",
            }
        });
    }, false);');

    wp_enqueue_style(PXA_DOMAIN, PXA_URL . 'assets/css/style.css', [], PXA_VERSION);
    wp_enqueue_script(PXA_DOMAIN, PXA_URL . 'assets/js/script.js', ['tom-select'], PXA_VERSION);

    if (PXA_pages()) {
        wp_add_inline_script(PXA_DOMAIN, "
            window.usetifulTags = {
                userId: '" . PXA_get_setting('email') . "',
            };
        
            (function(w, d, s) {
                var a = d.getElementsByTagName('head')[0];
                var r = d.createElement('script');
                r.async = 1;
                r.src = s;
                r.setAttribute('id', 'usetifulScript');
                r.dataset.token = '034ac0356602577d23610f3a147b417f';
                a.appendChild(r);
            })(window, document, 'https://www.usetiful.com/dist/usetiful.js');
        
            // Feedback
            !function(){var t=document.createElement('script');t.type='text/javascript',t.async=!0,t.src='https://unpkg.com/@formbricks/js@^1.6.5/dist/index.umd.js';var e=document.getElementsByTagName('script')[0];e.parentNode.insertBefore(t,e),setTimeout(function(){window.formbricks.init({
                environmentId: 'cm1tnnfgg008h13dfrxlk6l3j',
                apiHost: 'https://feedback.mosko.link',
                userId: '" . PXA_get_setting('email') . "',
                attributes: {
                    domain: '" . get_domain() . "',
                },
            })},500)}();");
    }
});

// add_action('admin_head', function () { });

// add_action('admin_footer', function () { });

add_filter('admin_footer_text', function () {
    if (PXA_pages()) {
        return sprintf(__('Todos os Direitos Reservados - %s - Copyright © %s', 'pixel-x-app'), PXA_AUTHOR, gmdate('Y'));
    }
}, 9999);

add_filter('update_footer', function () {
    if (PXA_pages()) {
        return sprintf(__('Versão %s', 'pixel-x-app'), PXA_VERSION);
    }
}, 9999);

/*
 * Commands
 */
add_action('admin_init', function () {
    if (data_get($_GET, PXA_key('command'))) {
        switch (data_get($_GET, PXA_key('command'))) {
            case PXA_key('command_events_send'):
                PXA_send_events_to_server();
                PXA_queues_worker();

                break;
            case PXA_key('command_events_resend'):
                PXA_update_post_status('event', 'draft', 'pending');
                PXA_send_events_to_server();
                PXA_queues_worker();

                break;
            case PXA_key('command_events_delete'):
                PXA_delete_olds('event', -1);

                break;
            case PXA_key('command_events_delete_error'):
                PXA_delete_olds('event', -1, null, 'draft');

                break;
            case PXA_key('command_leads_delete'):
                PXA_delete_olds('lead', -1);

                break;
            case PXA_key('command_leads_anon_delete'):
                PXA_delete_olds_wp('lead', -1);

                break;
            case PXA_key('command_conversions_delete'):
                PXA_delete_olds('conversion', -1);

                break;
            case PXA_key('command_integrations_delete'):
                PXA_delete_olds('integration', -1);

                break;
            case PXA_key('export_leads'):
                PXA_export_leads();

                break;
            case PXA_key('export_events'):
                PXA_export_events();

                break;
            case PXA_key('export_conversions'):
                PXA_export_conversions();

                break;
            case PXA_key('command_queue_cleaner'):
                PXA_queues_cleaner(true);
                PXA_queues_worker();

                break;
            case PXA_key('command_queue_worker'):
                PXA_queues_worker();

                break;
            case PXA_key('command_models_cleaner'):
                PXA_models_cleaner();

                break;
            case PXA_key('command_send_webhook'):
                PXA_send_webhooks(true);

                break;
            case PXA_key('command_delete_all'):
                PXA_delete_olds('lead', -1);
                PXA_delete_olds('event', -1);
                PXA_delete_olds('conversion', -1);
                PXA_delete_olds('integration', -1);

                foreach (array_keys(pxa_options()) as $option) {
                    delete_option($option);
                }

                wp_redirect(PXA_PAGE_LICENSE);

                break;
        }

        pxa_cache_flush();

        wp_redirect(PXA_PAGE_SETTINGS);
        exit;
    }
});

/*
 * Admin Notices
 */
add_action('admin_notices', function () {
    if (
        ! PXA_license_status()
        && data_get($_GET, 'page') != PXA_PREFIX . 'license'
    ) {
        PXA_component_notice([
            'class'       => 'error',
            'title'       => __('Parabéns por Instalar o Pixel X App!', 'pixel-x-app'),
            'description' => __('Parece que você ainda não ativou sua licença do plugin. Pronto para ativar?', 'pixel-x-app'),
            'button'      => [
                'title' => __('Ativar Licença do Pixel X App', 'pixel-x-app'),
                'link'  => PXA_PAGE_LICENSE,
            ]
        ]);
    } elseif (
        ( ! PXA_get_setting('fb_pixels') && ! PXA_get_setting('fb_pixel'))
        && data_get($_GET, 'page') != PXA_PREFIX . 'settings'
    ) {
        PXA_component_notice([
            'class'       => 'error',
            'title'       => __('Parabéns por Ativar o Pixel X App!', 'pixel-x-app'),
            'description' => __('Você ainda não configurou seu Pixel do Facebook. Pronto para configurar?', 'pixel-x-app'),
            'button'      => [
                'title' => __('Configurar meu Pixel', 'pixel-x-app'),
                'link'  => PXA_PAGE_SETTINGS,
            ]
        ]);
    } elseif (data_get($_GET, 'post_type') == PXA_PREFIX . 'conversion' && data_get($_GET, PXA_PREFIX . 'notice') == 'import_conversion') {
        PXA_component_notice([
            'class'       => 'success',
            'close'       => 'true',
            'description' => __('Conversões Importadas com Sucesso!', 'pixel-x-app'),
        ]);
    } elseif (blank(get_option('permalink_structure'))) {
        PXA_component_notice([
            'class'       => 'warning',
            'close'       => 'true',
            'title'       => __('Recomendação Pixel X App!', 'pixel-x-app'),
            'description' => __('Configure os Links Permanentes do seu site como "Nome do post", nas configurações do seu WordPress.', 'pixel-x-app'),
            'button'      => [
                'title' => __('Configurar Links Permanentes', 'pixel-x-app'),
                'link'  => admin_url('options-permalink.php'),
            ]
        ]);
    }

    // Plugins For Disabled
    $active_plugins = get_option('active_plugins');
    if (in_array('pixelyoursite/facebook-pixel-master.php', $active_plugins) || in_array('pixelyoursite-pro/pixelyoursite-pro.php', $active_plugins)) {
        PXA_component_notice([
            'class'       => 'error',
            'close'       => 'true',
            'title'       => sprintf('%s: %s', PXA_NAME, __('Aviso de Incompatibilidade!', 'pixel-x-app')) ,
            'description' => __('Desative o plugin <b>Pixel Your Site</b> para evitar erros de rastreamento, por incompatibilidade entre os plugins.', 'pixel-x-app'),
        ]);
    }
    if (in_array('facebook-conversion-pixel/facebook-conversion-pixel.php', $active_plugins) || in_array('pixel-cat-premium/facebook-conversion-pixel.php', $active_plugins)) {
        PXA_component_notice([
            'class'       => 'error',
            'close'       => 'true',
            'title'       => sprintf('%s: %s', PXA_NAME, __('Aviso de Incompatibilidade!', 'pixel-x-app')) ,
            'description' => __('Desative o plugin <b>Pixel Cat</b> para evitar erros de rastreamento, por incompatibilidade entre os plugins.', 'pixel-x-app'),
        ]);
    }
    if (in_array('pixel-manager-for-woocommerce/pixel-manager-for-woocommerce.php', $active_plugins)) {
        PXA_component_notice([
            'class'       => 'error',
            'close'       => 'true',
            'title'       => sprintf('%s: %s', PXA_NAME, __('Aviso de Incompatibilidade!', 'pixel-x-app')) ,
            'description' => __('Desative o plugin <b>Pixel Tag Manager for WooCommerce </b> para evitar erros de rastreamento, por incompatibilidade entre os plugins.', 'pixel-x-app'),
        ]);
    }
});
