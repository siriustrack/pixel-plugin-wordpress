<?php

if ( ! function_exists('data_get')) {
    /**
     * @param $target
     * @param $key
     * @param $default
     * @return mixed
     */
    function data_get($target, $key, $default = null)
    {
        if (is_null($key)) {
            return $target;
        }

        foreach (explode('.', $key) as $segment) {
            if (is_array($target)) {
                if ( ! array_key_exists($segment, $target)) {
                    return $default;
                }

                $target = $target[$segment];
            } elseif (is_object($target)) {
                if ( ! isset($target->{$segment})) {
                    return $default;
                }

                $target = $target->{$segment};
            } else {
                return $default;
            }
        }

        return $target;
    }
}

if ( ! function_exists('str_contains')) {
    /**
     * @param $string
     * @param $search
     */
    function str_contains($string, $search)
    {
        foreach ((array) $search as $needle) {
            if ($needle !== '' && mb_strpos($string, $needle) !== false) {
                return true;
            }
        }

        return false;
    }
}

if ( ! function_exists('array_contains')) {
    /**
     * @param $string
     * @param $search
     */
    function array_contains($haystack, $needle)
    {
        if (in_array($needle, (array) $haystack)) {
            return true;
        }

        foreach ((array) $haystack as $item) {
            foreach ((array) $needle as $search) {
                if (str_contains($item, $search)) {
                    return true;
                }
            }
        }

        return false;
    }
}

if ( ! function_exists('str_after')) {
    /**
     * @param $string
     * @param $search
     * @return mixed
     */
    function str_after($string, $search)
    {
        return ($string && $search) ? strstr($string, $search) : $string;
    }
}

if ( ! function_exists('str_before')) {
    /**
     * @param $string
     * @param $search
     * @return mixed
     */
    function str_before($string, $search)
    {
        return ($string && $search) ? strstr($string, $search, true) : $string;
    }
}

if ( ! function_exists('str_finish')) {
    /**
     * @param $value
     * @param $cap
     */
    function str_finish($value, $cap)
    {
        $quoted = preg_quote($cap, '/');

        return preg_replace('/(?:' . $quoted . ')+$/u', '', $value) . $cap;
    }
}

if ( ! function_exists('str_starts_with')) {
    /**
     * @param $haystack
     * @param $needles
     */
    function str_starts_with($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if ($needle !== '' && substr($haystack, 0, strlen($needle)) === (string) $needle) {
                return true;
            }
        }

        return false;
    }
}

if ( ! function_exists('str_start')) {
    /**
     * @param $haystack
     * @param $needles
     */
    function str_start($value, $prefix)
    {
        $quoted = preg_quote($prefix, '/');

        return $prefix . preg_replace('/^(?:' . $quoted . ')+/u', '', $value);
    }
}

if ( ! function_exists('str_random')) {
    function str_random($length = 20, $uppercase = false)
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

        if ( ! $uppercase) {
            $characters .= 'abcdefghijklmnopqrstuvwxyz';
        }

        $random_string = '';

        for ($i = 0; $i < $length; $i++) {
            $random_string .= $characters[wp_rand(0, strlen($characters) - 1)];
        }

        return $random_string;
    }
}

if ( ! function_exists('number_raw')) {
    /**
     * @param mixed $value The value to extract the number from.
     * @return string The extracted numeric string or an empty string if the value is null.
     */
    function number_raw($value)
    {
        if (is_null($value)) {
            return ''; // Return empty string if value is null
        }

        return preg_replace('/[^0-9]/', '', $value);
    }
}

if ( ! function_exists('plyr_video')) {
    /**
     * @param $video
     * @param $type
     * @param null $poster
     * @return mixed
     */
    function plyr_video($video, $type = null, $poster = null)
    {
        $id = wp_rand(9000, 9999);
        wp_enqueue_style('plyr', 'https://cdn.plyr.io/3.6.1/plyr.css', []);
        wp_enqueue_script('plyr', 'https://cdn.plyr.io/3.6.1/plyr.js', []);

        switch ($type) {
            case 'youtube':$body = '<div id="' . $id . '" data-plyr-provider="youtube" data-plyr-embed-id="' . $video . '" poster="' . $poster . '"></div>';

                break;
            case 'vimeo':$body = '<div id="' . $id . '" data-plyr-provider="vimeo" data-plyr-embed-id="' . $video . '" poster="' . $poster . '"></div>';

                break;
            default:$body = '<video id="' . $id . '" playsinline controls data-poster="' . $poster . '">';
                $body .= '    <source src="' . $video . '" type="video/mp4" />';
                $body .= '</video>';

                break;
        }
        $body .= '<script>jQuery(document).ready(function($) { const player_' . $id . ' = new Plyr(document.getElementById("' . $id . '")); });</script>';

        return $body;
    }
}

if ( ! function_exists('get_time')) {
    /**
     * @param $value
     * @param $type
     * @return mixed
     */
    function get_time($value, $type)
    {
        $value = (int) $value;

        switch ($type) {
            case 'minutes':
                return $value * 60;

                break;
            case 'hours':
                return $value * 60 * 60;

                break;
            case 'days':
                return $value * 60 * 60 * 24;

                break;
            case 'weeks':
                return $value * 60 * 60 * 24 * 7;

                break;
            case 'months':
                return $value * 60 * 60 * 24 * 30;

                break;
            default:
                return $value;

                break;
        }
    }
}

if ( ! function_exists('md_get_client_IP')) {
    /**
     * @param $getHostByAddr
     * @return mixed
     */
    function md_get_client_IP($getHostByAddr = false)
    {
        $headers = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            if ( ! empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];

                // Em caso de múltiplos IPs, pegue o primeiro
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }

                return trim($ip);
            }
        }

        return;
        // return json_decode(file_get_contents('https://get.geojs.io/v1/ip.json'), true)['ip'];
    }
}

