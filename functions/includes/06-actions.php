<?php

function PXA_action($parameters)
{
    // Variáveis da Página
    $domain               = get_domain();
    $page_id              = data_get($parameters, 'page_id') ?: 'REMOTE';
    $page_title           = data_get($parameters, 'page_title');
    $page_url             = data_get($parameters, 'page_url');
    $page_slug            = data_get(parse_url($page_url), 'path');
    $disabled_user_logged = boolval(PXA_get_setting('disabled_user_logged', false));
    $web_priority         = boolval(PXA_get_setting('web_priority', false));
    // $always_pageview      = boolval(PXA_get_setting('always_pageview', false));

    $page_slug = str_start($page_slug, '/');

    if ($page_slug == 'HOME' || $page_slug == '/') {
        $page_slug = 'HOME';
    }

    // Verificação
    // dd(
    //     ! PXA_license_status(),
    //     ($disabled_user_logged && user_logged()),
    //     data_get($_GET, 'action')    == 'edit',
    //     data_get($_GET, 'action')    == 'elementor',
    //     data_get($_GET, 'action')    == 'op-builder',
    //     data_get($_GET, 'op3editor') == 1,
    //     $page_url,
    //     ! str_contains($page_url, $domain),
    //     str_contains($page_url, 'op-builder'),
    //     PXA_is_bot()
    // );

    $array_verify = [
        __('Licença Inativa.', 'pixel-x-app')                            => ! PXA_license_status(),
        __('Pixel desativado para usuários logados.', 'pixel-x-app')     => ($disabled_user_logged && user_logged()),
        __('Editor de Página.', 'pixel-x-app')                           => data_get($_GET, 'action')    == 'edit',
        __('Editor de Página.', 'pixel-x-app')                           => data_get($_GET, 'action')    == 'elementor',
        __('Editor de Página.', 'pixel-x-app')                           => data_get($_GET, 'action')    == 'op-builder',
        __('Editor de Página.', 'pixel-x-app')                           => data_get($_GET, 'op3editor') == 1,
        __('Editor de Página.', 'pixel-x-app')                           => str_contains($page_url, 'op-builder'),
        __('Script carregado em página fora do domínio.', 'pixel-x-app') => ! str_contains($page_url, $domain),
        __('Acesso feito por bot.', 'pixel-x-app')                       => PXA_is_bot(),
    ];

    foreach ($array_verify as $message => $status) {
        if ($status) {
            return minifierJS("const console_msg = `
%cPixel X App
%c$message
%cAcesse https://pixelx.app/csl e conheça como podemos otimizar as suas campanhas também.`;
        
        let console_style;
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            console_style = [
                'font-size: 20px; font-weight: bold; color: #0047bb;',
                'font-size: 16px; color: #ee7b37;',
                'font-size: 14px; color: #f9f9f9;',
            ];
        } else {
            console_style = [
                'font-size: 20px; font-weight: bold; color: #0047bb;',
                'font-size: 16px; color: #ee7b37;',
                'font-size: 14px; color: #101820;',
            ];
        }

        console.log(console_msg, ...console_style);");

            return;
        }
    }

    // Assets
    $pxa_js = PXA_asset('base.min.js');
    $fb_js  = PXA_asset('fb.js');

    // APIs
    $pxa_api_event = rest_url(PXA_DOMAIN . '/v1/event');
    $pxa_api_lead  = rest_url(PXA_DOMAIN . '/v1/lead');

    // Variabels
    $g_product_id         = PXA_get_setting('product_id');
    $g_product_name       = PXA_get_setting('product_name');
    $g_product_value      = PXA_get_setting('product_value');
    $g_predicted_ltv      = PXA_get_setting('predicted_ltv');
    $g_offer_ids          = PXA_get_setting('offer_ids');
    $g_currency           = PXA_get_setting('currency');
    $g_phone_country      = PXA_get_setting('phone_country');
    $g_phone_valid        = boolval(PXA_get_setting('phone_valid', false));
    $g_phone_update       = boolval(PXA_get_setting('phone_update', false));
    $page_view_event      = false;
    $additional_settings  = '';
    $additional_resources = '';

    /**
     * Campos Personalizados
     */
    $g_input_custom_name  = PXA_get_setting('input_custom_name');
    $g_input_custom_email = PXA_get_setting('input_custom_email');
    $g_input_custom_phone = PXA_get_setting('input_custom_phone');

    /**
    * Mascara de Telefone
    */
    // Internacional
    if (PXA_get_setting('phone_mask_inter')) {
        $phone_mask_inter_css = PXA_asset_lib('intl-tel-input/css/intlTelInput.min.css');
        $phone_mask_inter_js  = PXA_asset_lib('intl-tel-input/js/intlTelInputWithUtils.min.js');
        // $phone_mask_inter_utils_js = PXA_asset_lib('intl-tel-input/js/utils.js');

        $additional_settings .= "
            // Mascara de Telefone Internacional
            phone_mask_inter: true,
            phone_mask_inter_css: '$phone_mask_inter_css',
            phone_mask_inter_js: '$phone_mask_inter_js',
        ";
        // phone_mask_inter_utils_js: '$phone_mask_inter_utils_js',
    }
    // Custom
    if (PXA_get_setting('phone_mask_status')) {
        $phone_mask    = PXA_get_setting('phone_mask');
        $phone_mask_js = PXA_asset_lib('imask/imask.js');

        $additional_settings .= "
            // Mascara de Telefone
            phone_mask: '$phone_mask',
            phone_mask_js: '$phone_mask_js',
        ";
    }

    /**
     * Passagem de Parâmetros
     */
    if ( ! boolval(PXA_get_setting('disable_parameter_passing', false))) {
        $parameter_passing_lead = boolval(PXA_get_setting('parameter_passing_lead', false));
        $additional_resources .= "await window.pixel_x_app.parameters_load($parameter_passing_lead);";
    }

    /**
     * Facebook Pixels
     */
    $pixels_global = collect(PXA_get_setting('fb_pixels'));
    $pixels        = $pixels_global->pluck('pixel');

    if ($page_id != 'REMOTE' && is_numeric($page_id)) {
        $pixels_page = collect(PXA_get_post_meta($page_id, 'fb_pixels', []));
        if ($pixels_page->count() > 0) {
            $pixels = $pixels->intersect($pixels_page->all());
        }
    }

    $pixels = $pixels->implode(',');

    /**
     * Eventos
     */
    $events      = null;
    $conversions = PXA_query([
        'post_type'      => PXA_key('conversion'),
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        // 'meta_query'     => [
        //     'relation' => 'OR',
        //     [
        //         'key'     => PXA_key('pages'),
        //         'value'   => '"' . $page_id . '"',
        //         'compare' => 'LIKE'
        //     ], [
        //         'key'     => PXA_key('display_on'),
        //         'value'   => ConversionDisplayEnum::SITE_WIDE,
        //         'compare' => '='
        //     ], [
        //         'key'     => PXA_key('display_on'),
        //         'value'   => ConversionDisplayEnum::REGEX,
        //         'compare' => '='
        //     ],
        // ]
    ])['data'];

    if (count($conversions)) {
        foreach ($conversions as $conversion) {
            if (
                blank(data_get($conversion, PXA_key('trigger')))
                || blank(data_get($conversion, PXA_key('event')))
            ) {
                continue;
            }

            $conversion_id = data_get($conversion, 'ID');
            $trigger       = TriggerEnum::from(data_get($conversion, PXA_key('trigger')));
            $trigger       = $trigger->getValue();
            $event_type    = EventEnum::from(data_get($conversion, PXA_key('event')));
            $event_name    = $event_type->getValue();
            $event_id      = wp_generate_uuid4();

            // Verifica se Slug é compatível com Regex
            if (
                data_get($conversion, PXA_key('display_on')) == ConversionDisplayEnum::REGEX
                && ! preg_match('/' . data_get($conversion, PXA_key('regex')) . '/', $page_slug)
            ) {
                continue;
            }

            // Verifica se caminho página está correto
            $c_paths = array_map(function ($item) {
                if ($item == 'HOME' || $item == '/') {
                    return 'HOME';
                }

                return str_start($item, '/');
            }, explode(',', data_get($conversion, PXA_key('path'), '')));

            if (
                data_get($conversion, PXA_key('display_on')) == ConversionDisplayEnum::PATH
                && ! in_array($page_slug, $c_paths)
            ) {
                continue;
            }

            // Verifica por ID da Página
            if (
                data_get($conversion, PXA_key('display_on')) == ConversionDisplayEnum::SPECIFIC
                && ! in_array($page_id, PXA_get_post_meta($conversion_id, 'pages', []))
            ) {
                continue;
            }

            if (in_array($event_type, [
                EventEnum::VIEW_CONTENT,
                EventEnum::CUSTOM
            ])) {
                $content_name = esc_attr(data_get($conversion, PXA_key('content_name')));
            } else {
                $content_name = null;
            }

            $product_id    = data_get($conversion, PXA_key('product_id'));
            $product_name  = esc_attr(data_get($conversion, PXA_key('product_name')));
            $offer_ids     = esc_attr(data_get($conversion, PXA_key('offer_ids')));
            $product_value = data_get($conversion, PXA_key('product_value'));
            $currency      = data_get($conversion, PXA_key('currency'));
            $predicted_ltv = data_get($conversion, PXA_key('predicted_ltv'));

            $time      = (int) data_get($conversion, PXA_key('time'), 0);
            $css_class = str_start(data_get($conversion, PXA_key('class')), '.');
            $css_id    = str_start(data_get($conversion, PXA_key('class')), '#');
            $scroll    = (int) data_get($conversion, PXA_key('scroll'), 0);

            $event_html = 'await window.pixel_x_app.send_event({';
            $event_html .= "event_id: '$event_id',";

            if ($event_type == EventEnum::CUSTOM) {
                $event_name = data_get($conversion, PXA_key('event_custom'));
                $event_html .= "event_name: '$event_name',";
                $event_html .= 'event_custom: true,';
            } else {
                $event_html .= "event_name: '$event_name',";
            }

            if (filled($page_id)) {
                $event_html .= "page_id: '$page_id',";
            }

            if (filled($page_title)) {
                $event_html .= "page_title: '$page_title',";
            }

            if (filled($product_id)) {
                $event_html .= "product_id: '$product_id',";
            } elseif (filled($g_product_id)) {
                $event_html .= "product_id: '$g_product_id',";
            }

            if (filled($product_name)) {
                $event_html .= "product_name: '$product_name',";
            } elseif (filled($g_product_name)) {
                $event_html .= "product_name: '$g_product_name',";
            }

            if (filled($product_value)) {
                $product_value = money_format($product_value);
                $event_html .= "product_value: '$product_value',";
            } elseif (filled($g_product_value)) {
                $g_product_value = money_format($g_product_value);
                $event_html .= "product_value: '$g_product_value',";
            }

            if (filled($predicted_ltv)) {
                $predicted_ltv = money_format($predicted_ltv);
                $event_html .= "predicted_ltv: '$predicted_ltv',";
            } elseif (filled($g_predicted_ltv)) {
                $g_predicted_ltv = money_format($g_predicted_ltv);
                $event_html .= "predicted_ltv: '$g_predicted_ltv',";
            }

            if (filled($offer_ids)) {
                $event_html .= "content_ids: '$offer_ids',";
            } elseif (filled($g_offer_ids)) {
                $event_html .= "content_ids: '$g_offer_ids',";
            }

            if (filled($content_name)) {
                $event_html .= "content_name: '$content_name',";
            }

            if (filled($currency)) {
                $event_html .= "currency: '$currency',";
            } elseif (filled($g_currency)) {
                $event_html .= "currency: '$g_currency',";
            }

            $event_html .= '});';

            switch ($trigger) {
                case TriggerEnum::PAGE_LOAD:
                    if ($event_type == EventEnum::PAGE_VIEW) {
                        $page_view_event = true;
                    }
                    $events .= $event_html;

                    break;
                case TriggerEnum::PAGE_TIME:
                    $events .= "setTimeout(async () => { $event_html }, $time * 1000);";

                    break;
                case TriggerEnum::VIDEO_TIME:
                    // echo 'VIDEO_TIME';

                    break;
                case TriggerEnum::FORM_SUBMIT:
                    // document.querySelectorAll('$css_class, $css_class form')
                    // el.submit();
                    $events .= "
                        const PXA_form_$conversion_id = async () => {
                            const elements_$conversion_id = document.querySelectorAll('$css_class, $css_id');
                            for (const el of elements_$conversion_id) {
                                if (el.tagName === 'FORM' && !el.classList.contains('pxa_tracked')) {
                                    el.classList.add('pxa_tracked');
                                    el.addEventListener('submit', async (e) => {
                                        // e.preventDefault();
                                        
                                        if (window.pixel_x_app.check_values(el)) {
                                            $event_html
                                        }
                                    });
                                } else {
                                    const form_$conversion_id = el.querySelector('form');
                                    if (form_$conversion_id && !form_$conversion_id.classList.contains('pxa_tracked')) {
                                        form_$conversion_id.classList.add('pxa_tracked');
                                        form_$conversion_id.addEventListener('submit', async (e) => {
                                            // e.preventDefault();
                                            
                                            if (window.pixel_x_app.check_values(form_$conversion_id)) {
                                                $event_html
                                            }
                                        });
                                    }
                            
                                    // const buttons_$conversion_id = el.querySelectorAll('.op3-link');
                                    // for (const button of buttons_$conversion_id) {
                                    //     button.addEventListener('click', async (e) => {
                                    //         form_$conversion_id.submit();
                                    //     });
                                    // }
                                }
                            }
                        }
                        await PXA_form_$conversion_id();
                    ";

                    // If Elementor in Site
                    if (in_array('elementor/elementor.php', get_option('active_plugins'))) {
                        $events .= "await window.pixel_x_app.elementor_function(
                            PXA_form_$conversion_id
                        );";
                    }

                    break;
                case TriggerEnum::CLICK:
                    $events .= "
                        const PXA_click_$conversion_id = async () => {
                            document.querySelectorAll('$css_class, $css_id')
                                .forEach(function (el) {
                                    el.addEventListener('click', async () => {
                                        $event_html
                                    });
                                });
                        }
                        await PXA_click_$conversion_id();
                    ";

                    // If Elementor in Site
                    if (in_array('elementor/elementor.php', get_option('active_plugins'))) {
                        $events .= "await window.pixel_x_app.elementor_function(
                            PXA_click_$conversion_id
                        );";
                    }

                    break;
                case TriggerEnum::VIEW_ELEMENT:
                    $events .= "
                            document.querySelectorAll('$css_class, $css_id')
                                .forEach(function (el) {
                                    document.addEventListener('scroll', async () => {
                                        if (
                                            el.getBoundingClientRect().top <= window.innerHeight / 4
                                            && !el.classList.contains('pxa_tracked')
                                            && !window.pixel_x_app.check_is_hidden(el)
                                        ) {
                                            $event_html
                                            
                                            el.classList.add('pxa_tracked');
                                        }
                                    });
                                });
                        ";

                    break;
                case TriggerEnum::MOUSE_OVER:
                    $events .= "
                            document.querySelectorAll('$css_class, $css_id')
                                .forEach(function (el) {
                                    el.addEventListener('mouseover', async () => {
                                        if (!el.classList.contains('pxa_tracked')) {
                                            $event_html
                                            
                                            el.classList.add('pxa_tracked');
                                        }
                                    });
                                });
                        ";

                    break;
                case TriggerEnum::SCROLL:
                    $events .= "
                            window.pxa = {
                                ...window.pxa,
                                scroll_reached_$scroll: false
                            }
                            window.addEventListener('scroll', async () => {
                                const windowHeight = window.innerHeight;
                                const scrollHeight = document.documentElement.scrollHeight;
                                const scrollTop = window.scrollY;
                
                                const scrollPercentage = (scrollTop / (scrollHeight - windowHeight)) * 100;
                
                                if (scrollPercentage >= $scroll && ! window.pxa.scroll_reached_$scroll) {
                                    window.pxa.scroll_reached_$scroll = true;
                                    $event_html
                                }
                            });
                        ";

                    break;
            }
        }
    }

    // Event Default PageView
    // Se não há pageview na página
    // $always_pageview
    if ( ! $page_view_event) {
        $event_id = wp_generate_uuid4();

        $events .= 'await window.pixel_x_app.send_event({';
        $events .= "event_id: '$event_id',";
        $events .= "event_name: 'PageView',";

        if (filled($page_id)) {
            $events .= "page_id: '$page_id',";
        }

        if (filled($page_title)) {
            $events .= "page_title: '$page_title',";
        }

        if (filled($g_product_id)) {
            $events .= "product_id: '$g_product_id',";
        }

        if (filled($g_product_name)) {
            $events .= "product_name: '$g_product_name',";
        }

        if (filled($g_product_value)) {
            $g_product_value = money_format($g_product_value);
            $events .= "product_value: '$g_product_value',";
        }

        if (filled($g_offer_ids)) {
            $events .= "content_ids: '$g_offer_ids',";
        }

        if (filled($g_currency)) {
            $events .= "currency: '$g_currency',";
        }

        $events .= '});';
    }

    if ($pixels) {
        $script = "
            const PXA_load_start = async () => {
                if (PXA_load) {
                    return;
                }
                
                PXA_load = true;
                
                if (typeof window.pixel_x_app === 'object' || window.hasOwnProperty('pixel_x_app')) {
                    return;
                }
                
                async function initPixelXApp() {
                    const pxa_start = {
                        // Variáveis do Site
                        domain: '$domain',
                        api_event: '$pxa_api_event',
                        api_lead: '$pxa_api_lead',
                        phone_country: '$g_phone_country' || undefined,
                        phone_valid: '$g_phone_valid' || false,
                        phone_update: '$g_phone_update' || false,
                        web_priority: '$web_priority' || false,
                    
                        // Variáveis do Facebook
                        fb_js: '$fb_js',
                        fb_pixels: '$pixels',
                        
                        // Variáveis da Página
                        page_id: '$page_id' || document.currentScript?.getAttribute('data-page-id'),
                        page_title: '$page_title' || document.currentScript?.getAttribute('data-page-name'),
                        
                        // Variáveis Gerais
                        input_custom_name: '$g_input_custom_name'|| undefined,
                        input_custom_email: '$g_input_custom_email'|| undefined,
                        input_custom_phone: '$g_input_custom_phone'|| undefined,
                        
                        $additional_settings
                    };
                    
                    window.pixel_x_app = new PixelXApp();
                    await window.pixel_x_app.start(pxa_start).catch((error) => {
                        console.error('Erro ao iniciar PixelXApp:', error);
                    });
                    
                    $additional_resources
                
                    $events
                }
                
                // Verifica se a classe PixelXApp existe
                if (typeof PixelXApp === 'undefined') {
                    // Carrega o script externo
                    const script = document.createElement('script');
                    script.src = '$pxa_js';
                    script.onload = async ()=> {
                        await initPixelXApp();
                    };
                    document.head.appendChild(script);
                } else {
                    await initPixelXApp();
                }
            }
            
            // Start
            let PXA_load = false;
            document.addEventListener('DOMContentLoaded', async () => { await PXA_load_start() }, false);
            document.addEventListener('load', async () => { await PXA_load_start() }, false);
            setTimeout(async () => { await PXA_load_start() }, 1000);
        ";

        // return $script;
        return minifierJS($script);
    }
}

