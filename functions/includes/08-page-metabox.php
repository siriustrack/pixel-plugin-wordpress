<?php

use Carbon_Fields\Container;
use Carbon_Fields\Field;

add_action('carbon_fields_register_fields', function () {
    $pixels = collect(PXA_get_setting('fb_pixels'))->pluck('pixel');
    $pixels = $pixels->mapWithKeys(function ($item, $key) {
        return [$item => $item];
    });

    Container::make('post_meta', __('Pixel X App', 'pixel-x-app'))
        ->where('post_type', 'IN', ['page', 'post'])
        ->set_context('side')
        ->set_priority('high')
        ->add_fields([
            /*
             * Page
             */
            Field::make('multiselect', PXA_key('fb_pixels'), __('Facebook - Pixel na Página', 'pixel-x-app'))
                ->set_help_text(__('Se nenhum pixel for selecionado, será acionado todos os pixels na página.', 'pixel-x-app'))
                ->set_options($pixels->all()),
        ]);
});