if ( ! function_exists('wp_response')) {
    function wp_response($code, $value)
    {
        return new WP_REST_Response([
            'status'   => $code,
            'response' => $value,
        ]);
    }
}

if ( ! function_exists('player_video')) {
    function player_video($video, $type = null)
    {
        wp_register_style('am_player_video', false);
        wp_enqueue_style('am_player_video');
        wp_add_inline_style('am_player_video', '.embed-container { position: relative !important; padding-bottom: 56.25% !important; height: 0 !important; overflow: hidden !important; max-width: 100% !important; } .embed-container iframe, .embed-container object, .embed-container embed { position: absolute !important; top: 0 !important; left: 0 !important; width: 100% !important; height: 100% !important; }');

        switch ($type) {
            case 'youtube':$video = 'https://www.youtube.com/embed/' . $video;

                break;
            case 'vimeo':$video = 'https://player.vimeo.com/' . $video;

                break;
            default:break;
        }

        return "<div class='embed-container'>
            <iframe src='" . $video . "' frameborder='0' allow='autoplay; fullscreen; picture-in-picture' allowfullscreen></iframe>
        </div>";
    }
}

if ( ! function_exists('has_ssl')) {
    function has_ssl($url)
    {
        $stream = stream_context_create([
            'ssl' => [
                'capture_peer_cert' => true,
            ],
        ]);
        $read = fopen($url, 'rb', false, $stream);
        $cont = stream_context_get_params($read);

        return ( ! is_null($cont['options']['ssl']['peer_certificate'])) ? true : false;
    }
}

if ( ! function_exists('get_whois_info')) {
    function get_whois_info($domain)
    {
        $cache_key = 'whois_info_' . md5($domain); // Gera uma chave de cache única

        // Tenta recuperar os dados do cache
        $data = wp_cache_get($cache_key);

        if ($data === false) {
            // Dados não encontrados no cache, realiza a consulta WHOIS
            $data = wp_remote_get('https://api.api-ninjas.com/v1/whois?domain=' . $domain, [
                'headers' => [
                    'X-Api-Key' => 'KeYFaH0Ar7DkVtJ+qeN1+A==iuwrm8zg18vBh0I8'
                ]
            ]);

            // Verifica se a consulta foi bem-sucedida
            if (is_wp_error($data)) {
                return $domain;
            }

            $body = json_decode(data_get($data, 'body'), true);

            // Armazena os dados no cache com expiração de 1 hora
            wp_cache_set($cache_key, $body, '', HOUR_IN_SECONDS);

            return $body;
        }

        return $data;
    }
}

if ( ! function_exists('get_domain')) {
    function get_domain($url = null)
    {
        if ($url == null) {
            $url_parts = parse_url(get_site_url());
            $url       = $url_parts['host'];
        }

        //         $whois       = get_whois_info($url);
        //         $domain_name = data_get($whois, 'domain_name');
        //
        //         if (filled($domain_name)) {
        //             return $domain_name;
        //         }

        // Verifica se a parte do host está presente e não é nula
        // https://stackoverflow.com/questions/1201194/php-getting-domain-name-from-subdomain
        // https://gist.github.com/pocesar/5366899
        if (isset($url) && $url !== null) {
            $myhost = strtolower(trim($url));
            $count  = substr_count($myhost, '.');

            if ($count === 2) {
                if (strlen(explode('.', $myhost)[1]) > 3) {
                    $myhost = explode('.', $myhost, 2)[1];
                }
            } elseif ($count > 2) {
                $myhost = get_domain(explode('.', $myhost, 2)[1]);
            }

            return $myhost;
        }

        return null;
    }
}

if ( ! function_exists('blank')) {
    /**
     * Determine if the given value is "blank".
     *
     * @param  mixed  $value
     * @return bool
     */
    function blank($value)
    {
        if (is_null($value)) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        if (is_numeric($value) || is_bool($value)) {
            return false;
        }

        if ($value instanceof Countable) {
            return count($value) === 0;
        }

        return empty($value);
    }
}

if ( ! function_exists('filled')) {
    /**
     * Determine if a value is "filled".
     *
     * @param  mixed  $value
     * @return bool
     */
    function filled($value)
    {
        return ! blank($value);
    }
}

if ( ! function_exists('removeNullValues')) {
    function removeNullValues($array)
    {
        if (is_null($array)) {
            return $array;
        }

        foreach ($array as $key => $value) {
            if (is_array($value) && count($value) > 0) {
                $array[$key] = removeNullValues($value);
            } elseif (blank($value)) {
                unset($array[$key]);
            }
        }

        return $array;
    }
}

if ( ! function_exists('dump')) {
    function dump(...$args)
    {
        foreach ($args as $arg) {
            echo '<pre>';
            var_export($arg);
            echo '</pre>';
        }
    }
}

if ( ! function_exists('dd')) {
    function dd(...$args)
    {
        foreach ($args as $arg) {
            echo '<pre>';
            var_export($arg);
            echo '</pre>';
        }

        die();
    }
}

if ( ! function_exists('user_logged')) {
    function user_logged()
    {
        $cookie_user = '';

        foreach ($_COOKIE as $key => $cookie) {
            if (str_contains($key, 'wordpress_logged_in')) {
                $cookie_user = $cookie;

                break;
            }
        }

        $cookie_elements = explode('|', $cookie_user);

        if (count($cookie_elements) < 2) {
            return false;
        }

        $username = $cookie_elements[0];

        return (bool) username_exists($username);
    }
}

if ( ! function_exists('get_full_page_url')) {
    function get_full_url()
    {
        return home_url(add_query_arg(null, null));
    }
}
