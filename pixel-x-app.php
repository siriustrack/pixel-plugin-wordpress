<?php
/**
 * Plugin Name:   Pixel X App
 * Plugin URI:    https://pixelx.app/
 * Description:   O melhor sistema de otimização de rastreamento para anunciantes do Facebook Ads e Google Ads.
 * Version:       1.2.6
 * Author:        Mosko Digital
 * Author URI:    https://mosko.digital/
 * Text Domain:   pixel-x-app
 * Domain Path:   /languages/
 */

// Exit if accessed directly.
defined('ABSPATH') || die;

if ( ! function_exists('get_plugin_data')) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

$plugin_data = get_plugin_data(__FILE__);

$constants = [
    'PXA_PREFIX'             => 'pxa_',
    'PXA_FILE'               => __FILE__,
    'PXA_SLUG'               => plugin_basename(__FILE__),
    'PXA_URL'                => plugin_dir_url(__FILE__),
    'PXA_DIR'                => plugin_dir_path(__FILE__),
    'PXA_PAGE_DASHBOARD'     => admin_url('admin.php?page=pxa_dashboard'),
    'PXA_PAGE_DOCUMENTATION' => 'https://pixelx.app/base-de-conhecimento/',
    'PXA_PAGE_SUPPORT'       => 'https://pixelx.app/suporte/',
    'PXA_PAGE_SETTINGS'      => admin_url('admin.php?page=pxa_settings'),
    'PXA_PAGE_LICENSE'       => admin_url('admin.php?page=pxa_license'),
    'PXA_PAGE_CHECK_UPDATE'  => admin_url('admin.php?pxa_action=pxa_check_update'),

    'PXA_NAME'        => $plugin_data['Name'],
    'PXA_PAGE_SALES'  => $plugin_data['PluginURI'],
    'PXA_DESCRIPTION' => $plugin_data['Description'],
    'PXA_VERSION'     => $plugin_data['Version'],
    'PXA_AUTHOR'      => $plugin_data['Author'],
    'PXA_AUTHOR_URL'  => $plugin_data['AuthorURI'],
    'PXA_DOMAIN'      => $plugin_data['TextDomain'],
];

foreach ($constants as $key => $value) {
    if ( ! defined($key)) {
        define($key, $value);
    }
}

function PXA_PIXEL_PATH($timestamp)
{
    return WP_CONTENT_DIR . '/cache/pxa-actions-' . $timestamp . '.js';
}

function PXA_PIXEL_URL($timestamp)
{
    return content_url() . '/cache/pxa-actions.js?version=' . $timestamp;
}
function PXA_PIXEL_REMOTE()
{
    // $route = get_rest_url(null, PXA_DOMAIN . '/v1/pxa-remote');
    $route = get_home_url(null, PXA_DOMAIN . '/pxa-remote');
    $route .= str_contains($route, '?') ? '&' : '?';
    // $route .= 'version=' . PXA_VERSION;

    return $route;
}

// Verificando a versão do PHP
if (version_compare(PHP_VERSION, '8.0', '<')) {
    deactivate_plugins(PXA_SLUG);

    wp_die(
        __('<b>Atenção:</b> Este plugin requer a versão do PHP 8.0 ou superior.<br>Entre em contato com o suporte da sua hospedagem pedindo para atualizar a versão do PHP do seu site.', 'pixel-x-app')
        . '<hr>'
        . sprintf(__('Sugestão de Mensagem para Hospedagem:<br> <code>Olá Suporte, <br>Eu preciso que seja feito a atualização da Versão do PHP do meu site %s, para versão 8.0 ou superior.<br><br>Vocês podem fazer para mim, ou me orientar de como fazer pelo painel?</code>', 'pixel-x-app'), get_site_url())
        . '<hr><a class="button" href="' . admin_url('plugins.php') . '">' . __('Retornar ao Painel', 'pixel-x-app') . '</a>'
    );
}

// Desativar Plugins Conflituosos
foreach (get_option('active_plugins') as $plugin) {
    // Hostinger
    if (str_contains($plugin, 'hostinger')) {
        deactivate_plugins($plugin, true);
    }
}

// Frameworks
// "facebook/php-business-sdk": "^16.0"
if ( ! class_exists('ActionScheduler')) {
    require_once PXA_DIR . 'vendor/woocommerce/action-scheduler/action-scheduler.php';
}
require_once PXA_DIR . 'vendor/autoload.php';
require_once PXA_DIR . 'framework/am-helpers/helpers.php';

if ( ! class_exists('AMManager')) {
    require_once PXA_DIR . 'framework/am-manager/manager.php';
}

// Load Functions
require_once PXA_DIR . 'functions/include.php';
