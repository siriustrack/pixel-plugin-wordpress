<?php

function PXA_admin_dashboard()
{
    // $count_leads = wp_count_posts(PXA_key('lead'))->publish;
    $query_leads = PXA_query([
        'post_type'      => PXA_key('lead'),
        'post_status'    => 'any',
        'posts_per_page' => -1,
    ]);

    $leads             = $query_leads['data'];
    $count_leads       = $query_leads['total'];
    $count_events      = wp_count_posts(PXA_key('event'))->publish;
    $count_conversions = wp_count_posts(PXA_key('conversion'))->publish;

    ?>
<div class="pxa-row pxa-mt2 pxa-mr2">
    <div class="pxa-col-12">
        <div class="pxa-card">
            <div class="pxa-row pxa-align-center">
                <div class="pxa-col-xs-12 pxa-col-3">
                    <?= plyr_video('https://youtu.be/HYNrvzhUJa8', 'youtube'); ?>
                </div>
                <div class="pxa-col-xs-12 pxa-col-9">
                    <div class="pxa-text-2xl"><?= sprintf(__('Seja Bem Vindo ao %s!', 'pixel-x-app'), PXA_NAME) ?></div>
                    <div class="pxa-text-base"><?= __('Assista ao vídeo ao lado agora, e aprenda como começar a usar o plugin agora mesmo.', 'pixel-x-app') ?></div>
                    <hr>
                    <div class="pxa-row">
                        <div class="pxa-col-xs-12 pxa-col-6">
                            <div class="pxa-text-xl"><?= __('Comece por aqui', 'pixel-x-app') ?></div>
                            <div class="pxa-text-sm"><?= __('Depois, siga as recomendações ao lado.', 'pixel-x-app') ?></div>
                            <?=
                                        PXA_component_button([
                                            'title' => __('Assista aos Tutoriais', 'pixel-x-app'),
                                            'link'  => PXA_PAGE_DOCUMENTATION,
                                            'icon'  => 'format-video',
                                            'class' => 'pxa-btn-secondary pxa-btn-big'
                                        ]);
    ?>
                        </div>
                        <div class="pxa-col-xs-12 pxa-col-6">
                            <div class="pxa-text-xl"><?= __('Recomendações:', 'pixel-x-app') ?></div>
                            <ul class="pxa-list-step">
                                <?php if ( ! PXA_license_status()): ?>
                                <li>
                                    <a href="<?= PXA_PAGE_LICENSE ?>">
                                        <?= __('Ative sua licença agora para liberar os recursos do plugin', 'pixel-x-app') ?>
                                    </a>
                                </li>
                                <?php endif?>
                                <li>
                                    <a href="<?= PXA_PAGE_DOCUMENTATION ?>#unique-link-1">
                                        <?= __('Como otimizar seu funil de vendas com conversões personalizadas', 'pixel-x-app') ?>
                                    </a>
                                </li>
                                <li>
                                    <a href="<?= PXA_PAGE_DOCUMENTATION ?>#unique-link-2">
                                        <?= __('Pixel Xpert - Tenha um consultor de otimização para suas campanhas', 'pixel-x-app') ?>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php

    echo PXA_component_widget_stats(
        'Total de Leads',
        $count_leads,
        'groups'
    );

    echo    PXA_component_widget_stats(
        'Total de Eventos',
        $count_events,
        'star-filled'
    );

    echo    PXA_component_widget_stats(
        'Média de Eventos por Lead',
        ($count_leads)
                ? number_format(($count_events / $count_leads), 2, '.', '')
                : 0,
        'id'
    );

    echo    PXA_component_widget_stats(
        'Total de Conversões Criadas',
        $count_conversions,
        'location'
    );

    /*
     * Country
     */
    PXA_widget_list([
        'description' => __('Acessos por País', 'pixel-x-app'),
        'content'     => collect($leads)
            ->countBy(function ($item) {
                return ucwords(data_get($item, PXA_key('adress_country_name')));
            })
            ->map(function ($item, $key) {
                if (blank($key)) {
                    $key = __('Indefinido', 'pixel-x-app');
                }

                return[
                    __('Nome', 'pixel-x-app')  => ucwords($key),
                    __('Valor', 'pixel-x-app') => $item
                ];
            })
            ->values()
            ->toJson()
    ]);

    /*
     * State
     */
    PXA_widget_list([
        'description' => __('Acessos por Estado', 'pixel-x-app'),
        'content'     => collect($leads)
            ->countBy(function ($item) {
                return ucwords(data_get($item, PXA_key('adress_state')));
            })
            ->map(function ($item, $key) {
                if (blank($key)) {
                    $key = __('Indefinido', 'pixel-x-app');
                }

                return[
                    __('Nome', 'pixel-x-app')  => ucwords($key),
                    __('Valor', 'pixel-x-app') => $item
                ];
            })

            ->values()
            ->toJson()
    ]);

    /*
     * City
     */
    PXA_widget_list([
        'description' => __('Acessos por Cidade', 'pixel-x-app'),
        'content'     => collect($leads)
            ->countBy(function ($item) {
                return ucwords(data_get($item, PXA_key('adress_city')));
            })
            ->map(function ($item, $key) {
                if (blank($key)) {
                    $key = __('Indefinido', 'pixel-x-app');
                }

                return[
                    __('Nome', 'pixel-x-app')  => ucwords($key),
                    __('Valor', 'pixel-x-app') => $item
                ];
            })

            ->values()
            ->toJson()
    ]);

    /*
     * Days
     */
    PXA_widget_chart([
        'description' => __('Leads por Dia', 'pixel-x-app'),
        'grid'        => 6,
        'content'     => collect($leads)
            ->countBy(function ($item) {
                $date = new DateTime(data_get($item, 'post_date'));

                return $date->format('Y-m-d');
            })
            ->map(function ($item, $key) {
                return[
                    'name'  => $key,
                    'count' => $item
                ];
            })
            ->reverse()
            ->toArray()
    ]);

    /*
     * Month
     */
    PXA_widget_chart([
        'description' => __('Leads por Mês', 'pixel-x-app'),
        'grid'        => 6,
        'content'     => collect($leads)
            ->countBy(function ($item) {
                $date = new DateTime(data_get($item, 'post_date'));

                return $date->format('Y-m');
            })
            ->map(function ($item, $key) {
                return[
                    'name'  => $key,
                    'count' => $item
                ];
            })
            ->reverse()
            ->toArray()
    ]);
    ?>
</div>
<?php
}
