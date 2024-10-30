<?php

add_action('admin_menu', function () {
    add_menu_page(
        PXA_NAME,
        PXA_NAME,
        'manage_options',
        PXA_PREFIX . 'dashboard',
        'PXA_admin_dashboard', // 05-dashboard
        PXA_get_image('logo-white'), // Icon
        65
    );

    add_submenu_page(
        PXA_PREFIX . 'dashboard', // Parent
        __('Painel de Controle', 'pixel-x-app'),
        __('Painel de Controle', 'pixel-x-app'),
        'manage_options',
        PXA_PREFIX . 'dashboard', // Path
        null, // Function
        0
    );
});

/*
 * Plugin Header
 */
add_action('in_admin_header', function () {
    if (PXA_pages()) {
        $description = (PXA_license_status())
            ? PXA_DESCRIPTION
            : sprintf(__('Sua licença não está ativa, acesse o painel de <a href="%s">Configurações</a> para ativar agora.', 'pixel-x-app'), PXA_PAGE_SETTINGS);

        echo '
<div class="pxa-header-wrap">
    <div class="pxa-header-container">
        <div class="pxa-row pxa-align-center">
            <div class="pxa-col-xs-12 pxa-col-2 pxa-justify-center pxa-flex">
                <img class="pxa-header-logo" src="' . PXA_get_image('logo-horizontal') . '" alt="' . PXA_NAME . '">
            </div>
            <div class="pxa-col-7 pxa-header-subtitle pxa-xs-d-none">
                <h3>' . __('Rastreamento Avançado de Campanhas de Anúncios', 'pixel-x-app') . '</h3>
                <span>' . $description . '</span>
            </div>
            <div class="pxa-col-xs-12 pxa-col-3 pxa-flex-center pxa-justify-around">'
                . PXA_component_button([
                    'title'  => __('Tutoriais', 'pixel-x-app'),
                    'link'   => PXA_PAGE_DOCUMENTATION,
                    'icon'   => 'format-video',
                    'class'  => 'pxa-btn-primary pxa-btn-big',
                    'target' => '_blank',
                ])
                . PXA_component_button([
                    'title'  => __('Suporte', 'pixel-x-app'),
                    'link'   => PXA_PAGE_SUPPORT,
                    'icon'   => 'whatsapp',
                    'class'  => 'pxa-btn-primary pxa-btn-big',
                    'target' => '_blank',
                ])
         . '</div>
        </div>
    </div>
</div>';
    }
}, 100);

add_action('init', function () {
    $action = data_get($_GET, PXA_PREFIX . 'action');

    if ($action == PXA_PREFIX . 'welcome_hidden') {
        update_option(PXA_PREFIX . 'welcome_card_hidden', true, false);
        wp_redirect(PXA_PAGE_DASHBOARD);
        exit();
    }
});
