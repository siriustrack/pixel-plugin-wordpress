<?php

/*
 * Components
 */
if ( ! function_exists('PXA_component_header')) {
    function PXA_component_header($title, $subtitle = null)
    {
        $content = '<div class="pxa-col-xs-12 pxa-col-12">';
        $content .= '<div class="pxa-pt2">';
        $content .= '<div class="pxa-text-2xl pxa-m0">' . $title . '</div>';

        if ($subtitle) {
            $content .= '<div class="pxa-text-sm  pxa-m0 pxa-small-caps">' . $subtitle . '</div>';
        }

        $content .= '<hr class="pxa-divider pxa-mb0">';
        $content .= '</div>';
        $content .= '</div>';

        echo $content;
    }
}

if ( ! function_exists('PXA_component_notice')) {
    function PXA_component_notice($data)
    {
        $class   = data_get($data, 'class', 'error'); // warning, success, info, error
        $message = '';

        if (data_get($data, 'close')) {
            $class .= ' is-dismissible';
        }

        if (data_get($data, 'title')) {
            $message .= sprintf('<div class="pxa-card-subtitle pxa-mb1"><b>%s</b></div>', data_get($data, 'title'));
        }

        if (data_get($data, 'description')) {
            $message .= sprintf('<div>%s</div>', data_get($data, 'description'));
        }

        if (data_get($data, 'button')) {
            $message .= '<div class="pxa-mt1">' . PXA_component_button([
                'title' => data_get($data, 'button.title'),
                'link'  => data_get($data, 'button.link'),
            ]) . '</div>';
        }

        printf('<div class="pxa notice notice-%1$s">
            <div class="pxa-inline-block pxa-m1 pxa-vertical-top">
                <img width="40" src="' . PXA_get_image('logo') . '"/>
            </div>
            <div class="pxa-inline-block pxa-m1">%2$s</div>
        </div>', esc_attr($class), $message);
    }
}

if ( ! function_exists('PXA_component_button')) {
    function PXA_component_button($data)
    {
        $content            = '';
        $title              = data_get($data, 'title');
        $link               = data_get($data, 'link', '#');
        $class              = data_get($data, 'class', 'button-primary');
        $icon               = data_get($data, 'icon');
        $style              = data_get($data, 'style');
        $target             = data_get($data, 'target');
        $download           = data_get($data, 'download', false);
        $confirmation       = data_get($data, 'confirmation');
        $confirmation_label = data_get($data, 'confirmation_label', __('Confirmar', 'pixel-x-app'));

        if ($confirmation) {
            $content .= <<<HTML
                <button
                    type="button"
                    onclick="sweet_alert('$confirmation', '$link');"
                    class="button $class"
                    style="$style"
                >
            HTML;
        } else {
            $content .= "<a
                href='$link'
                class='button $class'
            ";

            if ($style) {
                $content .= "style='$style'";
            }

            if ($target) {
                $content .= "target='$target'";
            }

            if ($download) {
                $content .= "download='$download'";
            }

            $content .= '>';
        }

        if ($icon) {
            $content .= '<span class="pxa-mr1 pxa-vertical-middle dashicons dashicons-' . $icon . '"></span>';
        }

        $content .= $title;

        if ($confirmation) {
            $content .= '</button>';
        } else {
            $content .= '</a>';
        }

        return $content;
    }
}

if ( ! function_exists('PXA_component_widget_stats')) {
    function PXA_component_widget_stats($title, $value, $icon = null, $description = null)
    {
        if ($description) {
            $description = '<p class="pxa-mt1 pxa-text-base">' . $description . '</p>';
        }

        if ($icon) {
            $icon = '<div class="pxa-stats-badge">
			    <span class="pxa-stats-icon dashicons dashicons-' . $icon . '"></span>
			</div>';
        }

        return <<<HTML
		<div class="pxa-col-xs-12 pxa-col-3">
			<div class="pxa-card">
			  <div class="pxa-flex-center">
				$icon
				<div class="pxa-pl2">
				    <div class="pxa-text-2xl pxa-m0">$value</div>
				    <span class="pxa-text-sm">$title</span>
				</div>
			  </div>
			  $description
			</div>
		</div>
HTML;
    }
}

if ( ! function_exists('PXA_widget_chart')) {
    function PXA_widget_chart($data)
    {
        $id      = wp_generate_uuid4();
        $type    = data_get($data, 'type', 'line'); // bar, pie, line
        $content = data_get($data, 'content');
        $label   = __('Eventos', 'pixel-x-app');
        $labels  = wp_json_encode(array_column($content, 'name'));
        $values  = wp_json_encode(array_column($content, 'count'));

        $body = '<div class="pxa-col-xs-12 pxa-col-' . data_get($data, 'grid', 4) . '">';
        $body .= '<div class="pxa-card">';

        if (data_get($data, 'title')) {
            $body .= '<div class="pxa-card-title">' . data_get($data, 'title') . '</div>';
        }

        if (data_get($data, 'description')) {
            $body .= '<div class="pxa-card-subtitle">' . data_get($data, 'description') . '</div>';
        }

        $body .= '<hr class="pxa-divider">';
        $body .= '<canvas id="' . $id . '"></canvas>';
        $body .= '</div></div>';

        echo $body;

        echo <<<HTML
		<script>
		jQuery(window).load(function () {
			new Chart(document.getElementById('$id'), {
				type: '$type',
				data: {
					labels: $labels,
					datasets: [{
						label: '$label',
						data: $values,
						borderWidth: 1,
						fill: true,
					}]
				},
				options: {
					scales: {
						y: {
							beginAtZero: true
						}
					}
				}
			});
		});
		</script>
		HTML;
    }
}

if ( ! function_exists('PXA_widget_list')) {
    function PXA_widget_list($data)
    {
        $body = '<div class="pxa-col-xs-12 pxa-col-' . data_get($data, 'grid', 4) . '">';
        $body .= '  <div class="pxa-card">';

        if (data_get($data, 'title')) {
            $body .= '      <div class="pxa-card-title">' . data_get($data, 'title') . '</div>';
        }

        if (data_get($data, 'description')) {
            $body .= '      <div class="pxa-card-subtitle">' . data_get($data, 'description') . '</div>';
        }

        $table_id = 'ID_' . rand();
        $body .= "<div id='{$table_id}'></div>";

        $body .= "<script defer>
            new Tabulator('#" . $table_id . "', {
                data: " . $data['content'] . ",
                autoColumns: true,
                layout: 'fitColumns',
                columnDefaults:{
                    resizable: false
                },
                pagination: true,
                paginationSize: 50,
                paginationButtonCount:3,
            })
        </script>";
        $body .= '</div>';
        $body .= '</div>';
        echo $body;
    }
}

if ( ! function_exists('PXA_list_itens')) {
    function PXA_list_itens($itens)
    {
        $body = '';
        if (empty($itens)) {
            $body .= '<li class="pxa-list-item">' . __('Nenhum item foi encontrado.', 'pixel-x-app') . '</li>';
        } else {
            foreach ($itens as $key => $item) {
                $body .= '<li class="pxa-list-item">';

                if (data_get($item, 'link')) {
                    $body .= '<a class="dashicons dashicons-edit pxa-float-right" href="' . data_get($item, 'link') . '">' . data_get($item, 'name', $key) . '</a>';
                } else {
                    $body .= data_get($item, 'name');
                }

                if (data_get($item, 'count')) {
                    $body .= '<span class="pxa-float-right">' . data_get($item, 'count') . '</span>';
                }

                if (data_get($item, 'badge')) {
                    $body .= '<span class="pxa-badge pxa-float-right">' . data_get($item, 'badge') . '</span>';
                }
                $body .= '</li>';
            }
        }

        return $body;
    }
}

if ( ! function_exists('PXA_filter_select')) {
    function PXA_filter_select($label, $meta_key, $meta_values)
    {
        echo '<select name="' . $meta_key . '" id="' . $meta_key . '" class="postform">';
        echo '<option value="">' . $label . '</option>';

        foreach ($meta_values as $key => $value) {
            echo '<option value="' . esc_attr($key) . '" ' . selected(data_get($_GET, $meta_key), $key, false) . '>' . esc_html($value) . '</option>';
        }

        echo '</select>';
    }
}