/*
 * Pixel Remote
 */
add_action('rest_api_init', function () {
    register_rest_route(PXA_DOMAIN . '/v1', '/pxa-remote', [
        'methods'  => 'GET',
        'callback' => function ($request) {
            header('HTTP/1.1 301 Moved Permanently');
            header('Location: ' . PXA_PIXEL_REMOTE() . $_SERVER['QUERY_STRING']);
            exit;
        },
        'permission_callback' => '__return_true',
    ]);
});

add_filter('single_template', 'pxa_remote_template');
add_filter('template_include', 'pxa_remote_template');

function pxa_remote_template($original)
{
    global $wp;

    if ($wp->request == PXA_DOMAIN . '/pxa-remote') {
        http_response_code(200);
        header('Content-Type: application/javascript');
        header('Access-Control-Allow-Origin: *');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        $page_id    = data_get($_GET, 'id', data_get($_GET, 'pi'));
        $page_title = data_get($_GET, 'title', data_get($_GET, 'pt'));
        $page_url   = data_get($_GET, 'url', data_get($_GET, 'pu'));

        $data = PXA_action([
            'page_id'    => $page_id,
            'page_title' => urldecode($page_title ?: ''),
            'page_url'   => urldecode($page_url ?: ''),
        ]);

        echo $data;

        exit;
    }

    return $original;
}

