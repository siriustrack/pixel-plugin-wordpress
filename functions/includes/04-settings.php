<?php

use Carbon_Fields\Container;
use Carbon_Fields\Field;

add_action('carbon_fields_register_fields', function () {
    $remote_run    = get_rest_url(null, PXA_DOMAIN . '/v1/remote-run?token=') . PXA_get_custom_token();
    $remote_script = '<script type="text/javascript">!function(){var e=window.location.href,t=document.title,n=Date.now(),o=document.createElement("script");o.src="' . PXA_PIXEL_REMOTE() . 'url="+encodeURIComponent(e)+"&title="+t+"&time="+n,o.async=!0,document.head.appendChild(o)}();</script>';

    if (data_get($_GET, 'page') == PXA_key('settings')) {
        update_option(PXA__key('remote_run'), $remote_run);
        update_option(PXA__key('remote_script'), $remote_script);
    }

    Container::make('theme_options', __('Configurações', 'pixel-x-app'))
        ->set_page_parent(PXA_key('dashboard'))
        ->set_page_file(PXA_key('settings'))
        ->set_page_menu_position(6)
        ->where('current_user_capability', '=', 'manage_options')
        ->add_tab(__('Facebook', 'pixel-x-app'), [
            Field::make('complex', PXA_key('fb_pixels'), '')
                ->set_layout('tabbed-horizontal')
                ->set_collapsed(true)
                // ->set_duplicate_groups_allowed(false)
                ->setup_labels([
                    'plural_name'   => 'Pixels',
                    'singular_name' => 'Pixel',
                ])
                ->add_fields([
                    Field::make('text', 'pixel', __('ID do Pixel', 'pixel-x-app'))
                        ->set_attribute('placeholder', '123...')
                        ->set_help_text(__('Insira o ID do seu Pixel do Facebook nesse campo.', 'pixel-x-app')),

                    Field::make('textarea', 'token', __('Token de Conversão', 'pixel-x-app'))
                        ->set_attribute('placeholder', 'ABC...')
                        ->set_help_text(__('Insira o Token da API de Conversão do seu Pixel do Facebook nesse campo.', 'pixel-x-app')),

                    Field::make('checkbox', 'test_status', __('Ativar Teste de Marcação?', 'pixel-x-app'))
                        ->set_option_value('yes')
                        ->set_help_text(__('Esse recurso deve ser ativado temporariamente apenas, desativar após finalizar os testes.', 'pixel-x-app'))
                        ->set_width(50),

                    Field::make('text', 'test_code', __('Código de Teste', 'pixel-x-app'))
                        ->set_attribute('placeholder', 'ABC...')
                        ->set_conditional_logic([
                            [
                                'field' => 'test_status',
                                'value' => true,
                            ]
                        ])
                        ->set_width(50),
                ]),

            Field::make('text', PXA_key('fb_domain'), __('Tag de Verificação de Domínio (Opcional)', 'pixel-x-app'))
                ->set_attribute('placeholder', '<meta name="facebook-domain-verification" content="..." />')
                ->set_help_text(__('Insira a tag HTML completa fornecido pelo Facebook nesse campo, apenas se você não tenha verificado seu domínio no Facebook.', 'pixel-x-app')),
        ])
        ->add_tab(__('Google', 'pixel-x-app'), [
            Field::make('text', PXA_key('gg_analytics'), __('Google Analytics', 'pixel-x-app'))
                ->set_attribute('placeholder', 'G-**********')
                ->set_width(50),

            Field::make('text', PXA_key('gg_tm'), __('Google Tag Manager', 'pixel-x-app'))
                ->set_attribute('placeholder', 'GTM-********')
                ->set_width(50),

            // Field::make('textarea', PXA_key('gg_analytics_token'), __('Token da API API do Protocolo de Medição ', 'pixel-x-app'))
            //     ->set_width(50),

            Field::make('text', PXA_key('gg_ads'), __('Google Ads', 'pixel-x-app'))
                ->set_help_text(__('Em desenvolvimento as otimizações de rastreamento, atualmente apenas adição do pixel.', 'pixel-x-app'))
                ->set_attribute('placeholder', 'AW-*********'),

            Field::make('text', PXA_key('gg_ads_domain'), __('Tag de Verificação de Domínio', 'pixel-x-app'))
                ->set_attribute('placeholder', '<meta name="google-domain-verification" content="..." />')
                ->set_help_text(__('Cole o código completo fornecido pelo Facebook.', 'pixel-x-app')),
        ])
        ->add_tab(__('Scripts', 'pixel-x-app'), [
            Field::make('textarea', PXA_key('header_scripts'), __('Scripts no Cabeçalho', 'pixel-x-app'))
                ->set_help_text(__('Se você precisar adicionar scripts ao seu cabeçalho, você deve inseri-los aqui. (&lt;head&gt;)', 'pixel-x-app')),

            Field::make('textarea', PXA_key('body_scripts'), __('Scripts no Começo do Corpo', 'pixel-x-app'))
                ->set_help_text(__('Se você precisar adicionar scripts começo do corpo da página, você deve inseri-los aqui. (&lt;body&gt;)', 'pixel-x-app')),

            Field::make('textarea', PXA_key('footer_scripts'), __('Scripts no Rodapé', 'pixel-x-app'))
                ->set_help_text(__('Se você precisar adicionar scripts ao rodapé, você deve inseri-los aqui. (&lt;/body&gt;)', 'pixel-x-app')),
        ])
        ->add_tab(__('Ferramentas', 'pixel-x-app'), [
            Field::make('separator', PXA_key('separator_heatmaps'), __('Mapa de Calor', 'pixel-x-app')),

            Field::make('text', PXA_key('tools_clarity'), __('Clarity', 'pixel-x-app'))
                ->set_help_text(__('Insira o ID do Projeto, você pode pegar o ID em Configurações > Visão Geral, no painel do <a href="https://clarity.microsoft.com/" target="_blank">Clarity</a>.', 'pixel-x-app'))
                ->set_width(25),

            Field::make('text', PXA_key('tools_hotjar'), __('Hotjar', 'pixel-x-app'))
                ->set_help_text(__('Insira o Site ID, você pode pegar o ID na Listagem de Sites, no painel do <a href="https://insights.hotjar.com/site/list" target="_blank">Hotjar</a>.', 'pixel-x-app'))
                ->set_width(25),

            Field::make('separator', PXA_key('separator_parameter_passing'), __('Passagem de Parâmetros da URL', 'pixel-x-app'))
                ->set_help_text(__('Por padrão, todos os parâmetros que estiverem presentes na URL, serão repassados para todos os botões com links externos ao site.
                    <br>Caso já tenha sido capturado em acessos anteriores UTMs, FBC, FBP, SRC e SCK, também serão incluidos nos links, mesmo não presentes na URL.', 'pixel-x-app')),

            Field::make('checkbox', PXA_key('disable_parameter_passing'), __('Desativar Passagem de Parâmetros da URL', 'pixel-x-app'))
                ->set_help_text(__('Desative a passagem de parâmetros da URL para todos os links externos.', 'pixel-x-app'))
                ->set_option_value('true')
                ->set_width(30),

            Field::make('checkbox', PXA_key('parameter_passing_lead'), __('Passagem de Dados do Lead como Parâmetros', 'pixel-x-app'))
                ->set_help_text(__('Se ativo, será incluido em todos os links externos ao site, os dados do lead já capturados, como nome, email, telefone e ip.<br><b>Alerta:</b> O Facebook pode informar que é contra a política de privacidade deles a passagem de dados pessoais pela URL.', 'pixel-x-app'))
                ->set_option_value('true')
                ->set_width(70)
                ->set_conditional_logic([
                    [
                        'field' => PXA_key('disable_parameter_passing'),
                        'value' => false,
                    ]
                ]),

            Field::make('separator', PXA_key('separator_input'), __('Nomes Personalizados de Campos', 'pixel-x-app'))
                ->set_help_text(__('Caso utilize alguma plataforma que não permite personalizar o nome dos campos do formulário, preencha abaixo com o nome dos campos.', 'pixel-x-app')),

            Field::make('text', PXA_key('input_custom_name'), __('Nome do Campo de "Nome"', 'pixel-x-app'))
                ->set_help_text(__('Separe por virgula ",", caso queira informar mais de um nome de campo.', 'pixel-x-app'))
                ->set_width(33),

            Field::make('text', PXA_key('input_custom_email'), __('Nome do Campo de "Email"', 'pixel-x-app'))
                ->set_help_text(__('Separe por virgula ",", caso queira informar mais de um nome de campo.', 'pixel-x-app'))
                ->set_width(33),

            Field::make('text', PXA_key('input_custom_phone'), __('Nome do Campo de "Telefone"', 'pixel-x-app'))
                ->set_help_text(__('Separe por virgula ",", caso queira informar mais de um nome de campo.', 'pixel-x-app'))
                ->set_width(34),

            Field::make('separator', PXA_key('separator_phone'), __('Validação e Mascará de Telefone', 'pixel-x-app')),

            Field::make('checkbox', PXA_key('phone_valid'), __('Validar Telefone antes de Enviar Evento?', 'pixel-x-app'))
                ->set_help_text(__('Ao ativar, o campo de telefone será validador e formatado após o lead informar o telefone.', 'pixel-x-app'))
                ->set_option_value('yes')
                ->set_width(33),

            Field::make('text', PXA_key('phone_country'), __('DDI do País de Telefone', 'pixel-x-app'))
                ->set_default_value('55')
                ->set_attribute('type', 'number')
                ->set_attribute('min', '0')
                ->set_width(34)
                ->set_conditional_logic([
                    [
                        'field' => PXA_key('phone_valid'),
                        'value' => true,
                    ]
                ]),

            Field::make('checkbox', PXA_key('phone_update'), __('Preencher Campo com Telefone Validado?', 'pixel-x-app'))
                ->set_help_text(__('Ao ativar, o campo de telefone será preenchido com o valor validado, após validação.', 'pixel-x-app'))
                ->set_option_value('yes')
                ->set_width(33)
                ->set_conditional_logic([
                    [
                        'field' => PXA_key('phone_valid'),
                        'value' => true,
                    ]
                ]),

            Field::make('checkbox', PXA_key('phone_mask_status'), __('Ativar Mascará de Telefone Personalizada?', 'pixel-x-app'))
                ->set_help_text(__('Permite definir uma mascará de preenchimento nos campos com classe CSS <span data-clipboard="pxa_mask_phone">".pxa_mask_phone"</span>.', 'pixel-x-app'))
                ->set_option_value('yes')
                ->set_conditional_logic([
                    [
                        'field' => PXA_key('phone_update'),
                        'value' => false,
                    ]
                ]),

            Field::make('text', PXA_key('phone_mask'), __('Mascará de Telefone Personalizada', 'pixel-x-app'))
                ->set_help_text(__('Defina a mascará utilizando "0" como variável que o lead precisa preencher. O que estiver entre "{}" são valores absolutos e entre "[]" são valores opcionais. Exemplo: <span data-clipboard="+{55} (00) [9]0000-0000">+{55} (00) [9]0000-0000</span>', 'pixel-x-app'))
                ->set_default_value('+{55} (00) [9]0000-0000')
                ->set_required(true)
                ->set_conditional_logic([
                    [
                        'field' => PXA_key('phone_update'),
                        'value' => false,
                    ], [
                        'field' => PXA_key('phone_mask_status'),
                        'value' => true,
                    ]
                ]),

            Field::make('checkbox', PXA_key('phone_mask_inter'), __('Ativar Mascará de Telefone Internacional?', 'pixel-x-app'))
                ->set_help_text(__('Permite ativar o recurso de mascará de telefone internacional, para que o cliente selecione o país e insira o seu número de telefone para os campos com classe CSS <span data-clipboard="pxa_mask_phone_inter">".pxa_mask_phone_inter"</span>.', 'pixel-x-app'))
                ->set_option_value('yes')
                ->set_conditional_logic([
                    [
                        'field' => PXA_key('phone_update'),
                        'value' => false,
                    ]
                ]),
        ])
        ->add_tab(__('Valores Globais', 'pixel-x-app'), [
            Field::make('separator', PXA_key('separator_product'), __('Informações de Produto', 'pixel-x-app')),

            Field::make('text', PXA_key('product_name'), __('Nome do Produto', 'pixel-x-app'))
                ->set_help_text(__('Informe o nome do produto igual cadastrado na plataforma de pagamento.', 'pixel-x-app'))
                ->set_width(33),

            Field::make('text', PXA_key('product_id'), __('ID do Produto', 'pixel-x-app'))
                ->set_help_text(__('Informe o ID do produto igual cadastrado na plataforma de pagamento.', 'pixel-x-app'))
                ->set_width(33),

            Field::make('text', PXA_key('offer_ids'), __('IDs da Oferta', 'pixel-x-app'))
                ->set_help_text(__('Separe os IDs das ofertas por ",", caso tenha mais de uma oferta na página.', 'pixel-x-app'))
                ->set_width(34),

            Field::make('text', PXA_key('product_value'), __('Valor do Produto', 'pixel-x-app'))
                ->set_help_text(__('Informe o valor do produto ou oferta.', 'pixel-x-app'))
                ->set_attribute('type', 'number')
                ->set_attribute('min', '0')
                ->set_attribute('step', '0.01')
                ->set_width(33),

            Field::make('text', PXA_key('predicted_ltv'), __('LTV Previsto Base', 'pixel-x-app'))
                ->set_help_text(__('Para produtos de recorrência é enviado o LTV (Lifetime Value) previsto do cliente com sua oferta, aqui você pode definir o mínimo a ser enviado como LTV.', 'pixel-x-app'))
                ->set_attribute('type', 'number')
                ->set_attribute('min', '0')
                ->set_attribute('step', '0.01')
                ->set_width(33),

            Field::make('text', PXA_key('currency'), __('Moeda', 'pixel-x-app'))
                ->set_help_text(__('Defina o código da moeda do produto conforme ISO 4217.<br><a href="https://pt.wikipedia.org/wiki/ISO_4217" target="_blank">Listagem de Moeda</a>', 'pixel-x-app'))
                ->set_default_value('BRL')
                ->set_width(34),
        ])
        ->add_tab(__('Configurações Avançadas', 'pixel-x-app'), [
            // Field::make('set', PXA_key('disabled_features'), __('Desativar Recursos Avançados (Em Breve)', 'pixel-x-app'))
            //     ->set_help_text(__('Aqui você pode selecionar quais recursos avançados deseja desativar.', 'pixel-x-app'))
            //     ->set_options([
            //         'lead'        => __('Informações do Lead', 'pixel-x-app'),
            //         'geolocation' => __('Geo Localização', 'pixel-x-app'),
            //         'content'     => __('Informações da Página', 'pixel-x-app'),
            //         'event'       => __('Informações do Evento', 'pixel-x-app'),
            //         'cookie'      => __('Marcação de Cookies', 'pixel-x-app'),
            //         'form'        => __('Formulário Submetido', 'pixel-x-app'),
            //         'scroll'      => __('Rolagem de Página', 'pixel-x-app'),
            //         'video'       => __('Visualização de Video', 'pixel-x-app'),
            //         'time'        => __('Tempo de Visualização', 'pixel-x-app'),
            //     ]),

            Field::make('checkbox', PXA_key('disabled_user_logged'), __('Desativar Rastreamento de Usuário Logados', 'pixel-x-app'))
                ->set_help_text(__('Caso não queira que os admins do site e editores auxilirem sejam rastreados ao acessarem e editarem elementos do site.', 'pixel-x-app'))
                ->set_option_value('yes'),

            // Field::make('checkbox', PXA_key('always_pageview'), __('Ativar Visualização de Página Padrão Sempre', 'pixel-x-app'))
            //     ->set_help_text(__('Por padrão, caso seja configurado uma conversão de "Visualização de Página", é desativado o PageView. Ative esse recurso se deseja sempre enviar o "Visualização de Página" padrão também.', 'pixel-x-app'))
            //     ->set_option_value('true'),

            Field::make('checkbox', PXA_key('web_priority'), __('Prioridade em Pixel Web', 'pixel-x-app'))
                ->set_help_text(__('Por padrão, nosso sistema é otimizado para executar a API de Conversão e depois o Pixel Web, o que pode ocorrer uma diminuição do Connect Rate. Ative esse recurso para inverter a ordem de rastreamento.', 'pixel-x-app'))
                ->set_option_value('true'),

            Field::make('checkbox', PXA_key('send_event_immediate'), __('Envio de API de Conversão Imediato', 'pixel-x-app'))
                ->set_help_text(sprintf(
                    '%s<br><b>%s</b>',
                    __('Caso queira que os eventos sejam enviados pela API de Conversão imediatamente, ative esse recurso.', 'pixel-x-app'),
                    __('Não recomendado para sites com grande quantidade de eventos.</b>', 'pixel-x-app'),
                ))
                ->set_option_value('true')
                ->set_width(50),

            Field::make('checkbox', PXA_key('delete_event_immediate'), __('Apagar Eventos Após Enviar', 'pixel-x-app'))
                ->set_help_text(__('Os eventos que forem enviados com sucesso, serão apagados em seguida, para não haver acumulo de eventos no site. Ajuda a diminuir o consumo de banco de dados.', 'pixel-x-app'))
                ->set_option_value('true')
                ->set_width(50),

            Field::make('separator', PXA_key('separator_remote'), __('Recursos Remotos', 'pixel-x-app')),

            Field::make('text', PXA_key('remote_run'), __('URL de Execução Remoto', 'pixel-x-app'))
                ->set_help_text(sprintf(
                    '%s <a href="%s" target="_blank">%s</a>.',
                    __('Saiba mais sobre Execução Remota e siga os passos no', 'pixel-x-app'),
                    'https://pixelx.app/tutorial-cron-remoto',
                    __('Tutorial de Execução Remoto.', 'pixel-x-app'),
                ))
                ->set_attribute('data-clipboard', $remote_run)
                ->set_attribute('readOnly', 'true')
                ->set_width(50),

            Field::make('text', PXA_key('remote_script'), __('Pixel X Externo', 'pixel-x-app'))
                ->set_help_text(sprintf(
                    __('Para o rastreamento avançado em subdomínios e/ou em páginas HTML externas ao site, do mesmo domínio. <a href="%s" target="_blank">Saiba mais sobre Rastreamento Remoto</a>.', 'pixel-x-app'),
                    'https://pixelx.app/tutorial-tracking-remote'
                ))
                ->set_default_value($remote_script)
                ->set_attribute('data-clipboard', $remote_script)
                ->set_attribute('readOnly', 'true')
                ->set_width(50),

            Field::make('separator', PXA_key('separator_webhooks'), __('Webhooks', 'pixel-x-app'))
                ->set_help_text(__('Ao ser configura alguma URL de Webhook, será feito o envio dos dados de hora em hora, referente a todos os eventos e leads novos cadastrados ou atualizados no intervalo.
                <br>Caso esteja ativo o recurso de "Apagar Eventos Após Enviar", o webhook irá enviado imediatamente antes do evento ser apagado.', 'pixel-x-app')),

            Field::make('complex', PXA_key('webhooks'), __('Webhook'))
                ->set_layout('tabbed-horizontal')
                ->add_fields([
                    Field::make('text', 'url', __('URL', 'pixel-x-app'))
                        ->set_width(75),
                    Field::make('set', 'type', __('Dados a Serem Enviados', 'pixel-x-app'))
                        ->set_options([
                            'event' => __('Dados de Eventos', 'pixel-x-app'),
                            'lead'  => __('Dados de Leads', 'pixel-x-app'),
                        ])
                        ->set_width(25),
                ])
        ])
        ->add_tab(__('Comandos / Importação / Limpeza', 'pixel-x-app'), [
            Field::make('separator', PXA_key('separator_command'), __('Comandos', 'pixel-x-app'))
                ->set_help_text(__('Lista de comandos manuais, para execuções de manutenção.', 'pixel-x-app')),

            Field::make('html', PXA_key('command_events_send'))
                ->set_html(PXA_component_button([
                    'title' => __('Enviar Eventos Pendentes', 'pixel-x-app'),
                    'link'  => admin_url('admin.php?pxa_command=' . PXA_key('command_events_send')),
                    'class' => 'pxa-btn-primary',
                ]))
                ->set_help_text(__('Envie os eventos pelo servidor por API de Conversão manualmente.', 'pixel-x-app'))
                ->set_width(25),

            Field::make('html', PXA_key('command_events_resend'))
                ->set_html(PXA_component_button([
                    'title' => __('Reenviar Eventos com Erro', 'pixel-x-app'),
                    'link'  => admin_url('admin.php?pxa_command=' . PXA_key('command_events_resend')),
                    'class' => 'pxa-btn-primary',
                ]))
                ->set_help_text(__('Reenvie os eventos que deram erro pelo servidor por API de Conversão manualmente.', 'pixel-x-app'))
                ->set_width(25),

            Field::make('html', PXA_key('command_events_delete'))
                ->set_html(PXA_component_button([
                    'title'        => __('Apagar Eventos Enviados', 'pixel-x-app'),
                    'link'         => admin_url('admin.php?pxa_command=' . PXA_key('command_events_delete')),
                    'class'        => 'pxa-btn-primary',
                    'confirmation' => __('Deseja mesmo apagar todos os registros?', 'pixel-x-app'),
                ]))
                ->set_help_text(__('Apague todos os eventos publicados e já enviados por servidor.', 'pixel-x-app'))
                ->set_width(25),

            Field::make('html', PXA_key('command_events_delete_error'))
                ->set_html(PXA_component_button([
                    'title'        => __('Apagar Eventos com Erro', 'pixel-x-app'),
                    'link'         => admin_url('admin.php?pxa_command=' . PXA_key('command_events_delete_error')),
                    'class'        => 'pxa-btn-primary',
                    'confirmation' => __('Deseja mesmo apagar todos os registros?', 'pixel-x-app'),
                ]))
                ->set_help_text(__('Apague todos os eventos com erro de envio por servidor.', 'pixel-x-app'))
                ->set_width(25),

            Field::make('html', PXA_key('command_leads_delete'))
                ->set_html(PXA_component_button([
                    'title'        => __('Apagar Todos os Leads e Eventos', 'pixel-x-app'),
                    'link'         => admin_url('admin.php?pxa_command=' . PXA_key('command_leads_delete')),
                    'class'        => 'pxa-btn-primary',
                    'confirmation' => __('Deseja mesmo apagar todos os registros?', 'pixel-x-app'),
                ]))
                ->set_help_text(__('Apague todos os leads e eventos cadastrados.', 'pixel-x-app'))
                ->set_width(25),

            Field::make('html', PXA_key('command_leads_anon_delete'))
                ->set_html(PXA_component_button([
                    'title'        => __('Apagar Todos os Leads Anônimos', 'pixel-x-app'),
                    'link'         => admin_url('admin.php?pxa_command=' . PXA_key('command_leads_anon_delete')),
                    'class'        => 'pxa-btn-primary',
                    'confirmation' => __('Deseja mesmo apagar todos os registros?', 'pixel-x-app'),
                ]))
                ->set_help_text(__('Apague todos os leads sem informações capturadas e eventos dos leads.', 'pixel-x-app'))
                ->set_width(25),

            Field::make('html', PXA_key('command_conversions_delete'))
                ->set_html(PXA_component_button([
                    'title'        => __('Apagar Todas as Conversões', 'pixel-x-app'),
                    'link'         => admin_url('admin.php?pxa_command=' . PXA_key('command_conversions_delete')),
                    'class'        => 'pxa-btn-primary',
                    'confirmation' => __('Deseja mesmo apagar todos os registros?', 'pixel-x-app'),
                ]))
                ->set_help_text(__('Apague todas as conversões cadastradas.', 'pixel-x-app'))
                ->set_width(25),

            Field::make('html', PXA_key('command_integrations_delete'))
                ->set_html(PXA_component_button([
                    'title'        => __('Apagar Todas as Integrações', 'pixel-x-app'),
                    'link'         => admin_url('admin.php?pxa_command=' . PXA_key('command_integrations_delete')),
                    'class'        => 'pxa-btn-primary',
                    'confirmation' => __('Deseja mesmo apagar todos os registros?', 'pixel-x-app'),
                ]))
                ->set_help_text(__('Apague todas as integrações cadastradas.', 'pixel-x-app'))
                ->set_width(25),

            Field::make('html', PXA_key('command_queue_worker'))
                ->set_html(PXA_component_button([
                    'title' => __('Processar Fila de Servidor', 'pixel-x-app'),
                    'link'  => admin_url('admin.php?pxa_command=' . PXA_key('command_queue_worker')),
                    'class' => 'pxa-btn-primary',
                ]))
                ->set_help_text(__('Processa todas as ações agendadas na filas.', 'pixel-x-app'))
                ->set_width(25),

            Field::make('html', PXA_key('command_queue_cleaner'))
                ->set_html(PXA_component_button([
                    'title' => __('Limpar Fila de Servidor', 'pixel-x-app'),
                    'link'  => admin_url('admin.php?pxa_command=' . PXA_key('command_queue_cleaner')),
                    'class' => 'pxa-btn-primary',
                ]))
                ->set_help_text(__('Apague todas as ações em filas.', 'pixel-x-app'))
                ->set_width(25),

            Field::make('html', PXA_key('command_models_cleaner'))
                ->set_html(PXA_component_button([
                    'title' => __('Processar Limpeza Manual', 'pixel-x-app'),
                    'link'  => admin_url('admin.php?pxa_command=' . PXA_key('command_models_cleaner')),
                    'class' => 'pxa-btn-primary',
                ]))
                ->set_help_text(__('Realizar a limpeza da Pixel X manualmente.', 'pixel-x-app'))
                ->set_width(25),

            Field::make('html', PXA_key('command_delete_all'))
                ->set_html(PXA_component_button([
                    'title'        => __('Apagar Todos os Dados', 'pixel-x-app'),
                    'link'         => admin_url('admin.php?pxa_command=' . PXA_key('command_delete_all')),
                    'class'        => 'pxa-btn-primary',
                    'confirmation' => __('Deseja mesmo apagar todos os registros?', 'pixel-x-app'),
                ]))
                ->set_help_text(__('Apague todas as configurações e registros.', 'pixel-x-app'))
                ->set_width(25),

            Field::make('html', PXA_key('command_send_webhook'))
                ->set_html(PXA_component_button([
                    'title' => __('Enviar Webhooks Manualmente', 'pixel-x-app'),
                    'link'  => admin_url('admin.php?pxa_command=' . PXA_key('command_send_webhook')),
                    'class' => 'pxa-btn-primary',
                ]))
                ->set_help_text(__('Reenviar os dados de leads e eventos para os webhooks cadastrados.', 'pixel-x-app'))
                ->set_width(25),

            Field::make('separator', PXA_key('separator_import'), __('Importação', 'pixel-x-app')),

            Field::make('file', PXA_key('import_conversions'), __('Conversões'))
                ->set_type([ 'json' ]),

            Field::make('separator', PXA_key('separator_clean'), __('Limpeza Automática', 'pixel-x-app'))
                ->set_help_text(__('Automatize o processo de limpeza dos dados rastreados pela Pixel X App.', 'pixel-x-app')),

            Field::make('checkbox', PXA_key('cleaning_status'), __('Ativar Limpeza Automática?', 'pixel-x-app'))
                ->set_option_value('true')
                ->set_default_value('true')
                ->set_width(25),

            Field::make('text', PXA_key('cleaning_time'), __('Período de Limpeza', 'pixel-x-app'))
                ->set_help_text(__('Todo dia será feita a limpeza no horário definido. Padrão: 02:00.', 'pixel-x-app'))
                ->set_default_value('02:00')
                ->set_attribute('type', 'time')
                ->set_conditional_logic([[
                    'field' => PXA_key('cleaning_status'),
                    'value' => true,
                ]])
                ->set_width(25),

            Field::make('text', PXA_key('cleaning_period_event'), __('Apagar Eventos com Mais de "X" Dias', 'pixel-x-app'))
                ->set_help_text(__('Defina a partir de quantos dias atrás, deve ser feita a limpeza. Padrão: 7 dias.', 'pixel-x-app'))
                ->set_default_value('3')
                ->set_attribute('type', 'number')
                ->set_attribute('min', '1')
                ->set_attribute('step', '1')
                ->set_conditional_logic([[
                    'field' => PXA_key('cleaning_status'),
                    'value' => true,
                ]])
                ->set_width(25),

            Field::make('text', PXA_key('cleaning_period_lead'), __('Apagar Leads com Mais de "X" Dias', 'pixel-x-app'))
                ->set_help_text(__('Defina a partir de quantos dias atrás, deve ser feita a limpeza. Se definir como "0", não serão apagados. Padrão: 30 dias.', 'pixel-x-app'))
                ->set_default_value('7')
                ->set_attribute('type', 'number')
                ->set_attribute('min', '0')
                ->set_attribute('step', '1')
                ->set_conditional_logic([[
                    'field' => PXA_key('cleaning_status'),
                    'value' => true,
                ]])
                ->set_width(25),
        ]);
});

/*
 * Import
 */
add_action('carbon_fields_theme_options_container_saved', function ($post_id, $container_id) {
    if ($container_id->get_page_file() == PXA_key('settings')) {
        if ( ! PXA_license_status()) {
            return;
        }

        pxa_cache_flush();

        $import_conversions = PXA_get_setting('import_conversions');

        if ($import_conversions) {
            // wp_get_attachment_url | get_attached_file
            $file_path = get_attached_file($import_conversions);
            if (file_exists($file_path)) {
                $file_content = file_get_contents($file_path);
                $conversions  = collect(json_decode($file_content));

                $conversions->each(function ($item, $key) {
                    $new = wp_insert_post([
                        'post_title'  => $item->title,
                        'post_status' => 'publish',
                        'post_type'   => PXA_key('conversion'),
                    ]);

                    // Set Values
                    carbon_set_post_meta($new, PXA_key('display_on'), $item->display_on);
                    carbon_set_post_meta($new, PXA_key('separator_trigger'), $item->separator_trigger);
                    carbon_set_post_meta($new, PXA_key('trigger'), $item->trigger);
                    carbon_set_post_meta($new, PXA_key('time'), $item->time);
                    carbon_set_post_meta($new, PXA_key('class'), $item->class);
                    carbon_set_post_meta($new, PXA_key('scroll'), $item->scroll);
                    carbon_set_post_meta($new, PXA_key('separator_event'), $item->separator_event);
                    carbon_set_post_meta($new, PXA_key('event'), $item->event);
                    carbon_set_post_meta($new, PXA_key('content_name'), $item->content_name);
                    carbon_set_post_meta($new, PXA_key('event_custom'), $item->event_custom);
                    carbon_set_post_meta($new, PXA_key('separator_product'), $item->separator_product);
                    carbon_set_post_meta($new, PXA_key('product_name'), $item->product_name);
                    carbon_set_post_meta($new, PXA_key('product_id'), $item->product_id);
                    carbon_set_post_meta($new, PXA_key('product_value'), $item->product_value);
                    carbon_set_post_meta($new, PXA_key('offer_ids'), $item->offer_ids);
                });

                wp_delete_attachment($import_conversions, true);

                wp_redirect(admin_url('edit.php?post_type=' . PXA_PREFIX . 'conversion&' . PXA_PREFIX . 'notice=import_conversion'));
                exit();
            }
        }
    }
}, 10, 3);
