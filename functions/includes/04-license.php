<?php

use Carbon_Fields\Container;
use Carbon_Fields\Field;

add_action('carbon_fields_register_fields', function () {
    Container::make('theme_options', __('Licença', 'pixel-x-app'))
        ->set_page_parent(PXA_key('dashboard'))
        ->set_page_file(PXA_key('license'))
        ->set_page_menu_position(7)
        ->where('current_user_capability', '=', 'manage_options')
        ->add_fields([
            Field::make('text', PXA_key('email'), __('Email', 'pixel-x-app'))
                ->set_help_text(__('Insira seu email de compra.', 'pixel-x-app'))
                ->set_required(true)
                ->set_width(45)
                ->set_attribute('type', 'email'),

            Field::make('text', PXA_key('license'), __('Licença', 'pixel-x-app'))
                ->set_help_text(__('Insira sua licença recebida por email, após a compra.', 'pixel-x-app'))
                ->set_required(true)
                ->set_width(45)
                ->set_attribute('type', 'password'),

            Field::make('text', PXA_key('status'), __('Status', 'pixel-x-app'))
                ->set_width(10)
                ->set_attribute('readOnly', 'true'),
        ]);
});

function AMManager()
{
    return new AMManager(
        PXA_FILE,
        PXA_key('license'),
        PXA_get_setting('license'),
        PXA_get_setting('email'),
        PXA_get_setting('status'),
    );
}

function PXA_check_license()
{
    pxa_cache_flush();

    if (PXA_license_status()) {
        carbon_set_theme_option(PXA_key('status'), __('Ativo', 'pixel-x-app'));
    } else {
        carbon_set_theme_option(PXA_key('status'), __('Inválido', 'pixel-x-app'));
    }
}

if ( ! function_exists('PXA_license_status')) {
    function PXA_license_status()
    {
        return pxa_cache_remember(
            'license_status',
            fn () => AMManager()->license_check() == 'active',
            DAY_IN_SECONDS
        );
    }
}

if ( ! function_exists('PXA_check_update')) {
    function PXA_check_update()
    {
        AMManager()->check_for_update();
    }
}

add_action('carbon_fields_theme_options_container_saved', function ($post_id, $container_id) {
    if ($container_id->get_page_file() == PXA_key('license')) {
        PXA_check_license();
    }
}, 10, 3);

add_action('admin_init', function () {
    if ( ! data_get($_GET, 'pxa_action')) {
        return;
    }

    if (data_get($_GET, 'pxa_action') == PXA_key('check_update')) {
        // Execute a verificação manual
        PXA_check_update();

        wp_redirect(admin_url('plugins.php'));
        exit;
    }
});