/*
 * Head Page
 */
add_action('wp_head', function () {
    if ( ! PXA_license_status()) {
        return;
    }

    $url = sprintf(
        '%sid=%s&title=%s&url=%s&time=%s',
        PXA_PIXEL_REMOTE(),
        (get_the_ID() ?: is_front_page()),
        urlencode(get_the_title() ?: get_bloginfo('name')),
        urlencode(get_full_url()),
        current_time('timestamp')
    );

    echo '<script type="text/javascript"
        src="' . $url . '"
        nowprocket
        data-no-optimize="1"
        data-no-defer="1"
    ></script>';

    echo PXA_get_setting('fb_domain');
    echo PXA_get_setting('gg_ads_domain');
    echo PXA_get_setting('header_scripts');
});

add_action('wp_body_open', function () {
    if ( ! PXA_license_status()) {
        return;
    }

    echo PXA_get_setting('body_scripts');

    /**
     * Google Tag Manager
     **/
    if ($pxa_gg_tm = PXA_get_setting('gg_tm')) {
        echo "<!-- Google Tag Manager (noscript) -->
        <noscript><iframe src='https://www.googletagmanager.com/ns.html?id=$pxa_gg_tm'
        height='0' width='0' style='display:none;visibility:hidden'></iframe></noscript>
        <!-- End Google Tag Manager (noscript) -->";
    }
});

