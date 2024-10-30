<?php

/*
 * Example Usage:
 * if ( ! class_exists('AMUpdaterChecker')) {
 *     include LG_DIR . 'framework/am-manager/manager.php';
 *     new AMManager(__FILE__, 'lg_settings', 'license', 'email');
 * }
 */

if ( ! class_exists('AMManager')) {
    class AMManager
    {
        public $api_url;
        public $plugin_path;
        public $plugin_slug;
        public $plugin_data;
        public $current_version;
        public $text_domain;
        public $settings;
        public $user_license;
        public $user_email;
        public $user_status;
        public $key_license;
        public $key_email;
        public $key_status;

        public function __construct($plugin_path, $settings, $key_license, $key_email, $key_status = 'status')
        {
            add_action('admin_enqueue_scripts', [&$this, 'notice_style']);
            add_action('in_admin_header', [&$this, 'notice_license'], 10, 2);
            if ( ! function_exists('get_plugin_data')) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }
            if ( ! function_exists('wp_remote_post') || ! function_exists('wp_remote_get')) {
                require_once ABSPATH . 'wp-includes/http.php';
            }

            $this->api_url     = 'https://ativa.alanmosko.com.br/wp-json/license/';
            $this->plugin_path = plugin_basename($plugin_path);
            $this->plugin_data = get_plugin_data($plugin_path);

            $this->settings = $settings;
            $settings       = get_option($settings);

            // Keys
            $this->key_license = $key_license;
            $this->key_email   = $key_email;
            $this->key_status  = $key_status;

            // License
            $this->user_license = (isset($settings[$key_license])) ? $settings[$key_license] : $key_license;
            $this->user_email   = (isset($settings[$key_email])) ? $settings[$key_email] : $key_email;
            $this->user_status  = (isset($settings[$key_status])) ? $settings[$key_status] : $key_status;

            // Plugin Slug
            if (strstr($this->plugin_path, '/')) {
                list($t1, $t2) = explode('/', $this->plugin_path);
            } else {
                $t2 = $this->plugin_path;
            }
            $this->plugin_slug = str_replace('.php', '', $t2);

            $this->current_version = $this->plugin_data['Version'];
            $this->text_domain     = $this->plugin_data['TextDomain'];

            if (empty($this->user_license) || empty($this->user_email)) {
                return false;
            }

            if ($this->user_status != __('Ativo', $this->text_domain)) {
                $this->license_check();
            }

            // Notice Remote
            if ( ! wp_next_scheduled('am_manager')) {
                wp_schedule_event(time(), DAY_IN_SECONDS, function () {
                    add_action('admin_notices', [&$this, 'notice_remote'], 10, 2);
                });
            }

            // Check for Update
            add_filter('site_transient_update_plugins', [ $this, 'check_for_update' ]);

            // Update
            add_filter('plugins_api', [&$this, 'plugin_api_call'], 10, 3);

            // Notice API
            add_action('rest_api_init', function () {
                register_rest_route('am_notice', '/dismiss', [
                    'methods'             => 'GET',
                    'callback'            => [&$this, 'notice_dismiss'],
                    'permission_callback' => '__return_true',
                ]);
            });
        }

        public function check_for_update($transient = null)
        {
            if (is_null($transient)) {
                $transient = get_site_transient('update_plugins');
            }

            if (empty($transient->checked)) {
                return $transient;
            }

            $status = $this->license_check();
            if ($status == 'active') {
                $response = $this->request_run('info');

                if (is_object($response) && ! empty($response) && version_compare($this->current_version, $response->version, '<')) {
                    $data = [
                        'id'          => $this->plugin_path,
                        'slug'        => $this->plugin_slug,
                        'plugin'      => $this->plugin_path,
                        'new_version' => $response->version,
                        'url'         => $response->author_homepage,
                        'package'     => $response->download_url,
                        'icons'       => ($response->icons) ? (array) $response->icons : null,
                        'banners'     => (array) data_get($response, 'banners'),
                    ];

                    $transient->response[$this->plugin_path] = (object) $data;
                }
            }

            set_site_transient('update_plugins', $transient);

            return $transient;
        }

        public function plugin_api_call($res, $action, $args)
        {
            if ( ! isset($args->slug) || $args->slug != $this->plugin_slug) {
                return $res;
            }

            $response = $this->request_run('info');

            if (is_object($response) && ! empty($response)) {
                $res                  = new stdClass();
                $res->name            = $response->name;
                $res->slug            = $this->plugin_slug;
                $res->version         = $response->version;
                $res->author          = '<a href="' . $response->author_homepage . '" target="_blank">' . $response->author . '</a>';
                $res->author_homepage = $response->author_homepage;
                $res->download_link   = $response->download_url;
                $res->trunk           = $response->download_url;
                $res->sections        = (array) $response->sections;
                $res->homepage        = $response->homepage;
                $res->last_updated    = $response->last_updated;
                $res->banners         = (array) $response->banners;

                $res->requires_php = '5.6';
            }

            return $res;
        }

        public function request_prepare($action)
        {
            global $wp_version;

            return [
                'timeout' => 10,
                'body'    => [
                    'action'  => $action,
                    'license' => $this->user_license,
                    'email'   => $this->user_email,
                    'domain'  => $this->get_domain(),
                ],
                'user-agent' => 'WordPress/' . $wp_version . '; ' . home_url(),
            ];
        }

        public function request_run($action)
        {
            $cache_key = 'am_manager_' . $this->plugin_slug . '_' . $action . '_' . $this->user_license . '_' . $this->user_email;

            $response = get_transient($cache_key);

            if ($response === false) {
                $request_data = $this->request_prepare($action);
                $raw_response = wp_remote_post($this->api_url . 'check', $request_data);
                $response     = null;

                if ( ! is_wp_error($raw_response) && ($raw_response['response']['code'] == 200)) {
                    $response = json_decode($raw_response['body'])->response;

                    //                     $response = json_decode($raw_response['body'], true);
                    //
                    //                     if (isset($response['response'])) {
                    //                         $response = (object) $response['response'];
                    //                     }
                }

                if ($response == 'active') {
                    set_transient($cache_key, $response, 21600); // 6 Horas
                }
            }

            return $response;
        }

        public function license_check()
        {
            $settings = get_option($this->settings);
            $response = $this->request_run('license');

            wp_cache_delete('alloptions', 'options');

            return $response;
        }

        public function get_domain($url = null)
        {
            if ($url == null) {
                $url = get_site_url();
                $url = str_replace('http://', '', $url);
                $url = str_replace('https://', '', $url);

                return str_replace('/', '', $url);
            }

            return $_SERVER['SERVER_NAME'];
        }

        public function notice_remote()
        {
            $request_data = $this->request_prepare('notice');

            $response = wp_remote_post($this->api_url . 'message/callback', $request_data);

            if ( ! is_wp_error($response) && ($response['response']['code'] == 200)) {
                $response = json_decode($response['body']);
                if (isset($response) && $response->status == 200) {
                    $data = [
                        'id'      => $response->response->id,
                        'title'   => $response->response->title,
                        'message' => $response->response->message,
                        'type'    => $response->response->icon,
                        'dismiss' => true,
                    ];

                    if (get_option('am_notice')) {
                        update_option('am_notice', $data);
                    } else {
                        add_option('am_notice', $data);
                    }
                }
            }
        }

        public function notice_style()
        {
            wp_enqueue_style('am-notice-style', plugins_url('/assets/style.css', __FILE__));
        }

        public function notice_dismiss()
        {
            $notice = get_option('am_notice');

            $hiddens = get_option('am_notice_hidden');

            if (get_option('am_notice_hidden') && ! in_array($notice['id'], $hiddens)) {
                $this->notice_template($notice);
            } else {
                add_option('am_notice_hidden', [$notice['id']]);
            }

            update_option('am_notice', 'false');

            wp_redirect($_SERVER['HTTP_REFERER']);
        }

        public function notice_license()
        {
            if (empty($this->user_license) || empty($this->user_email) || empty($this->user_status)) {
                $this->notice_template([
                    'title'   => $this->plugin_data['Name'],
                    'message' => __('Sua licença não está ativa ainda, verifique seu email e licença para que possa ativar as funções do plugin para você.', $this->text_domain),
                    'type'    => 'danger',
                ]);
            } elseif ($this->user_status != __('Ativo', $this->text_domain)) {
                $this->notice_template([
                    'title'   => $this->plugin_data['Name'],
                    'message' => __('Sua licença está inválida, verifique se há erro de digitação na sua licença e/ou email, remova os espaços em branco, se houver.', $this->text_domain),
                    'type'    => 'danger',
                ]);
            }

            if (get_option('am_notice', 'false') != 'false') {
                $notice  = get_option('am_notice');
                $hiddens = get_option('am_notice_hidden', []);

                if ( ! in_array($notice['id'], $hiddens)) {
                    $this->notice_template($notice);
                } else {
                    update_option('am_notice', 'false');
                }
            }
        }

        public function notice_template($data)
        {
            $button = '';
            if (isset($data['button']['link']) && isset($data['button']['text'])) {
                $button .= '<div class="am-notice-right">';
                $button .= '    <div class="am-notice-right-button">';
                $button .= '        <a href="' . $data['button']['link'] . '" class="am-notice-button">' . $data['button']['text'] . '</a>';
                $button .= '    </div>';
                $button .= '</div>';
            }

            if ( ! isset($data['type'])) {
                $data['type'] = 'info';
            }

            if ( ! isset($data['logo'])) {
                $data['logo'] = plugins_url('assets/icon-' . $data['type'] . '.svg', __FILE__);
            }

            $dismiss = '';
            if (isset($data['dismiss'])) {
                $dismiss = '<a href="' . get_rest_url(null, 'am_notice/dismiss') . '" class="notice-dismiss"></a>';
            }

            $body = '<div class="am-notice-wrap">';
            $body .= '  <div class="am-notice-container am-notice-' . $data['type'] . '">';
            $body .= '      <div class="am-notice-header">';
            $body .= '          <div class="am-notice-logo">';
            $body .= '              <img src="' . $data['logo'] . '" alt="Notice">';
            $body .= '              <div class="am-notice-logo-subtitle">';
            $body .= '                  <h3>' . $data['title'] . '</h3>';
            $body .= '                  ' . $data['message'] . '';
            $body .= '              </div>';
            $body .= '          </div>';
            $body .= '          ' . $button . '';
            $body .= '      </div>';
            $body .= '      ' . $dismiss;
            $body .= '  </div>';
            $body .= '</div>';

            echo $body;
        }

        public function license_manager_access()
        {
            return $this->api_url . 'license-manager-access?license=' . $this->user_license . '&email=' . $this->user_email;
        }

        public function license_information()
        {
            $key = $this->plugin_path . '-' . $this->settings . '-' . $this->key_license . '-' . $this->key_email;

            $content = get_transient($key);

            if ($content === false) {
                $data = wp_remote_get($this->api_url . 'information?license=' . $this->user_license . '&email=' . $this->user_email);

                $result = json_decode(wp_remote_retrieve_body($data), true);

                if (is_array($result) && ! is_wp_error($result)) {
                    $content = $result['response'];

                    set_transient($key, $content, 7 * DAY_IN_SECONDS);

                    return $content;
                }
            }

            return $content;
        }

        public function license_remove()
        {
            $data   = wp_remote_get($this->license_information()['remove_domain'] . $_SERVER['SERVER_NAME']);
            $result = json_decode(wp_remote_retrieve_body($data));
            if (is_object($result) && ! is_wp_error($result)) {
                return $result->response;
            }
        }
    }
}