add_action('wp_footer', function () {
    if ( ! PXA_license_status()) {
        return;
    }

    echo PXA_get_setting('footer_scripts');

    /**
     * Google Pixel
     **/
    $pxa_gg_analytics = PXA_get_setting('gg_analytics');
    $pxa_gg_ads       = PXA_get_setting('gg_ads');

    if ($pxa_gg_analytics || $pxa_gg_ads) {
        echo '<script async src="' . PXA_asset('gtag.js') . '"></script>';
    }

    /**
     * Google Analytics
     **/
    if ($pxa_gg_analytics = PXA_get_setting('gg_analytics')) {
        echo '<script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag("js", new Date());
            gtag("config", "' . $pxa_gg_analytics . '");
        </script>';
    }

    /**
     * Google Ads
     **/
    if ($pxa_gg_ads) {
        echo '<script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag("js", new Date());
            gtag("config", "' . $pxa_gg_ads . '");
        </script>';
    }

    /**
     * Google Tag Manager
     **/
    if ($pxa_gg_tm = PXA_get_setting('gg_tm')) {
        echo "<!-- Google Tag Manager -->
        <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
        'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer','$pxa_gg_tm');</script>
        <!-- End Google Tag Manager -->";
    }

    /**
     * Clarity
     **/
    if ($pxa_clarity = PXA_get_setting('tools_clarity')) {
        echo '<script type="text/javascript">
            (function(c,l,a,r,i,t,y){
                c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
                t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
                y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
            })(window, document, "clarity", "script", "' . $pxa_clarity . '");
        </script>';
    }

    /**
     * Hotjar
     **/
    if ($pxa_hotjar = PXA_get_setting('tools_hotjar')) {
        echo '<!-- Hotjar Tracking Code -->
        <script>
            (function(h,o,t,j,a,r){
                h.hj=h.hj||function(){(h.hj.q=h.hj.q||[]).push(arguments)};
                h._hjSettings={hjid:' . $pxa_hotjar . ',hjsv:6};
                a=o.getElementsByTagName("head")[0];
                r=o.createElement("script");r.async=1;
                r.src=t+h._hjSettings.hjid+j+h._hjSettings.hjsv;
                a.appendChild(r);
            })(window,document,"https://static.hotjar.com/c/hotjar-",".js?sv=");
        </script>';
    }
});
